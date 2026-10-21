<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Helpers\SettingsHelper;
use App\Mail\OrderMail;
use App\Models\AltOrder;
use App\Models\AltOrderItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ShippingDetail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;

class OrderService
{
    /**
     * Procesa la creación o actualización de un pedido.
     *
     * El estado se resuelve explícitamente: al crear se usa el pedido; al
     * actualizar se conserva el del pedido existente (salvo que sea un
     * borrador `saved`, que pasa a `pending` al confirmarse).
     *
     * @param  array<string, mixed>  $shippingData
     * @return RedirectResponse|Order|AltOrder
     */
    public function placeOrder(array $shippingData, bool $redirect = true)
    {
        $cart = Session::get('cart', []);
        if (empty($cart)) {
            throw ValidationException::withMessages(['cart' => 'El carrito está vacío.']);
        }

        if (Session::has('processing_order')) {
            throw ValidationException::withMessages(['cart' => 'Su pedido ya está siendo procesado. Por favor espere.']);
        }

        Session::put('processing_order', true);

        try {
            $isAlt = Auth::guard('alt')->check();
            $user = current_user();

            $updateOrderId = Session::get('updateOrder');
            $orderModel = $isAlt ? AltOrder::class : Order::class;
            $existingOrder = $updateOrderId ? $orderModel::find($updateOrderId) : null;

            if ($existingOrder) {
                $this->ensureOrderBelongsToUser($existingOrder, $isAlt);
            }

            $requestedStatus = $this->resolveStatus($shippingData['status'] ?? null);
            $isDraft = $requestedStatus === OrderStatus::SAVED;

            // Un borrador no exige stock: es solo un carrito aparcado.
            $validatedData = $this->validateCart($cart, $user, enforceStock: ! $isDraft);
            $total = $validatedData['total'];

            $status = $this->resolveStatusForPersistence($existingOrder, $requestedStatus);

            $shippingData['total_price'] = $total;
            $shippingData['user_id'] = $user->id;
            $shippingData['status'] = $status->value;

            $orderCreated = DB::transaction(function () use ($isAlt, $updateOrderId, $shippingData, $cart) {
                // 1. Crear/Actualizar la Orden
                $orderModel = $isAlt ? AltOrder::class : Order::class;
                $userIdField = $isAlt ? 'alt_user_id' : 'user_id';

                $order = $orderModel::updateOrCreate(
                    ['id' => $updateOrderId],
                    [
                        $userIdField => $shippingData['user_id'],
                        'total_price' => $shippingData['total_price'],
                        'sending_method' => $shippingData['sending_method'] ?? null,
                        'transport_detail' => $shippingData['transport_detail'] ?? null,
                        'payment_method' => $shippingData['payment_method'] ?? null,
                        'payment_detail' => $shippingData['payment_detail'] ?? null,
                        'information' => $shippingData['information'] ?? null,
                        'status' => $shippingData['status'],
                    ]
                );

                // 2. Detalles de Envío
                $this->handleShippingDetails($order, $shippingData, $isAlt);

                // 3. Items del Pedido (Bulk Delete and Insert)
                $itemModel = $isAlt ? AltOrderItem::class : OrderItem::class;
                $orderIdField = $isAlt ? 'alt_order_id' : 'order_id';

                $itemModel::where($orderIdField, $order->id)->delete();

                $itemsToInsert = [];
                foreach ($cart as $item) {
                    if (($item['price'] * $item['quantity']) > 0) {
                        $itemsToInsert[] = [
                            $orderIdField => $order->id,
                            'product_id' => $item['product_id'],
                            'quantity' => $item['quantity'],
                            'price' => $item['price'],
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }
                }

                if (! empty($itemsToInsert)) {
                    $itemModel::insert($itemsToInsert);
                }

                return $order;
            });

            // 4. Notificaciones: nunca para borradores ni para ediciones que conservan estado.
            if ($this->shouldNotify($existingOrder, $status)) {
                $this->sendOrderNotification($orderCreated, $isAlt);
            }

            // 5. Cleanup: el carrito se vacía también al guardar para después.
            $this->cleanupSessionAndStorage();

            $response = redirect()->route('ordersuccess', [
                'order' => $orderCreated->id,
                'status' => $orderCreated->status,
                'is_alt' => $isAlt,
            ]);

            return $redirect ? $response : $orderCreated;

        } finally {
            Session::forget('processing_order');
        }
    }

    /**
     * Normaliza el estado pedido. Ya no convierte silenciosamente a "on-hold".
     */
    protected function resolveStatus(OrderStatus|string|null $status): OrderStatus
    {
        if ($status instanceof OrderStatus) {
            return $status;
        }

        if ($status === null || $status === '') {
            return OrderStatus::PENDING;
        }

        $resolved = OrderStatus::tryFrom($status);

        if ($resolved === null) {
            throw ValidationException::withMessages([
                'status' => "Estado de pedido inválido: {$status}.",
            ]);
        }

        return $resolved;
    }

    /**
     * Determina el estado que se persiste al crear o actualizar.
     */
    protected function resolveStatusForPersistence(Order|AltOrder|null $existingOrder, OrderStatus $requested): OrderStatus
    {
        if ($existingOrder === null) {
            return $requested;
        }

        // Un borrador confirmado desde el checkout pasa a pendiente.
        if ($existingOrder->status === OrderStatus::SAVED) {
            return $requested === OrderStatus::SAVED ? OrderStatus::SAVED : OrderStatus::PENDING;
        }

        // Cualquier otro pedido conserva su estado (no se pisa el del admin).
        return $existingOrder->status;
    }

    /**
     * Solo se notifica al crear un pedido real o al confirmar un borrador.
     */
    protected function shouldNotify(Order|AltOrder|null $existingOrder, OrderStatus $status): bool
    {
        if ($status === OrderStatus::SAVED) {
            return false;
        }

        if ($existingOrder === null) {
            return true;
        }

        return $existingOrder->status === OrderStatus::SAVED;
    }

    /**
     * Evita que un usuario modifique el pedido de otro vía el id en sesión.
     */
    protected function ensureOrderBelongsToUser(Order|AltOrder $order, bool $isAlt): void
    {
        $isAdmin = real_user()?->role?->value === 'admin';
        $ownerId = $isAlt ? $order->alt_user_id : $order->user_id;

        if (! $isAdmin && (int) $ownerId !== (int) current_user()?->id) {
            throw ValidationException::withMessages(['cart' => 'No tiene permiso para modificar este pedido.']);
        }
    }

    /**
     * Valida el stock y los precios de los productos del carrito.
     *
     * @param  array<int, array<string, mixed>>  $cart
     * @return array{total: float, products: Collection}
     */
    protected function validateCart(array $cart, $user, bool $enforceStock = true): array
    {
        $total = 0;
        $productIds = array_column($cart, 'product_id');
        $products = Product::whereIn('id', $productIds)->get()->keyBy('id');

        foreach ($cart as $item) {
            $product = $products->get($item['product_id']);

            if (! $product) {
                throw ValidationException::withMessages(['cart' => "El producto {$item['name']} ya no está disponible."]);
            }

            if ($enforceStock && $product->stock < $item['quantity']) {
                throw ValidationException::withMessages(['cart' => "Stock insuficiente para {$item['name']}. Disponible: {$product->stock}."]);
            }

            $currentPrice = $user->getProductPrice($product);
            if (abs((float) $item['price'] - (float) $currentPrice) > 0.01) {
                throw ValidationException::withMessages(['cart' => "El precio de {$item['name']} ha cambiado ($ ".number_format($currentPrice, 2).'). Por favor verifique su carrito.']);
            }

            $orderedQuantity = (int) $item['quantity'];
            $billableQuantity = app(PriceListService::class)->calculateBillableQuantity($product, $orderedQuantity);

            $total += app(PriceListService::class)
                ->calculateItemPrice($user->list_id ?? 1, $product, $billableQuantity);
        }

        if ($total == 0) {
            throw ValidationException::withMessages(['cart' => 'El total del pedido no puede ser 0.']);
        }

        return ['total' => $total, 'products' => $products];
    }

    /**
     * Maneja la persistencia de los detalles de envío.
     */
    protected function handleShippingDetails($order, array $shippingData, bool $isAlt): void
    {
        $orderIdField = $isAlt ? 'alt_order_id' : 'order_id';
        $defaultMethod = 'Envío a cargo de la Empresa a Dirección Registrada';

        if (($shippingData['sending_method'] ?? '') !== $defaultMethod) {
            ShippingDetail::updateOrCreate(
                [$orderIdField => $order->id],
                [
                    'contact_name' => $shippingData['contact_name'] ?? null,
                    'address' => $shippingData['sending_address'] ?? null,
                    'city' => $shippingData['sending_city'] ?? null,
                    'postal_code' => $shippingData['postal_code'] ?? current_user()?->postal_code,
                    'phone' => $shippingData['contact_number'] ?? current_user()?->phone,
                    'shipping_status' => 'pending',
                ]
            );
        } else {
            ShippingDetail::where($orderIdField, $order->id)->delete();
        }
    }

    /**
     * Envía las notificaciones de correo electrónico.
     */
    protected function sendOrderNotification($order, bool $isAlt): void
    {
        try {
            $adminEmail = SettingsHelper::settings('order_placed_mail');
            $userEmail = current_user()?->email;

            if ($userEmail) {
                $mail = Mail::to($userEmail);
                if ($adminEmail) {
                    $mail->cc($adminEmail);
                }
                $mail->send(new OrderMail($order->id, $isAlt));
            } elseif ($adminEmail) {
                Mail::to($adminEmail)->send(new OrderMail($order->id, $isAlt));
            }
        } catch (\Exception $e) {
            Log::error('Error enviando correo de orden ('.($isAlt ? 'Alt' : 'Normal').'): '.$e->getMessage());
        }
    }

    /**
     * Limpia la sesión y los archivos temporales después de un pedido exitoso.
     */
    protected function cleanupSessionAndStorage(): void
    {
        Session::forget('cart');
        Session::forget('updateOrder');

        $cartId = current_user_cart_id();
        if ($cartId) {
            $path = storage_path('app/private/'.$cartId.'_cart.json');
            if (file_exists($path)) {
                unlink($path);
            }
        }
    }
}
