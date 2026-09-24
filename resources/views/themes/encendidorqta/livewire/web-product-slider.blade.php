<?php
use App\Models\Product;
use Livewire\Volt\Component;

new class extends Component {
    public $title;
    public $type;
    public $products = [];

    public function mount($type = 'featured', $title = '')
    {
        $this->type = $type;
        $this->title = $title;
        
        // Fetch 6 random ER products
        $erProducts = Product::where('published', 1)
            ->where(function($q) {
                $q->where('brand', 'not like', '%CIBAT%')
                  ->orWhereNull('brand');
            })
            ->where('description', 'not like', '%BATERIA%')
            ->inRandomOrder()
            ->take(6)
            ->get();
            
        // Fetch 6 random CIBAT products
        $cibatProducts = Product::where('published', 1)
            ->where(function($q) {
                $q->where('brand', 'like', '%CIBAT%')
                  ->orWhere('description', 'like', '%BATERIA%');
            })
            ->inRandomOrder()
            ->take(6)
            ->get();
            
        // Combine and shuffle
        $combined = $erProducts->merge($cibatProducts)->shuffle();
        
        $this->products = $combined->map(function ($product) {
            $product->qtty = $product->qtty_package;
            return $product;
        });
    }
}; ?>

<div class="max-w-7xl mx-auto px-4 mt-6 z-10 relative"
    x-data="{
        scrollNext() { 
            const slider = $refs.slider;
            slider.scrollBy({left: slider.offsetWidth / 2, behavior: 'smooth'}); 
        },
        scrollPrev() { 
            const slider = $refs.slider;
            slider.scrollBy({left: -slider.offsetWidth / 2, behavior: 'smooth'}); 
        },
        init() {
            setInterval(() => {
                const slider = $refs.slider;
                if (!slider) return;
                // If reached the end, scroll back to start
                if (slider.scrollLeft >= (slider.scrollWidth - slider.offsetWidth - 10)) {
                    slider.scrollTo({left: 0, behavior: 'smooth'});
                } else {
                    this.scrollNext();
                }
            }, 4000);
        }
    }">
    
    @if($title)
        <div class="flex items-center gap-3 mb-6">
            <!-- Hexagon -->
            <div class="w-8 h-8 bg-gray-400 shrink-0" style="clip-path: polygon(25% 0%, 75% 0%, 100% 50%, 75% 100%, 25% 100%, 0% 50%);"></div>
            
            <h2 class="text-xl md:text-3xl font-black text-gray-400 tracking-wide uppercase">
                {{ $title }}
            </h2>
            
            <div class="ml-4 text-xs md:text-sm text-gray-400 text-left leading-tight font-bold hidden sm:block">
                Lo mejor de nuestro catálogo, reunido en un solo lugar.<br>Descubrí nuestros productos destacados.
            </div>
        </div>
    @endif

    <div class="relative group">
        <!-- Flecha Izquierda -->
        <button @click="scrollPrev" class="absolute left-[-15px] top-1/2 -translate-y-1/2 z-20 bg-white/90 text-gray-600 p-2 rounded-full shadow-lg hover:bg-white hover:text-red-600 transition-colors hidden md:block opacity-0 group-hover:opacity-100">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-6 h-6">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
            </svg>
        </button>

        <!-- Flecha Derecha -->
        <button @click="scrollNext" class="absolute right-[-15px] top-1/2 -translate-y-1/2 z-20 bg-white/90 text-gray-600 p-2 rounded-full shadow-lg hover:bg-white hover:text-red-600 transition-colors hidden md:block opacity-0 group-hover:opacity-100">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-6 h-6">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
            </svg>
        </button>

        <div x-ref="slider" class="flex overflow-x-auto gap-4 pb-6 snap-x scroll-smooth" style="scrollbar-width: none;">
            @forelse ($products as $product)
                <!-- Explicit fixed width to ensure exactly 4 cards fit in ~1200px (1200 / 4 = 300) -->
                <div class="snap-start w-[240px] md:w-[280px] lg:w-[295px] shrink-0">
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
</div>
