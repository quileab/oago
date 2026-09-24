<?php
use App\Helpers\SettingsHelper;
use App\Services\ProductSearchService;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Livewire\WithoutUrlPagination;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Mary\Traits\Toast;

new class extends Component {
    use WithPagination, WithoutUrlPagination, Toast;

    public int $items = 30;
    public string $displayMode = 'infinite_scroll';
    public bool $featured = false;
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

    public function boot(): void
    {
        $this->productSearchService = App::make(ProductSearchService::class);
    }

    public function mount(): void
    {
        $this->displayMode = SettingsHelper::settings('catalog_display_mode', 'infinite_scroll');
        $this->items = (int) SettingsHelper::settings('catalog_items_per_page', 30);
    }

    public function products()
    {
        if (! empty($this->search)) {
            $this->similar = null;
        }

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

        $products = $this->productSearchService->searchProducts(
            $params,
            (int) $this->items,
            $this->featured,
            paginate: $this->displayMode === 'paginated'
        );

        $products->map(function ($product) {
            $product->qtty = $product->qtty_package;
            return $product;
        });

        return $products;
    }

    #[On('updateProducts')]
    public function with($filters = [], $resetPage = false)
    {
        // Update local properties from filters passed in event
        if (array_key_exists('search', $filters)) $this->search = $filters['search'];
        if (array_key_exists('category', $filters)) $this->category = $filters['category'];
        if (array_key_exists('brand', $filters)) $this->brand = $filters['brand'];
        if (array_key_exists('tag', $filters)) $this->tag = $filters['tag'];
        if (array_key_exists('similar', $filters)) $this->similar = $filters['similar'];
        if (array_key_exists('resetPage', $filters)) $resetPage = $filters['resetPage'];

        if ($resetPage) {
            $this->resetPage();
        }

        return ['products' => $this->products()];
    }

    public function loadMore(): void
    {
        $this->items += 15;
    }
}; ?>

<div class="max-w-7xl mx-auto px-4 mt-6 z-10 relative">
    @if($featured)
        <div class="flex items-center gap-3 mb-6">
            <!-- Hexagon -->
            <div class="w-8 h-8 bg-gray-400 shrink-0" style="clip-path: polygon(25% 0%, 75% 0%, 100% 50%, 75% 100%, 25% 100%, 0% 50%);"></div>
            
            <h2 class="text-xl md:text-3xl font-black text-gray-400 tracking-wide uppercase">
                PRODUCTO DESTACADOS
            </h2>
            
            <div class="ml-4 text-xs md:text-sm text-gray-400 text-left leading-tight font-bold hidden sm:block">
                Lo mejor de nuestro catálogo, reunido en un solo lugar.<br>Descubrí nuestros productos destacados.
            </div>
        </div>
    @else
        <div class="flex items-center gap-3 mb-6">
            <!-- Hexagon -->
            <div class="w-8 h-8 bg-gray-400 shrink-0" style="clip-path: polygon(25% 0%, 75% 0%, 100% 50%, 75% 100%, 25% 100%, 0% 50%);"></div>
            
            <h2 class="text-xl md:text-3xl font-black text-gray-400 tracking-wide uppercase">
                NUEVOS INGRESOS
            </h2>
            
            <div class="ml-4 text-xs md:text-sm text-gray-400 text-left leading-tight font-bold hidden sm:block">
                Lo mejor de nuestro catálogo, reunido en un solo lugar.<br>Descubrí nuestros productos destacados.
            </div>
        </div>
    @endif
    <div wire:ignore.self class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
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

    @if ($displayMode === 'infinite_scroll')
        @if (count($products) >= $items)
            <div x-data x-intersect.full="$wire.loadMore()">
                <div wire:loading wire:target="loadMore" class="text-center w-full p-4">
                    <p>Cargando más productos...</p>
                </div>
            </div>
        @endif
    @else
        <div class="mt-6 flex justify-center">
            {{ $products->links('livewire.pagination') }}
        </div>
    @endif
</div>
