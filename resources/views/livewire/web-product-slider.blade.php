<?php
use App\Services\ProductSearchService;
use Livewire\Volt\Component;

new class extends Component {
    public $title;
    public $type;
    public $products = [];

    public function mount($type = 'featured', $title = '')
    {
        $this->type = $type;
        $this->title = $title;
        
        $params = [];
        $featured = false;
        
        if ($this->type === 'featured') {
            $featured = true;
        } elseif ($this->type === 'new') {
            $params['order_by'] = 'created_at';
            $params['order_direction'] = 'desc';
        }
        
        $service = app(ProductSearchService::class);
        // Get 10 items for the slider
        $paginator = $service->searchProducts($params, 10, $featured);
        
        $this->products = collect($paginator->items())->map(function ($product) {
            $product->qtty = $product->qtty_package;
            return $product;
        });
    }
}; ?>

<div class="mx-5 z-10">
    <h2 class="text-2xl font-black text-gray-900 my-8 flex items-center gap-3">
        <span class="w-2 h-8 bg-blue-600 rounded-full"></span>
        {{ $title }}
    </h2>
    <div class="flex overflow-x-auto gap-6 pb-6 snap-x" style="scrollbar-width: thin;">
        @forelse ($products as $product)
            <div class="snap-start min-w-[280px] lg:min-w-[320px] shrink-0">
                @php
                    $product->description_html = str_replace('\n', '', $product->description_html);
                @endphp
                <livewire:web-product-card :$product wire:key="slider-{{ $type }}-{{ $product->id }}" />
            </div>
        @empty
            <p class="text-gray-500">No hay productos para mostrar en esta sección.</p>
        @endforelse
    </div>
</div>
