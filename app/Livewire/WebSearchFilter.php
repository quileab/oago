<?php

namespace App\Livewire;

//use Illuminate\Support\Facades\Cache;
use Livewire\Component;
use Illuminate\Support\Facades\DB;
use Mary\Traits\Toast;

class WebSearchFilter extends Component
{
    use Toast;
    public $categories = [];
    public $category;
    public $brands = [];
    public $brand;
    public $search;
    public $showFilters = false;
    public function mount()
    {
        // categories take unique values from products category attribute as id and name
        $this->categories = //Cache::remember('categories', 60*60, function () {
            DB::table('products')->select('category')
            ->where('published', 1)
            ->where('category', '!=', '')
            ->distinct()
            ->orderBy('category')
            ->get(['id', 'category']);
        $this->brands = //Cache::remember('brands', 60*60, function () {
            DB::table('products')->select('brand')
            ->where('published', 1)
            ->where('brand', '!=', '')
            ->distinct()
            ->orderBy('brand')
            ->get(['id', 'brand']);

        //});

        $this->category = session()->get('category')?: null;
        $this->search = session()->get('search')?: null;
        $this->brand = session()->get('brand')?: null;
    }
    public function render()
    {
        return view('livewire.web-search-filter');
    }

    public function goSearch()
    {
        if (!empty($this->category)) {
            session()->put('category', $this->category);
        } else {
            session()->forget('category');
        }
        if (!empty($this->brand)) {
            session()->put('brand', $this->brand);
        } else {
            session()->forget('brand');
        }
        if (!empty($this->search)) {
            session()->put('search', $this->search);
        } else {
            session()->forget('search');
        }
        // page reload
        // return redirect()->to('/');
        // replaced by dispatch browser event
        $this->dispatch('updateProducts');
        $this->showFilters = false;
    }

}
