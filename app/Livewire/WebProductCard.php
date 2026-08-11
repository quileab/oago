<?php

namespace App\Livewire;

use App\Helpers\SettingsHelper;
use App\Services\PriceListService;
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

    /** 'bulk' | 'unit' */
    public string $buyMode = 'bulk';

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
            $this->local_product['tags_array'] = $productModel->tags_array;

            if (isset($product->description_html)) {
                $this->local_product['description_html'] = $product->description_html;
            }
            if (isset($product->base_price)) {
                $this->local_product['base_price'] = $product->base_price;
            }
            if (isset($product->promo_price)) {
                $this->local_product['promo_price'] = $product->promo_price;
            }
        } else {
            $this->local_product = (array) $product;
            if (! isset($this->local_product['tags_array'])) {
                $this->local_product['tags_array'] = isset($this->local_product['tags']) && ! empty($this->local_product['tags'])
                    ? array_values(array_filter(explode('|', $this->local_product['tags'])))
                    : [];
            }
        }

        $priceService = app(PriceListService::class);
        $listId = current_user()?->list_id ?? 0;

        $basePrice = $this->local_product['base_price']
            ?? ($productModel ? $priceService->getEffectivePrice($listId, $productModel->id, true) : null)
            ?? ($this->local_product['price'] ?? 0);

        $promoPrice = $this->local_product['promo_price']
            ?? ($productModel ? $priceService->getEffectivePrice($listId, $productModel->id, false) : null)
            ?? null;

        if ($promoPrice !== null && $promoPrice < $basePrice) {
            $this->user_price = $basePrice;
            $this->offer_price = $promoPrice;
        } else {
            $this->user_price = $basePrice;
            $this->offer_price = 0;
        }

        $package = (int) ($this->local_product['qtty_package'] ?? 1);
        $this->buyMode = $package > 1 ? 'bulk' : 'unit';
        $this->qtty = $package > 1 ? $package : 1;
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

    public function setBuyMode(string $mode): void
    {
        $package = (int) ($this->local_product['qtty_package'] ?? 1);
        $this->buyMode = $mode;
        $this->qtty = $mode === 'bulk' ? $package : 1;
    }

    public function decrement(): void
    {
        $package = (int) ($this->local_product['qtty_package'] ?? 1);
        $step = $this->buyMode === 'bulk' ? $package : 1;
        $min = $this->buyMode === 'bulk' ? $package : 1;
        $currentQtty = (int) $this->qtty;

        if ($currentQtty > $min) {
            $this->qtty = $currentQtty - $step;
        }
    }

    public function increment(): void
    {
        $package = (int) ($this->local_product['qtty_package'] ?? 1);
        $step = $this->buyMode === 'bulk' ? $package : 1;
        $this->qtty = (int) $this->qtty + $step;
    }

    public function buy(): void
    {
        $package = (int) ($this->local_product['qtty_package'] ?? 1);

        $this->dispatch('addToCart', product: [
            'id' => $this->local_product['id'],
            'description' => $this->local_product['description'],
            'user_price' => $this->user_price,
            'qtty_package' => $package,
        ], quantity: (int) $this->qtty);

        $this->qtty = $this->buyMode === 'bulk' ? $package : 1;
    }

    public function searchSimilar()
    {
        session()->forget(['category', 'brand', 'search']);
        session()->put('similar', $this->local_product['model']);

        $this->dispatch('updateProducts', filters: [
            'category' => null,
            'brand' => null,
            'search' => null,
            'tag' => null,
            'similar' => $this->local_product['model'],
            'resetPage' => true,
        ]);
    }
}
