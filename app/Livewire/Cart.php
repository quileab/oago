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
        $this->calculateTotal();
    }

    #[On('addToCart')]
    public function onAddToCart($product, int $quantity = 1): void
    {
        if (Auth::guest() || Auth::user()->role->value === 'guest') {
            $this->warning('Debe iniciar sesión para comprar');
            return;
        }

        if (is_numeric($product)) {
            $productModel = Product::find($product);
            if (!$productModel) return;
            
            $product = [
                'id' => $productModel->id,
                'description' => $productModel->description,
                'user_price' => current_user()->getProductPrice($productModel),
                'qtty_package' => $productModel->qtty_package,
            ];
        }

        $productId = $product['id'];
        $cart = Session::get('cart', []);
        $qttyPackage = max(1, $product['qtty_package'] ?? 1);

        if (isset($cart[$productId])) {
            $cart[$productId]['quantity'] += $quantity;
        } else {
            $cart[$productId] = [
                'product_id' => $productId,
                'name' => $product['description'],
                'price' => $product['user_price'],
                'bulkQuantity' => $qttyPackage,
                'quantity' => $quantity,
            ];
        }

        $this->updateCartAndNotify($cart);
    }

    public function removeFromCart($productId)
    {
        $cart = Session::get('cart', []);
        if (isset($cart[$productId])) {
            unset($cart[$productId]);
            $this->updateCartAndNotify($cart);
        }
    }

    public function updateQuantity($productId, $quantity)
    {
        $cart = Session::get('cart', []);
        if (isset($cart[$productId])) {
            $cart[$productId]['quantity'] = max(1, (int)$quantity);
            $this->updateCartAndNotify($cart);
        }
    }

    private function updateCartAndNotify($cart)
    {
        foreach ($cart as $id => $item) {
            $cart[$id]['byBulk'] = ($item['quantity'] % ($item['bulkQuantity'] ?? 1)) == 0;
        }

        Session::put('cart', $cart);
        $this->calculateTotal();
        
        // Notificar cambios para que la UI reaccione
        $this->dispatch('cart-updated');

        // Guardar temporalmente el carrito en un JSON
        $this->jsonCartUpdate();
    }

    public function calculateTotal()
    {
        $cart = Session::get('cart', []);
        $this->total = 0;
        foreach ($cart as $item) {
            $product = Product::find($item['product_id']);
            $orderedQuantity = (int)$item['quantity'];
            $billableQuantity = $orderedQuantity;

            if ($product && $product->hasBonus() && $product->bonus_threshold > 0) {
                $bonusThreshold = $product->bonus_threshold + $product->bonus_amount;
                $timesBonusApplies = floor($orderedQuantity / $bonusThreshold);
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
        $this->dispatch('cart-updated');
        $this->info('Carrito vacío');
        $this->jsonCartDelete();
    }

    public function jsonCartUpdate()
    {
        if (Auth::check() || Auth::guard('alt')->check()) {
            $jsonCart = json_encode(Session::get('cart'));
            file_put_contents(storage_path('app/private/' . Auth::id() . '_cart.json'), $jsonCart);
        }
    }

    public function jsonCartDelete()
    {
        if (file_exists(storage_path('app/private/' . Auth::id() . '_cart.json'))) {
            unlink(storage_path('app/private/' . Auth::id() . '_cart.json'));
        }
    }

    public function saveCart()
    {
        Order::placeOrder(['status' => 'on-hold']);
    }
}
