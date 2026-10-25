<?php

namespace App\Livewire;

use App\Models\Product;
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

    #[Url(history: true)]
    public $similar = null;

    public function mount()
    {
        $this->categories = array_map(
            fn (string $category) => ['id' => $category, 'category' => $category],
            Product::getCachedCategories()
        );

        $this->brands = array_map(
            fn (string $brand) => ['id' => $brand, 'brand' => $brand],
            Product::getCachedBrands()
        );
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
            $query = parse_url($referer, PHP_URL_QUERY) ?? '';
            $basePath = parse_url(url('/'), PHP_URL_PATH) ?? '/';

            parse_str($query, $queryParams);
            $hasProductId = isset($queryParams['product_id']) || request()->has('product_id');

            // Si estamos en la página principal y NO hay un product_id en la URL/referer, no redirigir
            if (($path === $basePath || $path === $basePath.'/') && ! $hasProductId) {
                return;
            }
        }

        $params = array_filter([
            'search' => $this->search,
            'category' => $this->category,
            'brand' => $this->brand,
            'tag' => $this->tag,
        ]);

        $url = empty($params) ? '/' : '/?'.http_build_query($params);
        $this->redirect($url, navigate: true);
    }

    public function clearSearch()
    {
        $this->search = null;
        $this->similar = null;
        session()->forget('similar');
        $this->dispatch('updateProducts', filters: [
            'search' => null,
            'similar' => null,
            'resetPage' => true,
        ]);
        $this->handleRedirect();
    }

    public function goSearch()
    {
        $this->similar = null;
        session()->forget('similar');
        $this->dispatch('updateProducts', filters: [
            'category' => $this->category,
            'brand' => $this->brand,
            'search' => $this->search,
            'tag' => $this->tag,
            'similar' => null,
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
        $this->similar = null;
        $this->goSearch();
    }

    public function updatedBrand()
    {
        $this->search = null;
        $this->category = null;
        $this->tag = null;
        $this->similar = null;
        $this->goSearch();
    }

    public function clearFilters()
    {
        $this->category = null;
        $this->brand = null;
        $this->tag = null;
        $this->similar = null;
        session()->forget('similar');
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

        $this->similar = null;
        session()->forget('similar');
        $this->dispatch('updateProducts', filters: [
            'search' => $this->search,
            'category' => $this->category,
            'brand' => $this->brand,
            'tag' => $this->tag,
            'similar' => null,
            'resetPage' => true,
        ]);

        $this->handleRedirect();
    }
}
