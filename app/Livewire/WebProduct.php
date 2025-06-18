<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Services\ProductSearchService;

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

    public function render(ProductSearchService $productSearch)
    {
        $featured = false;
        $params = $this->filter;

        if (isset($params['model'])) {
            $params['similar'] = $params['model'];
            unset($params['model']);
        }

        $component_products = $productSearch->searchProducts($params, $this->items, $featured);

        return view('livewire.web-product', ['products' => $component_products, 'title' => $this->title]);
    }
}
