<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\DB;

class WebSearchFilter extends Component
{
    public $categories = [];
    public $category;
    public $search;
    public function mount(){
        // categories take unique values from products category attribute as id and name
        $this->categories = DB::table('products')->select('category')
            ->where('published', 1)
            ->where('category', '!=', '')
            ->distinct()
            ->orderBy('category')
            ->get(['id', 'category']);

        $this->category = session()->get('category');
        $this->search = session()->get('search');
    }
    public function render()
    {
        return view('livewire.web-search-filter');
    }

    public function goSearch(){
        session()->put('category', $this->category);
        session()->put('search', $this->search);
        // page reload
        return redirect()->to('/');
    }
    public function goReset(){
        session()->forget('category');
        $this->category = '';
        session()->forget('search');
        $this->search = '';
        // page reload
        return redirect()->to('/');
    }
}
