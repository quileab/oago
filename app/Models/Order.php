<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Helpers\SettingsHelper;
use App\Mail\OrderMail;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;

/**
 * @property int $id
 * @property int $user_id
 * @property float $total_price
 * @property string|null $sending_method
 * @property OrderStatus $status
 * @property-read User $user
 * @property-read Collection<int, OrderItem> $items
 */
class Order extends Model
{
    // protected $fillable = ['status'];
    protected $fillable = [
        'user_id',
        'total_price',
        'sending_method',
        'sending_address',
        'sending_city',
        'contact_name',
        'contact_number',
        'transport_detail',
        'payment_method',
        'payment_detail',
        'information',
        'status',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function shipping(): HasOne
    {
        return $this->hasOne(ShippingDetail::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
        ];
    }

    public static function placeOrder($shipping = null)
    {
        if (empty(Session::get('cart'))) {
            return;
        }

        if (Session::has('processing_order')) {
            throw ValidationException::withMessages(['cart' => 'Su pedido ya está siendo procesado. Por favor espere.']);
        }

        Session::put('processing_order', true);

        try {
            // Verificar si se está actualizando la orden
            if (Session::has('updateOrder')) {
                $order = Order::findOrFail(Session::get('updateOrder'))->toArray();
            } else {
                $order = [];
            }

            // if status is not pending, it should be 'on-hold'
            if ($shipping['status'] && $shipping['status'] !== 'pending') {
                $shipping['status'] = 'on-hold';
            }

            $total = 0;
            $productIds = array_column(Session::get('cart', []), 'product_id');
            $products = Product::whereIn('id', $productIds)->get()->keyBy('id');

            foreach (Session::get('cart', []) as $item) {
                $product = $products->get($item['product_id']);

                if (! $product) {
                    throw ValidationException::withMessages(['cart' => "El producto {$item['name']} ya no está disponible."]);
                }

                // Validar Stock
                if ($product->stock < $item['quantity']) {
                    throw ValidationException::withMessages(['cart' => "Stock insuficiente para {$item['name']}. Disponible: {$product->stock}."]);
                }

                // Validar Precio (permitir pequeña diferencia por redondeo)
                $currentPrice = current_user()->getProductPrice($product);
                if (abs((float) $item['price'] - (float) $currentPrice) > 0.01) {
                    throw ValidationException::withMessages(['cart' => "El precio de {$item['name']} ha cambiado ($ ".number_format($currentPrice, 2).'). Por favor verifique su carrito.']);
                }

                $orderedQuantity = (int) $item['quantity'];
                $billableQuantity = $orderedQuantity;

                if ($product && $product->hasBonus() && $product->bonus_threshold > 0) {
                    $bonusThreshold = $product->bonus_threshold + $product->bonus_amount;
                    $timesBonusApplies = floor($orderedQuantity / $bonusThreshold);
                    $freeUnits = $timesBonusApplies * $product->bonus_amount;
                    $billableQuantity = $orderedQuantity - $freeUnits;
                }

                $total += (float) $item['price'] * $billableQuantity;
            }
            if ($total == 0) {
                return;
            }
            $shipping['total_price'] = $total;
            $shipping['user_id'] = current_user()->id;

            $orderCreated = DB::transaction(function () use ($order, $shipping) {
                // Crear la orden (Solo datos comerciales)
                $orderCreated = Order::updateOrCreate(
                    ['id' => $order['id'] ?? null],
                    [
                        'user_id' => $shipping['user_id'],
                        'total_price' => $shipping['total_price'],
                        'sending_method' => $shipping['sending_method'] ?? null,
                        'transport_detail' => $shipping['transport_detail'] ?? null,
                        'payment_method' => $shipping['payment_method'] ?? null,
                        'payment_detail' => $shipping['payment_detail'] ?? null,
                        'information' => $shipping['information'] ?? null,
                        'status' => $shipping['status'] ?? 'pending',
                    ]
                );

                // Guardar detalles de envío en la tabla shipping_details (Solo si no es el método predeterminado)
                if (($shipping['sending_method'] ?? '') !== 'Envío a cargo de la Empresa a Dirección Registrada') {
                    ShippingDetail::updateOrCreate(
                        ['order_id' => $orderCreated->id],
                        [
                            'contact_name' => $shipping['contact_name'] ?? null,
                            'address' => $shipping['sending_address'] ?? null,
                            'city' => $shipping['sending_city'] ?? null,
                            'postal_code' => current_user()?->postal_code, // Respaldo del perfil
                            'phone' => $shipping['contact_number'] ?? current_user()?->phone,
                            'shipping_status' => 'pending',
                        ]
                    );
                } else {
                    // Eliminar si existía
                    ShippingDetail::where('order_id', $orderCreated->id)->delete();
                }

                // Remove old items from the order OrderItem where order_id at once
                OrderItem::where('order_id', $orderCreated->id)->delete();

                // Add / Update items to the order
                foreach (Session::get('cart', []) as $item) {
                    if (($item['price'] * $item['quantity']) > 0) {
                        // update or create
                        OrderItem::updateOrCreate(
                            [
                                'order_id' => $orderCreated->id,
                                'product_id' => $item['product_id'],
                            ],
                            [
                                'quantity' => $item['quantity'],
                                'price' => $item['price'],
                            ]
                        );
                    }
                }

                return $orderCreated;
            });

            // Enviar correo de confirmación
            try {
                $adminEmail = SettingsHelper::settings('order_placed_mail');
                $userEmail = current_user()?->email;

                if ($userEmail) {
                    $mail = Mail::to($userEmail);
                    if ($adminEmail) {
                        $mail->cc($adminEmail);
                    }
                    $mail->send(new OrderMail($orderCreated->id));
                } elseif ($adminEmail) {
                    Mail::to($adminEmail)->send(new OrderMail($orderCreated->id));
                }
            } catch (\Exception $e) {
                Log::error('Error enviando correo de orden: '.$e->getMessage());
            }

            // Limpiar el carrito
            Session::forget('cart');
            Session::forget('updateOrder');
            unset($items, $order, $shipping, $data);

            // delete JSON cart
            $cartId = current_user_cart_id();
            if ($cartId && file_exists(storage_path('app/private/'.$cartId.'_cart.json'))) {
                unlink(storage_path('app/private/'.$cartId.'_cart.json'));
            }

            // Redireccionar a una página de éxito
            return redirect()->route('ordersuccess', ['order' => $orderCreated->id, 'status' => $orderCreated->status]);
        } finally {
            Session::forget('processing_order');
        }
    }

    public static function orderStates($state = null)
    {
        if ($state === null) {
            $statuses = [];
            foreach (OrderStatus::cases() as $status) {
                $statuses[$status->value] = $status->label();
            }

            return $statuses;
        }

        if ($state instanceof OrderStatus) {
            return $state->label();
        }

        return OrderStatus::from($state)->label();
    }
}
