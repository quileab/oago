<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Auth;
use App\Models\Product;
use App\Models\Order;
use App\Models\OrderItem;

class Cart extends Component
{
    protected $listeners = ['addToCart'];
    public $cart = [];
    public $total = 0;

    public function mount()
    {
        // Inicializar el carrito si ya existe en la sesión
        $this->cart = Session::get('cart', []);
        $this->calculateTotal();
    }

    public function addToCart($productId)
    {
        $product = Product::find($productId, $byBulk = false);

        // Si el producto ya está en el carrito, aumentar la cantidad
        if (isset($this->cart[$productId])) {
            $this->cart[$productId]['quantity'] += $byBulk ? $product->qtty_package : 1;
        } else {
            // Si no, agregar el producto al carrito
            $this->cart[$productId] = [
                'product_id' => $product->id,
                'name' => $product->description,
                'price' => $product->offer_price,
                'quantity' => $byBulk ? $product->qtty_package : 1,
                'byBulk' => $byBulk,
            ];
        }

        // Guardar el carrito en la sesión
        Session::put('cart', $this->cart);
        $this->calculateTotal();
    }

    public function removeFromCart($productId)
    {
        if (isset($this->cart[$productId])) {
            unset($this->cart[$productId]);
            Session::put('cart', $this->cart);
            $this->calculateTotal();
        }
    }

    public function updateQuantity($productId, $quantity)
    {
        if (isset($this->cart[$productId])) {
            $this->cart[$productId]['quantity'] = $quantity;
            Session::put('cart', $this->cart);
            $this->calculateTotal();
        }
    }

    public function calculateTotal()
    {
        $this->total = 0;
        foreach ($this->cart as $item) {
            $this->total += $item['price'] * $item['quantity'];
        }
    }

    public function placeOrder()
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        // Crear la orden
        $order = Order::create([
            'user_id' => Auth::id(),
            'status' => 'pending',
            'total_price' => $this->total,
            'order_date' => now(),
        ]);

        // Agregar los productos a la orden
        foreach ($this->cart as $item) {
            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
                'price' => $item['price'],
            ]);
        }

        // Limpiar el carrito
        Session::forget('cart');
        $this->cart = [];
        $this->total = 0;

        // Redireccionar a una página de éxito
        return redirect()->route('order.success', ['order' => $order->id]);
    }

    public function render()
    {
        return view('livewire.cart');
    }

}
