<?php

namespace App\Livewire;

use Livewire\Component;

class WebProduct extends Component
{
    public $filter=[];
    public $products;

    public function mount($filter = [])
    {
        $this->filter = $filter;

        $this->products = \App\Models\Products::where($this->filter['featured'])->orWhere($this->filter)->limit(10)->get();
        dd($this->products);
    }

    public function render()
    {
        return view('livewire.web-product');
    }
}
