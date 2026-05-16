<?php

namespace App\Livewire;

use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Url;
use Livewire\Component;
use Mary\Traits\Toast;

class WebSearchFilter extends Component
{
    use Toast;

    public $categories = [];

    #[Url(history: true)]
    public $category = null;

    public $brands = [];

    #[Url(history: true)]
    public $brand = null;

    #[Url(history: true)]
    public $search = null;

    #[Url(history: true)]
    public $tag = null;

    public function mount()
    {
        // Get unique categories and brands as an array of objects with id and name
        $this->categories = DB::table('products')
            ->select('category')
            ->where('published', 1)
            ->where('category', '!=', '')
            ->distinct()
            ->orderBy('category')
            ->get()
            ->map(fn ($item) => ['id' => $item->category, 'category' => $item->category])
            ->toArray();

        $this->brands = DB::table('products')
            ->select('brand')
            ->where('published', 1)
            ->where('brand', '!=', '')
            ->distinct()
            ->orderBy('brand')
            ->get()
            ->map(fn ($item) => ['id' => $item->brand, 'brand' => $item->brand])
            ->toArray();
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
        $this->dispatch('updateProducts', filters: ['search' => null, 'resetPage' => true]);
        $this->handleRedirect();
    }

    public function goSearch()
    {
        $this->dispatch('updateProducts', filters: [
            'category' => $this->category,
            'brand' => $this->brand,
            'search' => $this->search,
            'tag' => $this->tag,
            'resetPage' => true,
        ]);

        session()->put('noslider', true);
        $this->handleRedirect();
    }

    public function updatedCategory()
    {
        $this->search = null;
        $this->brand = null;
        $this->tag = null;
        $this->goSearch();
    }

    public function updatedBrand()
    {
        $this->search = null;
        $this->category = null;
        $this->tag = null;
        $this->goSearch();
    }

    public function clearFilters()
    {
        $this->category = null;
        $this->brand = null;
        $this->tag = null;
        $this->goSearch();
    }

    public function addTag($tag)
    {
        // if current tag is the same, remove it
        if ($this->tag === $tag) {
            $this->tag = null;
        } else {
            $this->tag = $tag;
            // clear other filters when adding a tag
            $this->search = null;
            $this->category = null;
            $this->brand = null;
        }

        $this->dispatch('updateProducts', filters: [
            'search' => $this->search,
            'category' => $this->category,
            'brand' => $this->brand,
            'tag' => $this->tag,
            'resetPage' => true,
        ]);

        $this->handleRedirect();
    }
}
