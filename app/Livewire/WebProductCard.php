<?php

namespace App\Livewire;

use Livewire\Component;
use Mary\Traits\Toast;
use Livewire\Attributes\On;
use App\Models\Product;

class WebProductCard extends Component
{
    use Toast;
    public array $local_product;
    public $qtty = 1;
    public $user_price = 0;
    public $offer_price = 0;

    #[On('cart-updated')]
    public function syncQuantity()
    {
        $cart = session()->get('cart', []);
        if (isset($cart[$this->local_product['id']])) {
            $this->qtty = $cart[$this->local_product['id']]['quantity'];
        }
    }

    public function mount($product)
    {
        if ($product instanceof \Illuminate\Database\Eloquent\Model) {
            $this->local_product = $product->toArray();
            if (isset($product->description_html)) {
                $this->local_product['description_html'] = $product->description_html;
            }
        } else {
            $this->local_product = (array) $product;
        }
        
        $this->user_price = $this->local_product['user_price'] ?? current_user()?->getProductPrice(Product::find($this->local_product['id'])) ?? 0;
        $this->offer_price = $this->local_product['offer_price'] ?? 0;

        $cart = session()->get('cart', []);
        $this->qtty = $cart[$this->local_product['id']]['quantity'] ?? ($this->local_product['qtty_package'] ?? 1);
    }

    public function render()
    {
        return view('livewire.web-product-card', [
            'product' => (object) $this->local_product,
            'display_price' => $this->user_price,
            'display_offer' => $this->offer_price
        ]);
    }

    public function decrementQtty()
    {
        $step = $this->local_product['qtty_package'] ?? 1;
        if ($this->qtty > 1) {
            $this->qtty--;
        }
    }

    public function incrementQtty()
    {
        $this->qtty++;
    }

    public function buy()
    {
        $this->dispatch('addToCart', product: $this->local_product['id'], quantity: (int)$this->qtty);
    }

    public function searchSimilar()
    {
        session()->forget(['category', 'brand', 'search']);
        session()->put('similar', $this->local_product['model']);

        $this->dispatch('updateProducts', ['resetPage' => true]);
    }
}
