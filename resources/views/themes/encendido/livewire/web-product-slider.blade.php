<?php

use App\Services\ProductSearchService;
use Livewire\Volt\Component;
use Illuminate\Support\Facades\App;

new class extends Component {
    public string $type = 'featured';
    public string $title = 'DESTACADOS';
    
    public function with()
    {
        $searchService = App::make(ProductSearchService::class);
        $params = ['published' => true];
        
        if ($this->type === 'new') {
            $params['order_by'] = 'created_at';
            $params['order_direction'] = 'desc';
            $products = $searchService->searchProducts($params, 8, false);
        } else {
            $products = $searchService->searchProducts($params, 8, true);
        }

        // searchProducts returns a paginator or single product depending on limit, but itemsPerPage is 8.
        // so it returns a paginator.
        $productsList = $products ? $products->items() : [];

        // Asegurar que tengan qtty para el card
        foreach($productsList as $product) {
            $product->qtty = $product->qtty_package;
        }
        
        return [
            'products' => collect($productsList)
        ];
    }
    
    public function toJSON()
    {
        return [];
    }
}; ?>

<div class="z-10 bg-base-100/30 rounded-2xl p-6 shadow-xl backdrop-blur-md border border-base-200">
    <h2 class="text-2xl font-black text-base-content mb-6 flex items-center gap-3">
        <span class="w-2 h-8 bg-primary rounded-full"></span>
        {{ $title }}
    </h2>
    
    @if($products->isEmpty())
        <p class="text-neutral-content/70">No hay productos disponibles por el momento.</p>
    @else
        <!-- Swiper Container -->
        <div class="swiper product-swiper overflow-hidden rounded-xl px-1 py-4">
            <div class="swiper-wrapper flex items-stretch">
                @foreach ($products as $product)
                    <div class="swiper-slide h-auto flex flex-col">
                        @php
                            $product->description_html = str_replace('\n', '', $product->description_html ?? '');
                        @endphp
                        <livewire:web-product-card :$product wire:key="slider-{{ $this->type }}-{{ $product->id }}" />
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
