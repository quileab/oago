<?php

namespace App\Livewire;

use Livewire\Component;

class WebProduct extends Component
{
    public $filter=[];
    public $component_products;
    public $title="Default Title";
    public $items = 6;

    public function mount($filter = [])
    {
        $this->filter = $filter;
        $user = auth()->user();
        if (!$user) {
            $this->component_products = \App\Models\Product::where($this->filter)
            ->limit($this->items)
            // exclude products that are not published and description starts with "CONS INT"
            ->where('published', 1)
            ->where('description', 'not like', 'CONS INT%')

            // filter when session category is set
            ->when(session()->has('category'), function ($query) {
                return $query->where('category', session()->get('category'));
            })
            // filter when session search is set
            ->when(session()->has('search'), function ($query) {
                return $query->where('description', 'like', '%' . session()->get('search') . '%');
            })
            ->get();
            return;            
        }

        // Dump the filter and session data
        $this->component_products=\App\Models\Product::where($this->filter)
            ->limit($this->items)
            // exclude products that are not published and description starts with "CONS INT"
            ->where('published', 1)
            ->where('description', 'not like', 'CONS INT%')
            
            // filter when session category is set
            ->when(session()->has('category'), function ($query) {
                return $query->where('category', session()->get('category'));
            })
            // filter when session search is set
            ->when(session()->has('search'), function ($query) {
                return $query->where('description', 'like', '%' . session()->get('search') . '%');
            })
            ->leftJoin('list_prices', function ($join) use ($user) {
                $join->on('products.id', '=', 'list_prices.product_id')
                     ->where('list_prices.list_id', $user->list_id); // Asociar precios de la lista del usuario
            })
            ->select('products.*', 'list_prices.price as user_price') // Seleccionar columnas de productos y el precio del usuario
            ->get();

            //dd($this->products->toSql(), $this->products->getBindings());
            //dd($user->list_id,$this->products);
    }

    public function render()
    {
        return view('livewire.web-product', ['products' => $this->component_products]);
    }
}
