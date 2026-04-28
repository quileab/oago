<?php

namespace App\Models;

use App\Helpers\SettingsHelper;
use App\Mail\OrderMail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;

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

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function shipping()
    {
        return $this->hasOne(ShippingDetail::class);
    }

    public static function placeOrder($shipping = null)
    {
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
        foreach (Session::get('cart', []) as $item) {
            $total += $item['price'] * $item['quantity'];
        }
        if ($total == 0) {
            // debido a un error intenta guardar 2 veces el mismo pedido
            // esto evita la duplicación
            return;
        }
        $shipping['total_price'] = $total;
        $shipping['user_id'] = current_user()->id;

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
        if (file_exists(storage_path('app/private/'.Auth::id().'_cart.json'))) {
            unlink(storage_path('app/private/'.Auth::id().'_cart.json'));
        }

        // Redireccionar a una página de éxito
        return redirect()->route('ordersuccess', ['order' => $orderCreated->id, 'status' => $orderCreated->status]);
    }

    public static function orderStates($state)
    {
        $statuses = [
            'pending' => 'Pendiente',
            'on-hold' => 'En espera',
            'processing' => 'Procesando',
            'completed' => 'Completado',
            'cancelled' => 'Cancelado',
            'refunded' => 'Reembolsado',
            'failed' => 'Fallido',
        ];

        // return translation of order status from array
        return $statuses[$state];
    }
}
