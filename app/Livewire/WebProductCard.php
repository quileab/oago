<?php

namespace App\Livewire;

use App\Helpers\SettingsHelper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
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
        $productModel = $product instanceof Model ? $product : null;

        if ($productModel) {
            $this->local_product = $productModel->toArray();
            if (isset($product->description_html)) {
                $this->local_product['description_html'] = $product->description_html;
            }
        } else {
            $this->local_product = (array) $product;
        }

        $this->user_price = $this->local_product['user_price']
            ?? ($productModel ? current_user()?->getProductPrice($productModel) : null)
            ?? ($this->local_product['price'] ?? 0);
        $this->offer_price = $this->local_product['offer_price'] ?? 0;

        $this->qtty = $this->local_product['qtty_package'] ?? 1;
    }

    public function render()
    {
        $productObj = (object) $this->local_product;

        // Ensure id exists to avoid Blade errors
        if (! isset($productObj->id)) {
            $productObj->id = 0;
        }

        return view('livewire.web-product-card', [
            'product' => $productObj,
            'display_price' => $this->user_price,
            'display_offer' => $this->offer_price,
            'cart' => session()->get('cart', []), // Pasamos el carrito actual para el badge visual
            'showPrices' => ! Auth::guest() || SettingsHelper::settings('show_prices_to_guests', false),
            'guestMessage' => SettingsHelper::settings('show_prices_to_guests', false) ? 'Regístrese para comprar' : 'Regístrese para ver precios',
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
        $this->dispatch('addToCart', product: [
            'id' => $this->local_product['id'],
            'description' => $this->local_product['description'],
            'user_price' => $this->user_price,
            'qtty_package' => $this->local_product['qtty_package'] ?? 1,
        ], quantity: (int) $this->qtty);

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
