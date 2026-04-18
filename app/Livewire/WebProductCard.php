<?php

namespace App\Livewire;

use App\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Livewire\Attributes\On;
use Livewire\Component;
use Mary\Traits\Toast;

class WebProductCard extends Component
{
    use Toast;

    public array $local_product;

    public $qtty = 1;

    public $user_price = 0;

    public $offer_price = 0;

    // Escuchamos el evento solo para que el card se refresque y muestre el badge de "En Carrito" actualizado
    #[On('cart-updated')]
    public function refreshCard()
    {
        // No sincronizamos $this->qtty para que el usuario pueda seguir eligiendo cuánto agregar
        $this->render();
    }

    public function mount($product)
    {
        if ($product instanceof Model) {
            $this->local_product = $product->toArray();
            if (isset($product->description_html)) {
                $this->local_product['description_html'] = $product->description_html;
            }
        } else {
            $this->local_product = (array) $product;
        }

        $this->user_price = $this->local_product['user_price'] ?? current_user()?->getProductPrice(Product::find($this->local_product['id'])) ?? 0;
        $this->offer_price = $this->local_product['offer_price'] ?? 0;

        // Inicializar siempre con el valor por defecto del bulto
        $this->qtty = $this->local_product['qtty_package'] ?? 1;
    }

    public function render()
    {
        return view('livewire.web-product-card', [
            'product' => (object) $this->local_product,
            'display_price' => $this->user_price,
            'display_offer' => $this->offer_price,
            'cart' => session()->get('cart', []), // Pasamos el carrito actual para el badge visual
        ]);
    }

    public function decrementQtty()
    {
        $step = $this->local_product['qtty_package'] ?? 1;
        if ($this->qtty > $step) {
            $this->qtty -= $step;
        }
    }

    public function incrementQtty()
    {
        $step = $this->local_product['qtty_package'] ?? 1;
        $this->qtty += $step;
    }

    public function decrementUnit()
    {
        if ($this->qtty > 1) {
            $this->qtty--;
        }
    }

    public function incrementUnit()
    {
        $this->qtty++;
    }

    public function buy()
    {
        $this->dispatch('addToCart', product: $this->local_product['id'], quantity: (int) $this->qtty);

        // RESET: Volvemos a la cantidad base después de agregar
        $this->qtty = $this->local_product['qtty_package'] ?? 1;
    }

    public function searchSimilar()
    {
        session()->forget(['category', 'brand', 'search']);
        session()->put('similar', $this->local_product['model']);

        $this->dispatch('updateProducts', ['resetPage' => true]);
    }
}
