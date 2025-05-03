<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;

class WebProduct extends Component
{
    use WithPagination;
    public $filter = [];
    //public $component_products;
    public $title = "Default Title";
    public $items = 15;

    public function mount($filter = [])
    {
        $this->filter = $filter;

        if (session()->has('similar')) {
            $this->filter['model'] = session()->get('similar');
            //clear all session filters
            session()->forget('similar');
            session()->forget('category');
            session()->forget('search');
        }
    }

    public function render()
    {
        $component_products = \App\Models\Product::where($this->filter)
            ->limit($this->items)
            // exclude products that are not published and description starts with "CONS INT"
            // ->where('description', 'not like', 'CONS INT%')
            ->where('model', '!=', 'consumo interno')
            // when user is logged in
            ->when($user = auth()->user(), function ($query) use ($user) {
                return $query->leftJoin('list_prices', function ($join) use ($user) {
                    $join->on('products.id', '=', 'list_prices.product_id')
                        ->where('list_prices.list_id', $user->list_id); // Asociar precios de la lista del usuario
                })
                    // Seleccionar columnas de productos y el precio del usuario
                    ->select('products.*', 'list_prices.price as user_price');
            })
            ->get();

        return view('livewire.web-product', ['products' => $component_products, 'title' => $this->title]);
    }
}
