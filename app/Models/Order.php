<?php

namespace App\Models;

use App\Mail\OrderMail;
use App\Models\OrderItem;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Session;

class Order extends Model
{
    //protected $fillable = ['status'];
    protected $guarded = [];
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
        $shipping['user_id'] = Auth::id();

        $data = array_merge($order, $shipping);
        // Crear la orden
        $orderCreated = Order::updateOrCreate(
            ['id' => $order['id'] ?? null],
            $data
        );

        // Remove old items from the order OrderItem where order_id at once
        OrderItem::where('order_id', $orderCreated->id)->delete();

        // Add / Update items to the order 
        foreach (Session::get('cart', []) as $item) {
            // update or create
            OrderItem::updateOrCreate([
                'order_id' => $orderCreated->id,
                'product_id' => $item['product_id'],
            ], [
                'quantity' => $item['quantity'],
                'price' => $item['price'],
            ]);
        }

        // Enviar correo de confirmación
        //Mail::to('' . Auth::user()->email)->send(new OrderMail($orderCreated));
        Mail::to('gmenaker@oagostini.com.ar')->send(new OrderMail($orderCreated->id));

        // Limpiar el carrito
        Session::forget('cart');
        Session::forget('updateOrder');
        unset($items, $order, $shipping, $data);

        // delete JSON cart
        if (file_exists(storage_path('app/private/' . Auth::id() . '_cart.json'))) {
            unlink(storage_path('app/private/' . Auth::id() . '_cart.json'));
        }
        // Redireccionar a una página de éxito
        return redirect()->route('ordersuccess', ['order' => $orderCreated->id]);
    }
}
