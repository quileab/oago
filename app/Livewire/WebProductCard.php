<?php

namespace App\Livewire;

use Livewire\Component;

class WebProductCard extends Component
{
    public $product;

    public function mount($product)
    {
        $this->product = $product;
    }
    public function render()
    {
        return view('livewire.web-product-card');
    }
}
