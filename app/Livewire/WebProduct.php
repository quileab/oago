<?php

namespace App\Livewire;

use Livewire\Component;

class WebProduct extends Component
{
    public $filter=[];
    public $products;
    public $title="Default Title";
    public $items = 6;

    public function mount($filter = [])
    {
        $this->filter = $filter;
        $this->products = \App\Models\Products::where($this->filter)
            ->limit($this->items)
            // filter when session category is set
            ->when(session()->has('category'), function ($query) {
                return $query->where('category', session()->get('category'));
            })
            // filter when session search is set
            ->when(session()->has('search'), function ($query) {
                return $query->where('description', 'like', '%' . session()->get('search') . '%');
            })
            ->get();
    }

    public function render()
    {
        return view('livewire.web-product');
    }
}
