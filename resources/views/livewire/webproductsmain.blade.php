<?php
use App\Services\ProductSearchService;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Livewire\WithoutUrlPagination;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Mary\Traits\Toast;

new class extends Component {
    use WithPagination, WithoutUrlPagination, Toast;

    public $items = 30;
    public $featured = false;
    public $filter; // for data passed to the component

    #[Url(history: true)]
    public $search = null;

    #[Url(history: true)]
    public $category = null;

    #[Url(history: true)]
    public $brand = null;

    #[Url(history: true)]
    public $tag = null;

    #[Url(history: true)]
    public $similar = null;

    protected ProductSearchService $productSearchService;

    public function boot()
    {
        $this->productSearchService = App::make(ProductSearchService::class);
    }

    public function products()
    {
        $params = [
            'search' => $this->search,
            'category' => $this->category,
            'brand' => $this->brand,
            'similar' => $this->similar,
            'tag' => $this->tag,
        ];

        // if there's a filter, merge it with params
        if (is_array($this->filter)) {
            $params = array_merge($params, $this->filter);
        }

        $products = $this->productSearchService->searchProducts($params, (int) $this->items, $this->featured);

        $products->map(function ($product) {
            $product->qtty = $product->qtty_package;
            return $product;
        });
        return $products;
    }

    public function with()
    {
        return ['products' => $this->products()];
    }

    #[On('updateProducts')]
    public function updateFilters($filters = [])
    {
        // Update local properties from filters passed in event
        if (array_key_exists('search', $filters)) $this->search = $filters['search'];
        if (array_key_exists('category', $filters)) $this->category = $filters['category'];
        if (array_key_exists('brand', $filters)) $this->brand = $filters['brand'];
        if (array_key_exists('tag', $filters)) $this->tag = $filters['tag'];
        if (array_key_exists('similar', $filters)) $this->similar = $filters['similar'];

        if (isset($filters['resetPage']) && $filters['resetPage']) {
            $this->resetPage();
        }
    }

    public function loadMore()
    {
        $this->items += 15;
    }
}; ?>

<div class="mx-5 z-10 bg-gray-200">
    @if($featured)
        <h2 class="text-2xl font-black text-gray-900 my-8 flex items-center gap-3">
            <span class="w-2 h-8 bg-blue-600 rounded-full"></span>
            PRODUCTOS DESTACADOS
        </h2>
    @else
        <h2 class="text-2xl font-black text-gray-900 my-8 flex items-center gap-3">
            <span class="w-2 h-8 bg-blue-600 rounded-full"></span>
            NUESTRO CATÁLOGO
        </h2>
    @endif
    <div wire:ignore.self class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        @forelse ($products as $product)
            <div>
                @php
                    // remove \n from description 
                    $product->description_html = str_replace('\n', '', $product->description_html);
                @endphp
                {{-- <livewire:web-product-card :$product wire:key="{{ $product->id }}" /> --}}
                <livewire:web-product-card :$product wire:key="prod-{{ $product->id }}" />
            </div>
        @empty
            <h1 class="text-2xl">No existen productos</h1>
        @endforelse
    </div>

    @if (count($products) >= $items)
        <div x-data x-intersect.full="$wire.loadMore()">
            <div wire:loading wire:target="loadMore" class="text-center w-full p-4">
                <p>Cargando más productos...</p>
            </div>
        </div>
    @endif
</div>
