<?php

namespace App\Http\Controllers\Api;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Services\PriceListService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    /**
     * Listar pedidos pendientes.
     *
     * @return JsonResponse<array<int, mixed>>
     */
    public function listPendingOrders(Request $request): JsonResponse
    {
        $orders = Order::with(['user', 'items.product', 'shipping'])
            ->where('status', OrderStatus::PENDING)
            ->get();

        $orders->each(function ($order) {
            $order->setRelation('items', $order->items->filter(function ($item) {
                return $item->quantity > 0 && $item->price > 0;
            })->values());
        });

        return response()->json($orders, 200);
    }

    /**
     * Actualizar el estado de un pedido.
     *
     * @return array{message: string}
     */
    public function updateOrderStatus(Request $request, Order $order): JsonResponse
    {
        $request->validate([
            'status' => ['required', Rule::enum(OrderStatus::class)],
        ]);

        $target = OrderStatus::from($request->status);
        if ($target !== $order->status && ! $order->status->canTransitionTo($target)) {
            return response()->json([
                'message' => "Transición inválida: {$order->status->value} → {$target->value}.",
            ], 422);
        }

        $order->update(['status' => $target]);

        return response()->json([
            'message' => 'Estado del pedido '.$order->id.' => '.$request->status.': OK',
        ], 200);
    }

    /**
     * Actualizar los productos en un pedido.
     */
    public function updateOrderProducts(Request $request, Order $order): JsonResponse
    {
        Log::info("Request: { json_encode($request) }");

        // Normalizar formato legacy ('products' con 'id') a formato moderno ('items' con 'product_id')
        if ($request->has('products')) {
            $normalizedItems = [];
            foreach ($request->input('products', []) as $prod) {
                // Legacy usaba quantity=0 para indicar eliminación del producto.
                // En el flujo nuevo donde borramos todo y recreamos los ítems enviados,
                // omitimos los ítems con cantidad <= 0 para lograr la eliminación.
                if (isset($prod['quantity']) && (int) $prod['quantity'] > 0) {
                    $normalizedItems[] = [
                        'product_id' => $prod['id'] ?? null,
                        'quantity' => $prod['quantity'],
                        'price' => $prod['price'] ?? 0,
                    ];
                }
            }
            $request->merge(['items' => $normalizedItems]);
        }

        try {
            $request->validate([
                'items' => 'required|array',
                'items.*.product_id' => 'required|exists:products,id',
                'items.*.quantity' => 'required|integer|min:1',
                'items.*.price' => 'required|numeric|min:0',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error de validación: '.$e->getMessage(),
            ], 400);
        }

        $finishedStatuses = [OrderStatus::COMPLETED, OrderStatus::CANCELLED, OrderStatus::REFUNDED, OrderStatus::FAILED];
        if (in_array($order->status, $finishedStatuses, true)) {
            return response()->json([
                'message' => 'No se puede modificar un pedido ya finalizado.',
            ], 422);
        }

        try {
            DB::transaction(function () use ($request, $order) {
                $order->items()->delete();

                $total = 0;
                $productIds = array_column($request->items, 'product_id');
                $products = Product::whereIn('id', $productIds)->get()->keyBy('id');
                $orderUser = $order->user;

                if (! $orderUser) {
                    throw new \DomainException('El pedido no tiene un cliente asociado.');
                }

                foreach ($request->items as $item) {
                    $product = $products->get($item['product_id']);
                    $orderedQuantity = (int) $item['quantity'];

                    if (! $product) {
                        throw new \DomainException("El producto ID {$item['product_id']} ya no está disponible.");
                    }

                    $listId = app(PriceListService::class)->resolveListId($orderUser);

                    // Precio 0 = producto sin precio: no se vende.
                    if (! app(PriceListService::class)->isSellable($listId, $product)) {
                        throw new \DomainException("El producto {$product->description} no está disponible para la venta.");
                    }

                    $line = app(PriceListService::class)->calculateLine($listId, $product, $orderedQuantity);
                    $order->items()->create([
                        'product_id' => $item['product_id'],
                        'quantity' => $orderedQuantity,
                        'price' => $line['unit_price'],
                    ]);
                    $total += $line['subtotal'];
                }

                $order->update(['total_price' => $total]);
            });

            return response()->json([
                'message' => 'Productos del pedido '.$order->id.' actualizados: OK',
                'order' => $order->load('items.product'),
            ], 200);
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            Log::error('Error actualizando productos de orden API: '.$e->getMessage());

            return response()->json([
                'message' => 'Error interno al actualizar el pedido.',
            ], 500);
        }
    }
}
