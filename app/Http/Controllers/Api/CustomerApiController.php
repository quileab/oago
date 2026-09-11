<?php

namespace App\Http\Controllers\Api;

use App\Enums\OrderStatus;
use App\Enums\Role;
use App\Helpers\SettingsHelper;
use App\Http\Controllers\Controller;
use App\Mail\OrderMail;
use App\Models\AltUser;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ShippingDetail;
use App\Models\Tag;
use App\Models\User;
use App\Services\PriceListService;
use App\Services\SliderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class CustomerApiController extends Controller
{
    /**
     * Listar productos visibles con precios adaptados al usuario.
     */
    public function products(Request $request): JsonResponse
    {
        $user = current_user() ?? $request->user();
        if (! $user) {
            return response()->json(['message' => 'No autorizado'], 401);
        }

        $query = Product::where('published', true)
            ->where('visibility', 'visible')
            ->where(DB::raw('ifnull(model, "")'), '!=', 'consumo interno');

        if ($request->has('search') && ! empty($request->input('search'))) {
            $terms = array_filter(explode(' ', $request->input('search')));
            $query->where(function ($q) use ($terms) {
                foreach ($terms as $term) {
                    $q->where(DB::raw('concat(description, " ", ifnull(model, ""), " ", ifnull(brand, ""), " ", ifnull(product_type, ""), " ", ifnull(category, ""), " ", ifnull(tags, ""))'), 'like', "%$term%");
                }
            });
        }

        if ($request->has('category') && $request->input('category') !== '') {
            $query->where('category', $request->input('category'));
        }
        if ($request->has('brand') && $request->input('brand') !== '') {
            $query->where('brand', $request->input('brand'));
        }
        if ($request->has('featured') && $request->input('featured') !== '') {
            $query->where('featured', filter_var($request->input('featured'), FILTER_VALIDATE_BOOLEAN) ? 1 : 0);
        }
        if ($request->has('tag') && $request->input('tag') !== '') {
            $query->whereHas('tags', fn ($q) => $q->where('slug', $request->input('tag')));
        }

        $products = $query->paginate($request->input('per_page', 30));

        // Enriquecer productos con el precio efectivo del usuario
        $priceService = app(PriceListService::class);
        $products->getCollection()->transform(function ($product) use ($user, $priceService) {
            $listId = $user->list_id ?? 0;
            $basePrice = $priceService->getEffectivePrice($listId, $product->id, true) ?? (float) ($product->price ?? 0);
            $promoPrice = $priceService->getEffectivePrice($listId, $product->id, false);

            if ($promoPrice !== null && $promoPrice < $basePrice) {
                $product->price = $promoPrice;
                $product->base_price = $basePrice;
                $product->promo_price = $promoPrice;
            } else {
                $product->price = $basePrice;
                $product->base_price = null;
                $product->promo_price = null;
            }

            return $product;
        });

        return response()->json($products, 200);
    }

    /**
     * Listar pedidos del usuario autenticado.
     */
    public function orders(Request $request): JsonResponse
    {
        $user = current_user() ?? $request->user();
        if (! $user) {
            return response()->json(['message' => 'No autorizado'], 401);
        }

        $orders = Order::with(['items.product', 'shipping'])
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($orders, 200);
    }

    /**
     * Mostrar detalles de un pedido específico.
     */
    public function showOrder(Request $request, $id): JsonResponse
    {
        $user = current_user() ?? $request->user();
        if (! $user) {
            return response()->json(['message' => 'No autorizado'], 401);
        }

        $order = Order::with(['items.product', 'shipping'])
            ->where('user_id', $user->id)
            ->find($id);

        if (! $order) {
            return response()->json(['message' => 'Pedido no encontrado'], 404);
        }

        return response()->json($order, 200);
    }

    /**
     * Crear un pedido statelessly (Checkout para App Móvil).
     */
    public function placeOrder(Request $request): JsonResponse
    {
        $user = current_user() ?? $request->user();
        if (! $user) {
            return response()->json(['message' => 'No autorizado'], 401);
        }

        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'shipping' => 'required|array',
            'shipping.sending_method' => 'required|string',
            'shipping.payment_method' => 'required|string',
            'shipping.information' => 'nullable|string|max:240',
            'shipping.payment_detail' => 'nullable|string|max:100',
            'shipping.transport_detail' => 'nullable|string|max:100',
            'shipping.contact_name' => 'nullable|string|max:100',
            'shipping.contact_number' => 'nullable|string|max:50',
            'shipping.sending_address' => 'nullable|string|max:100',
            'shipping.sending_city' => 'nullable|string|max:50',
        ]);

        $items = $request->input('items');
        $shipping = $request->input('shipping');

        try {
            $order = DB::transaction(function () use ($items, $shipping, $user) {
                $total = 0;
                $productIds = array_column($items, 'product_id');
                $products = Product::whereIn('id', $productIds)->get()->keyBy('id');

                // 1. Validar Stock y Precios
                $itemsData = [];
                foreach ($items as $item) {
                    $product = $products->get($item['product_id']);
                    if (! $product) {
                        throw new \Exception("El producto ID {$item['product_id']} ya no está disponible.");
                    }

                    if ($product->stock < $item['quantity']) {
                        throw new \Exception("Stock insuficiente para {$product->description}. Disponible: {$product->stock}.");
                    }

                    $currentPrice = $user->getProductPrice($product);
                    $orderedQuantity = (int) $item['quantity'];
                    $billableQuantity = $orderedQuantity;

                    if ($product->hasBonus()) {
                        $bonusThreshold = $product->bonus_threshold + $product->bonus_amount;
                        $timesBonusApplies = floor($orderedQuantity / $bonusThreshold);
                        $freeUnits = $timesBonusApplies * $product->bonus_amount;
                        $billableQuantity = $orderedQuantity - $freeUnits;
                    }

                    $itemPrice = app(PriceListService::class)
                        ->calculateItemPrice($user->list_id ?? 1, $product, $billableQuantity);

                    // Add item data
                    $itemsData[] = [
                        'product_id' => $product->id,
                        'quantity' => $orderedQuantity,
                        'price' => $currentPrice,
                    ];

                    $total += $itemPrice;
                }

                if ($total == 0) {
                    throw new \Exception('El total del pedido no puede ser 0.');
                }

                // 2. Crear la Orden
                $order = Order::create([
                    'user_id' => $user->id,
                    'total_price' => $total,
                    'sending_method' => $shipping['sending_method'] ?? null,
                    'transport_detail' => $shipping['transport_detail'] ?? null,
                    'payment_method' => $shipping['payment_method'] ?? null,
                    'payment_detail' => $shipping['payment_detail'] ?? null,
                    'information' => strip_tags($shipping['information'] ?? ''),
                    'status' => OrderStatus::PENDING,
                ]);

                // 3. Crear los Detalles de Envío
                $defaultMethod = 'Envío a cargo de la Empresa a Dirección Registrada';
                if (($shipping['sending_method'] ?? '') !== $defaultMethod) {
                    ShippingDetail::create([
                        'order_id' => $order->id,
                        'contact_name' => $shipping['contact_name'] ?? null,
                        'address' => $shipping['sending_address'] ?? null,
                        'city' => $shipping['sending_city'] ?? null,
                        'postal_code' => $shipping['postal_code'] ?? $user->postal_code,
                        'phone' => $shipping['contact_number'] ?? $user->phone,
                        'shipping_status' => 'pending',
                    ]);
                }

                // 4. Crear los Items
                foreach ($itemsData as $itemData) {
                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $itemData['product_id'],
                        'quantity' => $itemData['quantity'],
                        'price' => $itemData['price'],
                    ]);
                }

                return $order;
            });

            // 5. Enviar Notificaciones
            try {
                $adminEmail = SettingsHelper::settings('order_placed_mail');
                $userEmail = $user->email;
                if ($userEmail) {
                    $mail = Mail::to($userEmail);
                    if ($adminEmail) {
                        $mail->cc($adminEmail);
                    }
                    $mail->send(new OrderMail($order->id, false));
                }
            } catch (\Exception $e) {
                Log::error('Error enviando correo de orden API móvil: '.$e->getMessage());
            }

            return response()->json([
                'message' => 'Pedido confirmado exitosamente.',
                'order' => $order->load(['items.product', 'shipping']),
            ], 201);

        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function showProduct(Request $request, int $id): JsonResponse
    {
        $user = current_user() ?? $request->user();
        $product = Product::where('published', true)->where('visibility', 'visible')->find($id);
        if (! $product) {
            return response()->json(['message' => 'Producto no encontrado'], 404);
        }
        $priceService = app(PriceListService::class);
        $listId = $user->list_id ?? 0;
        $basePrice = $priceService->getEffectivePrice($listId, $product->id, true) ?? (float) ($product->price ?? 0);
        $promoPrice = $priceService->getEffectivePrice($listId, $product->id, false);
        if ($promoPrice !== null && $promoPrice < $basePrice) {
            $product->price = $promoPrice;
            $product->base_price = $basePrice;
            $product->promo_price = $promoPrice;
        } else {
            $product->price = $basePrice;
            $product->base_price = null;
            $product->promo_price = null;
        }
        $product->load('tags');

        return response()->json($product, 200);
    }

    public function filters(Request $request): JsonResponse
    {
        $categories = Product::where('published', true)->where('visibility', 'visible')->distinct()->pluck('category')->filter()->values();
        $brands = Product::where('published', true)->where('visibility', 'visible')->distinct()->pluck('brand')->filter()->values();
        $tags = Tag::allCached()->map(fn ($t) => ['slug' => $t->slug, 'name' => $t->name])->values();

        return response()->json(['categories' => $categories, 'brands' => $brands, 'tags' => $tags], 200);
    }

    /**
     * Obtener el listado de slides/banners activos.
     */
    public function slider(Request $request): JsonResponse
    {
        return response()->json(app(SliderService::class)->getSlides(), 200);
    }

    /**
     * Obtener la lista de agentes de ventas (todos los usuarios y AltUsers con rol 'sales').
     * Incluye 'is_assigned': true/false para el cliente actual, ordenando los asignados primero.
     */
    public function sellers(Request $request): JsonResponse
    {
        $user = current_user() ?? $request->user();
        if (! $user) {
            return response()->json(['message' => 'No autorizado'], 401);
        }

        // Obtener asignaciones activas si el usuario es de tipo User
        $assignedMap = collect();
        if ($user instanceof User) {
            $assignedMap = $user->assignedSalesAgents()
                ->where('is_active', true)
                ->get()
                ->keyBy(fn ($item) => $item->sales_agent_type.'_'.$item->sales_agent_id);
        }

        // 1. Vendedores desde tabla users
        $salesUsers = User::where('role', Role::SALES)->get();

        // 2. Vendedores desde tabla alt_users
        $salesAltUsers = AltUser::where('role', Role::SALES)->get();

        $allSellers = collect();

        foreach ($salesUsers as $agent) {
            $key = User::class.'_'.$agent->id;
            $assignment = $assignedMap->get($key);
            $isAssigned = $assignment !== null;

            $allSellers->push([
                'id' => $agent->id,
                'name' => $agent->name,
                'lastname' => $agent->lastname ?? '',
                'full_name' => method_exists($agent, 'getFullNameAttribute') || isset($agent->fullName)
                    ? $agent->fullName
                    : trim(($agent->lastname ?? '').', '.($agent->name ?? ''), ', '),
                'email' => $agent->email,
                'phone' => $agent->phone ?? null,
                'is_assigned' => $isAssigned,
                'is_admin_assigned' => $isAssigned ? (bool) $assignment->is_admin_assigned : false,
                'agent_type' => 'User',
            ]);
        }

        foreach ($salesAltUsers as $agent) {
            $key = AltUser::class.'_'.$agent->id;
            $assignment = $assignedMap->get($key);
            $isAssigned = $assignment !== null;

            $allSellers->push([
                'id' => $agent->id,
                'name' => $agent->name,
                'lastname' => $agent->lastname ?? '',
                'full_name' => method_exists($agent, 'getFullNameAttribute') || isset($agent->fullName)
                    ? $agent->fullName
                    : trim(($agent->lastname ?? '').', '.($agent->name ?? ''), ', '),
                'email' => $agent->email,
                'phone' => $agent->phone ?? null,
                'is_assigned' => $isAssigned,
                'is_admin_assigned' => $isAssigned ? (bool) $assignment->is_admin_assigned : false,
                'agent_type' => 'AltUser',
            ]);
        }

        // Ordenar: primero los asignados (is_assigned: true), luego por full_name o nombre
        $sortedSellers = $allSellers->sortByDesc(fn ($seller) => $seller['is_assigned'] ? 1 : 0)
            ->values();

        return response()->json($sortedSellers, 200);
    }
}
