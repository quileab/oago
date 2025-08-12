<?php
use App\Services\ProductSearchService;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Livewire\WithoutUrlPagination;
use Livewire\Attributes\On;
use Mary\Traits\Toast;
// use Livewire\Attributes\Inject; // Importar el atributo Inject para que el constructor reciba el servicio

new class extends Component {
    use WithPagination, WithoutUrlPagination, Toast;

    public $items = 30;
    public $featured = false;

    // # //[ Inject]
    protected ProductSearchService $productSearchService;

    public function boot() // replace constructor inject with boot
    {
        $this->productSearchService = App::make(ProductSearchService::class);
    }

    public function products()
    {
        $params = [
            'search' => session()->get('search'),
            'category' => session()->get('category'),
            'brand' => session()->get('brand'),
            'similar' => session()->get('similar'),
            'tag' => session()->get('tag'),
        ];

        $products = $this->productSearchService->searchProducts($params, $this->items, $this->featured);

        $products->map(function ($product) {
            $product->qtty = $product->qtty_package;
            return $product;
        });
        return $products;
    }

    #[On('updateProducts')]
    public function with($resetPage = false)
    {
        if ($resetPage) {
            $this->resetPage();
            // do not update until the page is reset
            $resetPage = false;
        }
        return ['products' => $this->products()];
    }

    public function loadMore()
    {
        $this->items += 15;
    }
}; ?>

<div class="mx-5 z-10 bg-gray-200">
    @if($featured)
        <h2 class="text-3xl font-bold my-4">Productos Destacados</h2>
    @else
        <h2 class="text-3xl font-bold my-4">Productos</h2>
    @endif
    <div wire:ignore.self class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        @forelse ($products as $product)
            <div>
                @php
                    // remove \n from description 
                    $product->description_html = str_replace('\n', '', $product->description_html);
                @endphp
                {{-- <livewire:web-product-card :$product wire:key="{{ $product->id }}" /> --}}
                <livewire:web-product-card :$product :key="'prod-{{ $product->id }}'.Str::random(16)" />
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