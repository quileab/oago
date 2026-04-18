<?php

namespace App\Livewire;

// use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Mary\Traits\Toast;

class WebSearchFilter extends Component
{
    use Toast;

    public $categories = [];

    public $category;

    public $brands = [];

    public $brand;

    // public $tag;
    public $search;

    public function mount()
    {
        // categories take unique values from products category attribute as id and name
        $this->categories = // Cache::remember('categories', 60*60, function () {
            DB::table('products')->select('category')
                ->where('published', 1)
                ->where('category', '!=', '')
                ->distinct()
                ->orderBy('category')
                ->get(['id', 'category']);
        $this->brands = // Cache::remember('brands', 60*60, function () {
            DB::table('products')->select('brand')
                ->where('published', 1)
                ->where('brand', '!=', '')
                ->distinct()
                ->orderBy('brand')
                ->get(['id', 'brand']);

        // });

        $this->category = session()->get('category') ?: null;
        $this->search = session()->get('search') ?: null;
        $this->brand = session()->get('brand') ?: null;
    }

    public function render()
    {
        return view('livewire.web-search-filter');
    }

    private function handleRedirect()
    {
        $referer = request()->headers->get('referer');
        if ($referer) {
            $path = parse_url($referer, PHP_URL_PATH) ?? '/';
            $basePath = parse_url(url('/'), PHP_URL_PATH) ?? '/';

            if ($path === $basePath || $path === $basePath.'/') {
                return; // Ya estamos en la página principal, no es necesario recargar
            }
        }

        $this->redirect('/', navigate: true);
    }

    public function clearSearch()
    {
        $this->search = null;
        session()->forget('search');
        $this->dispatch('updateProducts', ['resetPage' => true]);
        $this->handleRedirect();
    }

    public function goSearch()
    {
        if ($this->category && strlen($this->category)) {
            session()->put('category', $this->category);
        } else {
            session()->forget('category');
        }
        if ($this->brand && strlen($this->brand)) {
            session()->put('brand', $this->brand);
        } else {
            session()->forget('brand');
        }
        if ($this->search && strlen($this->search)) {
            session()->put('search', $this->search);
        } else {
            session()->forget('search');
        }
        session()->forget('similar');
        $this->dispatch('updateProducts', ['resetPage' => true]);
        // set session noslider
        session()->put('noslider', true);
        $this->handleRedirect();
    }

    public function updatedCategory()
    {
        $this->search = null;
        $this->brand = null;
        $this->goSearch();
    }

    public function updatedBrand()
    {
        $this->search = null;
        $this->category = null;
        $this->goSearch();
    }

    public function clearFilters()
    {
        $this->category = null;
        $this->brand = null;
        session()->forget('tag');
        $this->goSearch();
    }

    public function addTag($tag)
    {
        // clear other filters
        $this->search = null;
        $this->category = null;
        $this->brand = null;
        session()->forget(['search', 'category', 'brand']);

        // if session has tag is the same, remove it
        if (session()->has('tag') && session('tag') == $tag) {
            session()->forget('tag');
            $this->dispatch('updateProducts', ['resetPage' => true]);
            $this->handleRedirect();

            return;
        }
        session()->put('tag', $tag);
        $this->dispatch('updateProducts', ['resetPage' => true]);
        $this->handleRedirect();
    }
}
