<?php

namespace App\Livewire;

use Livewire\Component;

class WebProductCard extends Component
{
    public $local_product;

    public $qtty = 1;

    public function mount($product)
    {
        $this->local_product = $product;
        $this->qtty = $product['qtty_package'] ?? 1;
    }
    public function render()
    {
        return view('livewire.web-product-card', ['product' => $this->local_product]);
    }

    public function buy($product, $qtty = 1)
    {
        //dd('buy', $product, $qtty);
        $qtty = $this->qtty ?? $qtty;
        $this->dispatch('addToCart', $product, $qtty);
        $this->skipRender();
    }

    public function searchSimilar($product)
    {
        session()->forget('category');
        session()->forget('brand');
        session()->forget('search');
        session()->put('similar', $product['model']);

        $this->dispatch('updateProducts', ['resetPage' => true]);
        $this->skipRender();
    }
}
