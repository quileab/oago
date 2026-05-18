<?php

namespace App\Services;

use App\Models\AltOrder;
use App\Models\AltOrderItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ShippingDetail;
use App\Helpers\SettingsHelper;
use App\Mail\OrderMail;
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
     */
    public function placeOrder(array $shippingData): \Illuminate\Http\RedirectResponse
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
            
            // Determinar si es actualización
            $updateOrderId = Session::get('updateOrder');
            
            // Validar stock, precios y calcular total
            $validatedData = $this->validateCart($cart, $user);
            $total = $validatedData['total'];
            $products = $validatedData['products'];

            $shippingData['total_price'] = $total;
            $shippingData['user_id'] = $user->id;
            
            // Normalizar estado
            if (isset($shippingData['status']) && $shippingData['status'] !== 'pending') {
                $shippingData['status'] = 'on-hold';
            }

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
                        'status' => $shippingData['status'] ?? 'pending',
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
                
                if (!empty($itemsToInsert)) {
                    $itemModel::insert($itemsToInsert);
                }

                return $order;
            });

            // 4. Notificaciones
            $this->sendOrderNotification($orderCreated, $isAlt);

            // 5. Cleanup
            $this->cleanupSessionAndStorage();

            return redirect()->route('ordersuccess', [
                'order' => $orderCreated->id, 
                'status' => $orderCreated->status, 
                'is_alt' => $isAlt
            ]);

        } finally {
            Session::forget('processing_order');
        }
    }

    /**
     * Valida el stock y los precios de los productos en el carrito.
     */
    protected function validateCart(array $cart, $user): array
    {
        $total = 0;
        $productIds = array_column($cart, 'product_id');
        $products = Product::whereIn('id', $productIds)->get()->keyBy('id');

        foreach ($cart as $item) {
            $product = $products->get($item['product_id']);

            if (!$product) {
                throw ValidationException::withMessages(['cart' => "El producto {$item['name']} ya no está disponible."]);
            }

            if ($product->stock < $item['quantity']) {
                throw ValidationException::withMessages(['cart' => "Stock insuficiente para {$item['name']}. Disponible: {$product->stock}."]);
            }

            $currentPrice = $user->getProductPrice($product);
            if (abs((float) $item['price'] - (float) $currentPrice) > 0.01) {
                throw ValidationException::withMessages(['cart' => "El precio de {$item['name']} ha cambiado ($ " . number_format($currentPrice, 2) . "). Por favor verifique su carrito."]);
            }

            $orderedQuantity = (int) $item['quantity'];
            $billableQuantity = $orderedQuantity;

            if ($product->hasBonus()) {
                $bonusThreshold = $product->bonus_threshold + $product->bonus_amount;
                $timesBonusApplies = floor($orderedQuantity / $bonusThreshold);
                $freeUnits = $timesBonusApplies * $product->bonus_amount;
                $billableQuantity = $orderedQuantity - $freeUnits;
            }

            $total += (float) $item['price'] * $billableQuantity;
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
            Log::error("Error enviando correo de orden (" . ($isAlt ? 'Alt' : 'Normal') . "): " . $e->getMessage());
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
            $path = storage_path('app/private/' . $cartId . '_cart.json');
            if (file_exists($path)) {
                unlink($path);
            }
        }
    }
}
