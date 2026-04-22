<?php

namespace App\Models;

use App\Helpers\SettingsHelper;
use App\Mail\OrderMail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;

class AltOrder extends Model
{
    protected $table = 'alt_orders';

    protected $fillable = [
        'alt_user_id',
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
        return $this->belongsTo(AltUser::class, 'alt_user_id');
    }

    public function items()
    {
        return $this->hasMany(AltOrderItem::class, 'alt_order_id');
    }

    public function shipping()
    {
        return $this->hasOne(ShippingDetail::class, 'alt_order_id');
    }

    public static function orderStates($state = null)
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

        if ($state === null) {
            return $statuses;
        }

        return $statuses[$state] ?? $state;
    }

    public static function placeOrder($shipping = null)
    {
        // Verificar si se está actualizando la orden (opcional para alt orders por ahora)
        if (Session::has('updateOrder')) {
            $order = AltOrder::find(Session::get('updateOrder'))?->toArray() ?? [];
        } else {
            $order = [];
        }

        // if status is not pending, it should be 'on-hold'
        if (isset($shipping['status']) && $shipping['status'] !== 'pending') {
            $shipping['status'] = 'on-hold';
        }

        $total = 0;
        foreach (Session::get('cart', []) as $item) {
            $total += $item['price'] * $item['quantity'];
        }

        if ($total == 0) {
            return;
        }

        $shipping['total_price'] = $total;
        $shipping['alt_user_id'] = Auth::guard('alt')->id() ?? Auth::id(); // Usar el guard correcto

        // Crear la orden alternativa (Solo datos comerciales)
        $orderCreated = AltOrder::updateOrCreate(
            ['id' => $order['id'] ?? null],
            [
                'alt_user_id' => $shipping['alt_user_id'],
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
                ['alt_order_id' => $orderCreated->id],
                [
                    'contact_name' => $shipping['contact_name'] ?? null,
                    'address' => $shipping['sending_address'] ?? null,
                    'city' => $shipping['sending_city'] ?? null,
                    'postal_code' => $shipping['postal_code'] ?? current_user()?->postal_code, // Respaldo del perfil
                    'phone' => $shipping['contact_number'] ?? current_user()?->phone,
                    'shipping_status' => 'pending',
                ]
            );
        } else {
            // Eliminar si existía de un intento previo con otro método
            ShippingDetail::where('alt_order_id', $orderCreated->id)->delete();
        }

        // Remove old items
        AltOrderItem::where('alt_order_id', $orderCreated->id)->delete();

        // Add items
        foreach (Session::get('cart', []) as $item) {
            if (($item['price'] * $item['quantity']) > 0) {
                AltOrderItem::create([
                    'alt_order_id' => $orderCreated->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                ]);
            }
        }

        // Enviar correo (opcional, replicamos para que sea una copia)
        try {
            $adminEmail = SettingsHelper::settings('order_placed_mail');
            $userEmail = current_user()?->email;

            if ($userEmail) {
                $mail = Mail::to($userEmail);
                if ($adminEmail) {
                    $mail->cc($adminEmail);
                }
                // Nota: Usamos el mismo OrderMail por ahora, podría requerir ajustes si usa Order vs AltOrder
                $mail->send(new OrderMail($orderCreated->id, true)); // true = is_alt
            }
        } catch (\Exception $e) {
            Log::error('Error enviando correo de orden alternativa: '.$e->getMessage());
        }

        // Limpiar el carrito
        Session::forget('cart');
        Session::forget('updateOrder');

        // delete JSON cart
        if (file_exists(storage_path('app/private/'.Auth::id().'_cart.json'))) {
            unlink(storage_path('app/private/'.Auth::id().'_cart.json'));
        }

        return redirect()->route('ordersuccess', ['order' => $orderCreated->id, 'status' => $orderCreated->status, 'is_alt' => true]);
    }
}
