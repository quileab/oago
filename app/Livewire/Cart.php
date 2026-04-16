<?php

namespace App\Livewire;

use App\Models\Order;
use Livewire\Component;
use Livewire\Attributes\On;
use App\Models\Product;
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
    public function onAddToCart($product, int $quantity = 1): void
    {
        // if auth user role is guest or nor logged return
        if (Auth::guest() || Auth::user()->role->value === 'guest') {
            return;
        }

        // Si recibimos solo el ID, buscar el producto
        if (is_numeric($product)) {
            $productModel = Product::find($product);
            if (!$productModel) return;
            
            // Adaptar al formato esperado por el resto de la función
            $product = [
                'id' => $productModel->id,
                'description' => $productModel->description,
                'user_price' => current_user()->getProductPrice($productModel),
                'qtty_package' => $productModel->qtty_package,
            ];
        }

        $productId = $product['id'];
        $cart = Session::get('cart', []);
        
        // Ensure qtty_package is at least 1 to avoid division by zero
        $qttyPackage = max(1, $product['qtty_package'] ?? 1);

        // If the product is already in the cart, increase the quantity
        if (isset($cart[$productId])) {
            $cart[$productId]['quantity'] += $quantity;
        } else {
            // If not, add the product to the cart
            $cart[$productId] = [
                'product_id' => $product['id'],
                'name' => $product['description'],
                'price' => $product['user_price'],
                'bulkQuantity' => $qttyPackage,
                'quantity' => $quantity,
            ];
        }

        // Determine if the product is being purchased by bulk
        $cart[$productId]['byBulk'] =
            $cart[$productId]['quantity'] % $qttyPackage == 0;

        // Save the cart to the session
        Session::put('cart', $cart);
        
        // Recalculate the total
        $this->calculateTotal();
        
        // Informar al usuario que el producto se ha agregado al carrito
        $this->dispatch('cart-updated');
    }

    public function removeFromCart($productId)
    {
        // Eliminar el producto del carrito
        $cart = Session::get('cart', []);
        if (isset($cart[$productId])) {
            unset($cart[$productId]);
            Session::put('cart', $cart);
            $this->calculateTotal();
        }
    }

    public function updateQuantity($productId, $quantity)
    {
        // Actualizar la cantidad del producto en el carrito
        $cart = Session::get('cart', []);
        if (isset($cart[$productId])) {
            $cart[$productId]['quantity'] = (int) $quantity;
            Session::put('cart', $cart);
            $this->calculateTotal();
        }
    }

    public function calculateTotal()
    {
        // Recalcular el total del carrito
        $cart = Session::get('cart', []);
        $this->total = 0;
        foreach ($cart as $item) {
            $product = Product::find($item['product_id']);

            $orderedQuantity = (int)$item['quantity'];
            $billableQuantity = $orderedQuantity;

            // Bonus logic: If the threshold is met, the bonus_amount units are free.
            // Current logic was ADDING them to the total to pay, which is wrong.
            // We should keep the ordered quantity and NOT charge for the bonus units.
            if ($product && $product->hasBonus() && $product->bonus_threshold > 0) {
                $timesBonusApplies = floor($orderedQuantity / ($product->bonus_threshold + $product->bonus_amount));
                // The user gets ($timesBonusApplies * $product->bonus_amount) units for free.
                $freeUnits = $timesBonusApplies * $product->bonus_amount;
                $billableQuantity = $orderedQuantity - $freeUnits;
            }

            $this->total += (float)$item['price'] * $billableQuantity;
        }
    }

    public function render()
    {
        $cart = Session::get('cart', []);
        $enrichedCart = [];

        foreach ($cart as $productId => $item) {
            $product = Product::find($productId);
            if ($product) {
                $item['product_model'] = $product;
            }
            $enrichedCart[$productId] = $item;
        }

        return view('livewire.cart', [
            'cart' => $enrichedCart,
        ]);
    }

    public function emptyCart()
    {
        Session::forget('cart');
        Session::forget('updateOrder');
        $this->total = 0;
        $this->showCart = false;
        $this->info('Carrito vacío');
    }

    public function saveCart()
    {
        Order::placeOrder([
            'status' => 'on-hold',
        ]);
    }
}
