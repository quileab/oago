<?php

namespace App\Livewire;

use App\Models\Order;
use Livewire\Component;
use Livewire\Attributes\On;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Mary\Traits\Toast;

class Cart extends Component
{
    use Toast;
    public bool $showCart = false;
    public $total = 0;

    public function mount()
    {
        // Inicializar el carrito si ya existe en la sesión
        $this->calculateTotal();
    }

    #[On('addToCart')]
    public function onAddToCart(array $product, int $quantity = 1): void
    {
        $productId = $product['id'];
        $cart = Session::get('cart', []);
        // If the product is already in the cart, increase the quantity
        if (isset($cart[$productId])) {
            $cart[$productId]['quantity'] += $quantity;
        } else {
            // If not, add the product to the cart
            $cart[$productId] = [
                'product_id' => $product['id'],
                'name' => $product['description'],
                'price' => $product['user_price'],
                'bulkQuantity' => $product['qtty_package'],
                'quantity' => $quantity,
            ];
        }

        // Determine if the product is being purchased by bulk
        $cart[$productId]['byBulk'] =
            $cart[$productId]['quantity'] % $product['qtty_package'] == 0;
        // Save the cart to the session
        Session::put('cart', $cart);
        // Recalculate the total
        $this->calculateTotal();
        // save temporarily the cart to JSON using User-id in storage as filename user_id_cart.json.json
        $this->jsonCartUpdate();
        $this->info('Agregado', icon: 'o-shopping-cart', position: 'bottom-end', timeout: 1000);
    }

    public function removeFromCart($productId)
    {
        // Eliminar el producto del carrito
        $cart = Session::get('cart', []);
        if (isset($cart[$productId])) {
            unset($cart[$productId]);
            Session::put('cart', $cart);
            $this->calculateTotal();
            $this->jsonCartUpdate();
        }
    }

    public function updateQuantity($productId, $quantity)
    {
        // Actualizar la cantidad del producto en el carrito
        $cart = Session::get('cart', []);
        if (isset($cart[$productId])) {
            $cart[$productId]['quantity'] = $quantity;
            Session::put('cart', $cart);
            $this->calculateTotal();
            $this->jsonCartUpdate();
        }
    }

    public function calculateTotal()
    {
        // Recalcular el total del carrito
        $cart = Session::get('cart', []);
        $this->total = 0;
        foreach ($cart as $item) {
            $this->total += $item['price'] * $item['quantity'];
        }
    }

    public function render()
    {
        return view('livewire.cart', [
            'cart' => Session::get('cart', []),
        ]);
    }

    public function emptyCart()
    {
        Session::forget('cart');
        Session::forget('updateOrder');
        $this->total = 0;
        $this->showCart = false;
        $this->info('Carrito vacío');
        /// delete JSON cart
        if (file_exists(storage_path('app/private/' . Auth::id() . '_cart.json'))) {
            unlink(storage_path('app/private/' . Auth::id() . '_cart.json'));
        }
    }

    public function jsonCartUpdate()
    {
        // save session cart to json
        $jsonCart = json_encode(Session::get('cart'));
        file_put_contents(storage_path('app/private/' . Auth::id() . '_cart.json'), $jsonCart);
    }

    public function saveCart()
    {
        Order::placeOrder([
            'status' => 'on-hold',
        ]);
    }
}
