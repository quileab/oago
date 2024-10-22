<?php

namespace App\Livewire;

use Livewire\Component;

class WebProductCard extends Component
{
    public $local_product;

    public function mount($product)
    {
        $this->local_product = $product;
    }
    public function render()
    {
        return view('livewire.web-product-card',['product'=>$this->local_product]);
    }

    public function buy($product,$byBulk = false){
        $this->dispatch('addToCart', $product, $byBulk);
        $this->skipRender();      
    }

    public function updated($local_product){
        dd($local_product);
    }
}
