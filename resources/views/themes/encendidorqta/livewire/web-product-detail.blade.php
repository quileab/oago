<?php
use App\Models\Product;
use App\Services\ProductSearchService;
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
            $this->related_products = app(ProductSearchService::class)
                ->searchRelatedProducts($this->product, 12);
        }
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
                @php
                    $primaryMedia = $this->primaryMedia();
                    $isCibat = stripos($product->brand ?? '', 'CIBAT') !== false || stripos($product->description ?? '', 'BATERIA') !== false;
                    $fallbackLogo = $isCibat ? asset('themes/encendidorqta/cibatlogo.png') : asset('themes/encendidorqta/erlogo.png');
                @endphp

                <div x-data="{
                    activeMedia: {{ json_encode($primaryMedia) }},
                    mediaList: {{ json_encode($this->media) }},
                    getProxiedUrl(url) {
                        if (!url) return '{{ $fallbackLogo }}';
                        if (url.startsWith('http') && !url.includes(window.location.hostname) && !url.includes('localhost') && !url.includes('127.0.0.1')) {
                            return '{{ route('proxy.image') }}?url=' + encodeURIComponent(url) + '&return_404=1';
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
                    
                    <div class="w-full aspect-square flex items-center justify-center border border-slate-200 rounded-xl mb-4 relative overflow-hidden" 
                         :class="activeMedia.isFallback || !activeMedia.url ? 'bg-slate-100' : 'bg-white'"
                         id="detail-img-{{ $product->id }}">
                        @if($product->featured)
                            <div class="absolute top-4 left-4 z-10">
                                <span class="px-3 py-1 text-[10px] font-black text-white bg-red-600 rounded-br-lg rounded-tl-xl shadow-sm uppercase tracking-wider">DESTACADO</span>
                            </div>
                        @endif

                        <template x-if="activeMedia.type === 'image'">
                            <!-- Contenedor con tamaño limitado para prevenir placeholders enormes -->
                            <div class="w-full h-full flex items-center justify-center relative p-4">
                                
                                <!-- Normal Product Image -->
                                <template x-if="!activeMedia.isFallback && activeMedia.url">
                                    <img :src="getProxiedUrl(activeMedia.url)"
                                        class="max-w-full max-h-full object-contain transition-transform duration-700 hover:scale-105 {{ $product->stock == 0 ? 'opacity-40 grayscale' : '' }}"
                                        x-on:error="activeMedia.isFallback = true;">
                                </template>

                                <!-- Fallback State -->
                                <template x-if="activeMedia.isFallback || !activeMedia.url">
                                    <div class="w-full h-full flex flex-col items-center justify-center">
                                        <img src="{{ $fallbackLogo }}" alt="Placeholder" class="w-2/3 max-w-[160px] h-auto brightness-0 opacity-20">
                                        <span class="absolute bottom-8 left-0 right-0 text-center text-[11px] font-bold text-gray-400 uppercase tracking-widest">IMAGEN NO DISPONIBLE</span>
                                    </div>
                                </template>
                            </div>
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
                                <button @click="activeMedia = item; activeMedia.isFallback = item.isFallback"
                                    class="w-16 h-16 rounded-lg overflow-hidden border-2 transition-all p-0 flex-shrink-0 relative"
                                    :class="[
                                        activeMedia.url === item.url ? 'border-blue-600 shadow-sm' : 'border-slate-200 hover:border-slate-400 opacity-70 hover:opacity-100',
                                        item.isFallback || !item.url ? 'bg-gray-100' : 'bg-white'
                                    ]">

                                    <template x-if="!item.isFallback && item.url">
                                        <img :src="getProxiedUrl(item.thumb)" class="w-full h-full object-contain rounded-md" x-on:error="item.isFallback = true; activeMedia.isFallback = true;">
                                    </template>

                                    <template x-if="item.isFallback || !item.url">
                                        <div class="w-full h-full flex items-center justify-center p-2">
                                            <img src="{{ $fallbackLogo }}" class="w-full h-full object-contain brightness-0 opacity-20">
                                        </div>
                                    </template>

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
                        }" class="space-y-4">
                            
                            <div class="flex items-stretch h-14 shadow-sm rounded-xl overflow-hidden border-2 border-slate-200">
                                <button @click="sub(1)" class="w-20 bg-slate-100 hover:bg-slate-200 text-2xl font-bold text-slate-700 transition-colors border-r-2 border-slate-200">-</button>
                                @if($product->qtty_package > 1)
                                    <button @click="sub(step)" class="w-24 bg-blue-50 hover:bg-blue-100 text-xs font-black text-blue-700 border-r-2 border-slate-200">-{{ $product->qtty_package }}</button>
                                @endif

                                <input type="number" x-model="qtty" class="flex-grow text-center text-xl font-black bg-white focus:outline-none" min="1">

                                @if($product->qtty_package > 1)
                                    <button @click="add(step)" class="w-24 bg-blue-50 hover:bg-blue-100 text-xs font-black text-blue-700 border-l-2 border-slate-200">+{{ $product->qtty_package }}</button>
                                @endif
                                <button @click="add(1)" class="w-20 bg-slate-100 hover:bg-slate-200 text-2xl font-bold text-slate-700 transition-colors border-l-2 border-slate-200">+</button>
                            </div>

                            <x-button label="AGREGAR AL CARRITO" icon="o-shopping-cart"
                                class="w-full h-14 btn-primary text-lg font-black shadow-xl shadow-primary/30"
                                wire:click="buy({{ $product->id }})"
                                onclick="flyToCart('detail-img-{{ $product->id }}')"
                                spinner="buy" />
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
    @if(count($related_products) > 0)
        <div class="mt-16 z-10 relative">
            <div class="flex items-center gap-3 mb-6">
                <!-- Hexagon -->
                <div class="w-8 h-8 bg-gray-400 shrink-0" style="clip-path: polygon(25% 0%, 75% 0%, 100% 50%, 75% 100%, 25% 100%, 0% 50%);"></div>
                
                <h2 class="text-xl md:text-3xl font-black text-gray-400 tracking-wide uppercase">
                    PRODUCTOS RELACIONADOS
                </h2>
                
                <div class="ml-4 text-xs md:text-sm text-gray-400 text-left leading-tight font-bold hidden sm:block">
                    Productos que también<br>podrían interesarte.
                </div>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                @foreach ($related_products as $rel_product)
                    <livewire:web-product-card :product="$rel_product" :key="'rel-'.$rel_product->id" />
                @endforeach
            </div>
        </div>
    @endif
</div>
