<?php
use App\Models\Product;
use App\Services\ProductSearchService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;
use Mary\Traits\Toast;

new class extends Component
{
    use Toast;

    public $product;

    public $qtty = 1;

    public $related_products = [];

    public array $media = [];

    public array $tags = [];

    public $user_price = 0;

    public $offer_price = 0;

    public function mount(Product $prod_id)
    {
        $this->product = app(ProductSearchService::class)
            ->searchProducts(['id' => $prod_id->id], 1);

        if (! $this->product) {
            $this->product = new Product([
                'id' => 0,
                'brand' => 'Producto no encontrado',
                'description' => 'El producto que busca no está disponible o no existe.',
                'image_url' => asset('imgs/oago.png'),
                'tags' => '',
                'featured' => false,
                'description_html' => '',
                'user_price' => 0,
                'offer_price' => 0,
                'qtty_unit' => 1,
                'qtty_package' => 1,
                'stock' => 0,
            ]);
            $this->related_products = [];
        } else {
            $this->user_price = $this->product->base_price ?? (float) $this->product->price;
            $this->offer_price = $this->product->promo_price ?? 0;
            $this->qtty = $this->product->qtty_package;
            $this->media = $this->product->media ?? [];
            $this->tags = $this->product->tags_array ?? [];
        }
    }

    public function getRelatedProducts(): Collection|array
    {
        if (! empty($this->related_products)) {
            return $this->related_products;
        }

        if (! $this->product || ! $this->product->id) {
            return collect();
        }

        $this->related_products = app(ProductSearchService::class)
            ->searchRelatedProducts($this->product, 12);

        return $this->related_products;
    }

    public function buy(int $productId): void
    {
        $this->dispatch('addToCart', product: $productId, quantity: (int) $this->qtty);

        $this->qtty = $this->product->qtty_package ?? 1;
    }

    public function primaryMedia(): array
    {
        return $this->media[0] ?? [
            'type' => 'image',
            'url' => $this->product->image_url,
            'thumb' => $this->product->image_url,
        ];
    }

    public function stockBadge(): array
    {
        if ($this->product->stock < 10) {
            return ['label' => 'STOCK BAJO', 'class' => 'text-red-600 bg-red-50 border-red-100', 'icon' => 's-bolt'];
        }

        if ($this->product->stock < 100) {
            return ['label' => 'STOCK MEDIO', 'class' => 'text-amber-600 bg-amber-50 border-amber-100', 'icon' => 's-bolt'];
        }

        return ['label' => 'EN STOCK', 'class' => 'text-green-600 bg-green-50 border-green-100', 'icon' => 's-check-circle'];
    }

    public function canBuy(): bool
    {
        $basePrice = $this->offer_price > 0 ? $this->offer_price : $this->user_price;
        $currUser = current_user();
        return $this->product->stock > 0 && $currUser && ! in_array($currUser->role->value, ['none', 'guest']) && $basePrice > 0;
    }

    public function unitPrice(): float
    {
        $basePrice = $this->offer_price > 0 ? $this->offer_price : $this->user_price;

        return $this->product->qtty_unit > 0 ? $basePrice / $this->product->qtty_unit : $basePrice;
    }

    public function showPrices(): bool
    {
        return ! Auth::guest() || \App\Helpers\SettingsHelper::settings('show_prices_to_guests', false);
    }

    public function guestMessage(): string
    {
        return \App\Helpers\SettingsHelper::settings('show_prices_to_guests', false)
            ? 'Inicie sesión para comprar'
            : 'Inicie sesión para ver precios y comprar';
    }
}; ?>

<div class="max-w-7xl mx-auto p-4 lg:p-6">
    <!-- Migas de pan (Breadcrumbs) -->
    <div class="text-xs text-slate-500 mb-6 font-medium">
        <a href="/" class="hover:text-blue-600 transition-colors">Inicio</a> > 
        <a href="/?brand={{ $product->brand }}" class="hover:text-blue-600 transition-colors">{{ $product->brand ?: 'Productos' }}</a> > 
        <span class="font-bold text-slate-800">{{ $product->description }}</span>
    </div>

    @if ($product && $product->id !== 0)
        <!-- Contenedor Principal (Split) -->
        <div class="flex flex-col md:flex-row gap-8 lg:gap-12 bg-white p-6 md:p-8 rounded-2xl shadow-sm border border-slate-100 mb-8">
            
            <!-- Izquierda: Imágenes -->
            <div class="w-full md:w-[45%] flex flex-col items-center">
                @php($primaryMedia = $this->primaryMedia())

                <div x-data="{
                    activeMedia: {{ json_encode($primaryMedia) }},
                    mediaList: {{ json_encode($this->media) }},
                    getProxiedUrl(url) {
                        if (!url) return '/imgs/fallback.webp';
                        if (url.startsWith('http') && !url.includes(window.location.hostname) && !url.includes('localhost') && !url.includes('127.0.0.1')) {
                            return '{{ route('proxy.image') }}?url=' + encodeURIComponent(url);
                        }
                        return url;
                    },
                    getYouTubeEmbedUrl(url) {
                        if (!url) return '';
                        let videoId = '';
                        try {
                            const regExp = /^.*(youtu.be\/|v\/|u\/\w\/|embed\/|watch\?v=|\&v=)([^#\&\?]*).*/;
                            const match = url.match(regExp);
                            if (match && match[2].length === 11) {
                                videoId = match[2];
                            } else if (url.includes('v=')) {
                                videoId = url.split('v=')[1].split('&')[0];
                            }
                        } catch (e) {
                            console.error('Error extracting YouTube ID', e);
                        }
                        
                        return videoId ? 'https://www.youtube.com/embed/' + videoId : url;
                    }
                }" class="w-full flex flex-col items-center">
                    
                    <div class="w-full aspect-[4/3] flex items-center justify-center p-4 border border-slate-200 rounded-xl mb-4 relative" id="detail-img-{{ $product->id }}">
                        @if($product->featured)
                            <div class="absolute top-4 left-4 z-10">
                                <span class="px-3 py-1 text-[10px] font-black text-white bg-red-600 rounded-br-lg rounded-tl-xl shadow-sm uppercase tracking-wider">DESTACADO</span>
                            </div>
                        @endif

                        <template x-if="activeMedia.type === 'image'">
                            <img :src="getProxiedUrl(activeMedia.url)"
                                class="max-w-full max-h-full object-contain transition-transform duration-700 hover:scale-105 {{ $product->stock == 0 ? 'opacity-40 grayscale' : '' }}"
                                onerror="this.src='/imgs/fallback.webp'">
                        </template>

                        <template x-if="activeMedia.type === 'video'">
                            <div class="w-full h-full rounded-xl overflow-hidden" :key="activeMedia.url">
                                <iframe class="w-full h-full"
                                    :src="getYouTubeEmbedUrl(activeMedia.url)"
                                    frameborder="0"
                                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                    allowfullscreen
                                    referrerpolicy="strict-origin-when-cross-origin">
                                </iframe>
                            </div>
                        </template>

                        @if($product->stock == 0)
                            <div class="absolute inset-0 flex items-center justify-center pointer-events-none z-10">
                                <span class="px-6 py-2 bg-white/90 backdrop-blur text-gray-500 font-black rounded-xl border-2 border-gray-200 shadow-xl uppercase tracking-[0.2em]">Agotado</span>
                            </div>
                        @endif
                    </div>

                    <!-- Miniaturas -->
                    <template x-if="mediaList.length > 1">
                        <div class="flex flex-wrap gap-2 w-full justify-center">
                            <template x-for="(item, index) in mediaList" :key="index">
                                <button @click="activeMedia = item"
                                    class="w-16 h-16 rounded-lg overflow-hidden border-2 transition-all p-1 bg-white flex-shrink-0"
                                    :class="activeMedia.url === item.url ? 'border-blue-600 shadow-sm' : 'border-slate-200 hover:border-slate-400 opacity-70 hover:opacity-100'">

                                    <img :src="getProxiedUrl(item.thumb)" class="w-full h-full object-contain rounded-md" onerror="this.src='/imgs/fallback.webp'">

                                    <template x-if="item.type === 'video'">
                                        <div class="absolute inset-0 flex items-center justify-center bg-black/10">
                                            <div class="bg-white/90 rounded-full p-1 shadow-sm">
                                                <x-icon name="s-play" class="w-4 h-4 text-red-600" />
                                            </div>
                                        </div>
                                    </template>
                                </button>
                            </template>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Derecha: Información y Compra -->
            <div class="w-full md:w-[55%] flex flex-col justify-start">
                
                <!-- Título y Logo -->
                <div class="flex justify-between items-start gap-4 mb-2">
                    <h1 class="text-2xl md:text-3xl font-black text-slate-900 leading-tight">
                        {{ $product->description }}
                    </h1>
                    @if($product->brand)
                        <div class="shrink-0 font-black text-red-600 flex items-center gap-1 text-lg md:text-xl border px-2 py-1 rounded bg-slate-50">
                            {{ strtoupper($product->brand) }}
                        </div>
                    @endif
                </div>
                
                <!-- Subtítulo -->
                <div class="text-xs text-slate-500 mb-4 flex flex-wrap items-center gap-2">
                    <span>Código: <strong>{{ $product->id }}</strong></span>
                    <span class="text-slate-300">|</span>
                    <span>Marca: <strong>{{ $product->brand ?: 'Genérica' }}</strong></span>
                </div>

                <!-- Rating y Stock -->
                <div class="flex items-center gap-4 mb-6">
                    <div class="flex items-center text-yellow-400 text-sm">
                        ★★★★<span class="text-slate-300">★</span> <span class="text-slate-500 ml-1">(12)</span>
                    </div>
                    
                    @php($stockBadge = $this->stockBadge())
                    <div class="flex items-center text-sm font-bold px-2 py-0.5 rounded {{ $stockBadge['class'] }}">
                        <x-icon :name="$stockBadge['icon']" class="w-4 h-4 mr-1" />
                        {{ $stockBadge['label'] }}
                    </div>
                </div>

                <!-- Lista de Características -->
                <ul class="flex flex-col gap-3 mb-8 text-sm text-slate-700">
                    @if(count($this->tags) > 0)
                        @foreach(array_slice($this->tags, 0, 4) as $tag)
                            <li class="flex items-center gap-2"><x-icon name="o-check-circle" class="w-5 h-5 text-slate-400"/> {{ $tag }}</li>
                        @endforeach
                    @else
                        <li class="flex items-center gap-2"><x-icon name="o-bolt" class="w-5 h-5 text-slate-400"/> 12V</li>
                        <li class="flex items-center gap-2"><x-icon name="o-battery-50" class="w-5 h-5 text-slate-400"/> 4Ah</li>
                        <li class="flex items-center gap-2"><x-icon name="o-cog" class="w-5 h-5 text-slate-400"/> Sellada - Libre mantenimiento</li>
                        <li class="flex items-center gap-2"><x-icon name="o-check-badge" class="w-5 h-5 text-slate-400"/> Compatible con múltiples modelos</li>
                    @endif
                </ul>

                <!-- Precios -->
                @if($this->showPrices())
                    <div class="mb-6">
                        @if(($offer_price > 0 ? $offer_price : $user_price) <= 0)
                            <span class="text-2xl font-black text-slate-500 uppercase tracking-wider">Muy pronto</span>
                        @else
                            <div class="flex flex-col">
                                @if($offer_price > 0)
                                    <span class="text-sm text-slate-400 line-through font-bold mb-1">$ {{ number_format($user_price, 2, ',', '.') }}</span>
                                    <div class="flex items-baseline gap-2">
                                        <span class="text-4xl font-black text-slate-900 tracking-tighter">$ {{ number_format($offer_price, 2, ',', '.') }}</span>
                                        <span class="text-[10px] font-bold text-white bg-green-600 px-2 py-0.5 rounded uppercase tracking-wider">Oferta</span>
                                    </div>
                                @else
                                    <span class="text-4xl font-black text-slate-900 tracking-tighter">$ {{ number_format($user_price, 2, ',', '.') }}</span>
                                @endif

                                @if($product->qtty_unit > 1)
                                    <span class="text-xs font-bold text-slate-500 mt-1 flex items-center gap-1"><x-icon name="o-tag" class="w-3 h-3" /> Precio un.: $ {{ number_format($this->unitPrice(), 2, ',', '.') }}</span>
                                @endif
                            </div>
                        @endif
                    </div>
                @endif

                <!-- Acciones de Compra -->
                @if(!Auth::guest())
                    @if($this->canBuy())
                        <div x-data="{
                            qtty: @entangle('qtty'),
                            step: {{ $product->qtty_package }},
                            add(n) { this.qtty = parseInt(this.qtty) + n },
                            sub(n) { if(this.qtty > n) this.qtty -= n; else this.qtty = 1 }
                        }" class="flex flex-wrap items-center gap-3">
                            
                            <!-- Selector Cantidad -->
                            <div class="flex items-center border border-slate-300 rounded-lg h-12 overflow-hidden w-32 bg-white shrink-0">
                                <button @click="sub(1)" class="w-10 h-full flex items-center justify-center bg-slate-50 hover:bg-slate-100 text-slate-600 transition-colors font-bold text-xl border-r border-slate-200">-</button>
                                <input type="number" x-model="qtty" class="w-full h-full text-center font-bold text-slate-800 bg-transparent border-none focus:ring-0 outline-none p-0" min="1">
                                <button @click="add(1)" class="w-10 h-full flex items-center justify-center bg-slate-50 hover:bg-slate-100 text-slate-600 transition-colors font-bold text-xl border-l border-slate-200">+</button>
                            </div>
                            
                            <!-- Botón Agregar -->
                            <button wire:click="buy({{ $product->id }})"
                                onclick="flyToCart('detail-img-{{ $product->id }}')"
                                class="flex-1 bg-red-600 hover:bg-red-700 text-white font-bold h-12 rounded-lg flex items-center justify-center gap-2 transition-colors shadow-sm min-w-[200px]">
                                <div wire:loading.remove wire:target="buy"><x-icon name="o-shopping-cart" class="w-5 h-5" /></div>
                                <div wire:loading wire:target="buy"><x-icon name="o-arrow-path" class="w-5 h-5 animate-spin" /></div>
                                AGREGAR AL CARRITO
                            </button>
                            
                            <!-- Corazón Favoritos -->
                            <button class="w-12 h-12 flex items-center justify-center border border-slate-300 rounded-lg text-slate-400 hover:text-red-500 hover:bg-slate-50 transition-colors shrink-0">
                                <x-icon name="o-heart" class="w-6 h-6" />
                            </button>
                        </div>

                        @if($product->qtty_package > 1)
                            <div class="mt-3 text-xs text-slate-500 font-medium">
                                <x-icon name="o-cube" class="w-4 h-4 inline mr-1" /> Venta por bulto: {{ $product->qtty_package }} unidades.
                            </div>
                        @endif
                    @endif
                @else
                    <div class="p-6 bg-blue-50 rounded-2xl border border-blue-100 text-center">
                        <x-icon name="o-lock-closed" class="w-8 h-8 text-blue-400 mx-auto mb-2" />
                        <p class="text-blue-700 font-bold mb-4">{{ $this->guestMessage() }}</p>
                        <a href="/login" class="inline-block px-6 py-2 bg-blue-600 text-white font-bold rounded-lg hover:bg-blue-700 transition-colors shadow-sm">Ingresar ahora</a>
                    </div>
                @endif
            </div>
        </div>

        <!-- Pestañas (Tabs) -->
        <div x-data="{ tab: 'description' }" class="bg-white p-6 md:p-8 rounded-2xl shadow-sm border border-slate-100 mb-12">
            <div class="flex flex-wrap gap-6 border-b border-slate-200 mb-6">
                <button @click="tab = 'description'" :class="tab === 'description' ? 'border-b-2 border-blue-600 text-blue-600 font-bold pb-3 text-sm' : 'border-b-2 border-transparent text-slate-500 hover:text-slate-800 hover:border-slate-300 font-bold pb-3 text-sm transition-colors'">Descripción</button>
                <button @click="tab = 'specs'" :class="tab === 'specs' ? 'border-b-2 border-blue-600 text-blue-600 font-bold pb-3 text-sm' : 'border-b-2 border-transparent text-slate-500 hover:text-slate-800 hover:border-slate-300 font-bold pb-3 text-sm transition-colors'">Ficha técnica</button>
                <button @click="tab = 'models'" :class="tab === 'models' ? 'border-b-2 border-blue-600 text-blue-600 font-bold pb-3 text-sm' : 'border-b-2 border-transparent text-slate-500 hover:text-slate-800 hover:border-slate-300 font-bold pb-3 text-sm transition-colors'">Modelos compatibles</button>
                <button @click="tab = 'questions'" :class="tab === 'questions' ? 'border-b-2 border-blue-600 text-blue-600 font-bold pb-3 text-sm' : 'border-b-2 border-transparent text-slate-500 hover:text-slate-800 hover:border-slate-300 font-bold pb-3 text-sm transition-colors'">Consultas</button>
            </div>
            
            <!-- Tab contents -->
            <div x-show="tab === 'description'" class="prose prose-sm md:prose-base max-w-none text-slate-600">
                {!! $product->description_html ?: '<p>No hay descripción detallada disponible para este producto.</p>' !!}
            </div>
            <div x-show="tab === 'specs'" class="prose prose-sm md:prose-base max-w-none text-slate-600" style="display: none;">
                <p>Ficha técnica no disponible.</p>
            </div>
            <div x-show="tab === 'models'" class="prose prose-sm md:prose-base max-w-none text-slate-600" style="display: none;">
                <p>Información de modelos compatibles no disponible.</p>
            </div>
            <div x-show="tab === 'questions'" class="prose prose-sm md:prose-base max-w-none text-slate-600" style="display: none;">
                <p>Sección de consultas (próximamente).</p>
            </div>
        </div>

    @else
        <div class="bg-white p-12 rounded-3xl shadow-xl text-center border-2 border-dashed border-gray-200 mb-8">
            <x-icon name="o-face-frown" class="w-16 h-16 text-gray-300 mx-auto mb-4" />
            <h1 class="text-2xl font-black text-gray-400 uppercase">Producto no disponible</h1>
            <a href="/" class="inline-block mt-6 px-6 py-2 bg-blue-600 text-white font-bold rounded-lg hover:bg-blue-700 transition-colors">Volver a la tienda</a>
        </div>
    @endif

    <!-- Productos Relacionados -->
    @island('related-products', lazy: true)
        @placeholder
            <div class="mt-16">
                <h2 class="text-2xl font-black text-gray-900 mb-8 px-2 flex items-center gap-3">
                    <span class="w-2 h-8 bg-blue-600 rounded-full"></span>
                    PRODUCTOS RELACIONADOS
                </h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                    @for ($i = 0; $i < 4; $i++)
                        <div class="h-64 bg-white shadow-md rounded-2xl animate-pulse p-4 flex flex-col justify-between border border-gray-100">
                            <div class="w-full h-36 bg-gray-200 rounded-xl"></div>
                            <div class="h-4 bg-gray-200 rounded w-3/4"></div>
                            <div class="h-4 bg-gray-200 rounded w-1/2"></div>
                        </div>
                    @endfor
                </div>
            </div>
        @endplaceholder

        @php($relatedItems = $this->getRelatedProducts())
        @if(count($relatedItems) > 0)
            <div class="mt-16">
                <h2 class="text-2xl font-black text-gray-900 mb-8 px-2 flex items-center gap-3">
                    <span class="w-2 h-8 bg-blue-600 rounded-full"></span>
                    PRODUCTOS RELACIONADOS
                </h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                    @foreach ($relatedItems as $rel_product)
                        <livewire:web-product-card :product="$rel_product" :key="'rel-'.$rel_product->id" />
                    @endforeach
                </div>
            </div>
        @endif
    @endisland
</div>
