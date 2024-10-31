<?php

namespace App\Livewire;

use App\Models\Order;
use Livewire\Component;
use App\Models\OrderItem;
use Livewire\Attributes\On;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Mary\Traits\Toast;

class Cart extends Component
{
    use Toast;
    public bool $showCart = false;
    public $cart = [];
    public $total = 0;

    public function mount() {
        // Inicializar el carrito si ya existe en la sesión
        $this->cart = Session::get('cart', []);
        $this->calculateTotal();
    }

    #[On('addToCart')]
    public function onAddToCart($product, $byBulk = false) {
        $this->addToCart($product, $byBulk);
        $this->info('Producto añadido');
    }

    public function addToCart($product, $byBulk = false)
    {
        $productId = $product['id'];

        // Si el producto ya está en el carrito, aumentar la cantidad
        if (isset($this->cart[$productId])) {
            $this->cart[$productId]['quantity'] += $byBulk ? $product['qtty_package'] : 1;
        } else {
            // Si no, agregar el producto al carrito
            $this->cart[$productId] = [
                'product_id' => $product['id'],
                'name' => $product['description'],
                'price' => $product['user_price'],
                'quantity' => $byBulk ? $product['qtty_package'] : 1,
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

        // Verificar si se está actualizando la orden
        if (Session::has('updateOrder')) {
            $orderId = Session::get('updateOrder');
            $order = Order::findOrFail($orderId);
        }else{  
            // Crear la orden
            $order = Order::create([
                'user_id' => Auth::id(),
                'status' => 'pending',
                'total_price' => $this->total,
                'order_date' => now(),
            ]);
        }

        // TODO: Improve this Update, Delete and Create

        $items = OrderItem::where('order_id', $order->id)->get();
        foreach ($items as $item) {
            $item->delete();
        }

        // Agregar, modificar o eliminar los productos de la orden 
        foreach ($this->cart as $item) {
            // update or create
            OrderItem::updateOrCreate([
                'order_id' => $order->id,
                'product_id' => $item['product_id'],
            ], [
                'quantity' => $item['quantity'],
                'price' => $item['price'],
            ]);

        }

        // Limpiar el carrito
        Session::forget('cart');
        Session::forget('updateOrder');
        $this->cart = [];
        $this->total = 0;

        // Redireccionar a una página de éxito
        return redirect()->route('ordersuccess', ['order' => $order->id]);
    }

    public function render()
    {
        return view('livewire.cart');
    }

    public function emptyCart() {    
        Session::forget('cart');
        Session::forget('updateOrder');
        $this->cart = [];
        $this->total = 0;
        $this->showCart = false;
        $this->info('Carrito vaciado');
    }

}
