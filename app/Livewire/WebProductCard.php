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
    }
    public function render()
    {
        return view('livewire.web-product-card', ['product' => $this->local_product]);
    }

    public function buy($product, $qtty = 1)
    {
        $qtty = $this->qtty ?? $qtty;
        $this->dispatch('addToCart', $product, $qtty);
        $this->skipRender();
    }

    public function searchSimilar($product)
    {
        session()->put('similar', $product['model']);
        $this->redirect('/');
    }
}
