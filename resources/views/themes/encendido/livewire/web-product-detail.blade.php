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
                'image_url' => asset('imgs/Logos/logo_er50.jpg'),
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
            $this->tags = array_values(array_filter(explode('|', $this->product->tags)));
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
        return $this->product->stock > 0 && ! Auth::guest() && ! in_array(Auth::user()->role->value, ['none', 'guest']) && $basePrice > 0;
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
    <div class="mb-6">
        <x-button label="Volver al catálogo" icon="o-arrow-left" class="btn-sm btn-ghost font-bold text-neutral-300 hover:text-white" onclick="window.history.back()" />
    </div>

    @if ($product && $product->id !== 0)
        <div class="glass-panel ring-1 ring-white/10 rounded-lg overflow-hidden shadow-2xl">
            <div class="grid grid-cols-1 md:grid-cols-2">
                {{-- Columna Imagen --}}
                <div class="relative bg-neutral-900/60 flex flex-col items-center justify-center p-8 border-b md:border-b-0 md:border-r border-white/5">
                    @if($product->featured)
                        <div class="absolute top-4 left-4 z-10">
                            <span class="text-[10px] font-black px-2.5 py-1 text-white bg-gradient-to-r from-primary to-rose-600 rounded-br-xl shadow-[0_0_12px_rgba(239,68,68,0.5)] uppercase tracking-wider flex items-center gap-1">
                                <x-icon name="s-star" class="w-3 h-3 inline-block" /> Destacado
                            </span>
                        </div>
                    @endif

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
                    }" class="w-full flex flex-col">

                        <div class="relative group flex items-center justify-center min-h-[300px] md:min-h-[400px]" id="detail-img-{{ $product->id }}">
                            <template x-if="activeMedia.type === 'image'">
                                <img :src="getProxiedUrl(activeMedia.url)"
                                    class="max-h-[400px] w-auto object-contain transition-transform duration-700 group-hover:scale-105 {{ $product->stock == 0 ? 'opacity-40 grayscale' : '' }}"
                                    onerror="this.src='/imgs/fallback.webp'">
                            </template>

                            <template x-if="activeMedia.type === 'video'">
                                <div class="w-full aspect-video rounded-lg overflow-hidden shadow-2xl border border-white/10" :key="activeMedia.url">
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
                                <div class="absolute inset-0 flex items-center justify-center pointer-events-none backdrop-blur-sm bg-neutral-950/50">
                                    <span class="px-6 py-2 bg-red-600/90 text-white font-black rounded-lg border border-red-400/30 shadow-xl uppercase tracking-[0.2em]">Agotado</span>
                                </div>
                            @endif
                        </div>

                        <template x-if="mediaList.length > 1">
                            <div class="flex flex-wrap gap-3 mt-8 justify-center">
                                <template x-for="(item, index) in mediaList" :key="index">
                                    <button @click="activeMedia = item"
                                        class="relative w-16 h-16 rounded-lg overflow-hidden border-2 transition-all p-0.5 bg-neutral-900 shadow-sm flex-shrink-0"
                                        :class="activeMedia.url === item.url ? 'border-primary scale-110 shadow-md z-10' : 'border-white/10 opacity-60 hover:opacity-100 hover:border-white/30'">
                                        <img :src="getProxiedUrl(item.thumb)" class="w-full h-full object-cover rounded-md" onerror="this.src='/imgs/fallback.webp'">
                                        <template x-if="item.type === 'video'">
                                            <div class="absolute inset-0 flex items-center justify-center bg-black/30">
                                                <div class="bg-white/90 rounded-full p-1 shadow-sm">
                                                    <x-icon name="s-play" class="w-5 h-5 text-red-600" />
                                                </div>
                                            </div>
                                        </template>
                                    </button>
                                </template>
                            </div>
                        </template>
                    </div>
                </div>

                {{-- Columna Info --}}
                <div class="p-6 lg:p-10 flex flex-col bg-neutral-900/40">
                    {{-- Tags --}}
                    <div class="flex flex-wrap gap-2 mb-4">
                        @foreach ($this->tags as $tag)
                            <span class="px-2.5 py-1 text-[10px] font-black bg-amber-500/20 text-amber-300 border border-amber-500/30 rounded-md shadow-[0_0_8px_rgba(245,158,11,0.2)] uppercase tracking-wider">{{ $tag }}</span>
                        @endforeach
                    </div>

                    <h1 class="text-2xl lg:text-3xl font-black text-neutral-100 leading-tight mb-3">{{ $product->description }}</h1>

                    <div class="flex items-center gap-3 mb-6">
                        <span class="text-sm font-bold text-primary-content bg-primary/20 border border-primary/30 px-3 py-1 rounded-md">{{ $product->brand }}</span>
                        <span class="text-sm text-neutral-400 font-mono font-medium tracking-tighter">REF: {{ $product->id }}</span>
                    </div>

                    <div class="mb-6 text-neutral-300/80 leading-relaxed text-sm border-t border-white/5 pt-4">
                        {!! strip_tags($product->description_html, '<p><br><b><strong><i><em><ul><ol><li><a><span><div>') !!}
                    </div>

                    {{-- Precios --}}
                    @if($this->showPrices())
                        <div class="bg-neutral-950/60 p-5 rounded-lg mb-6 border border-white/10">
                            @if(($offer_price > 0 ? $offer_price : $user_price) <= 0)
                                <span class="text-2xl font-black text-neutral-500 uppercase tracking-wider">Muy pronto</span>
                            @else
                                <div class="flex flex-col">
                                    @if($offer_price > 0)
                                        <span class="text-sm text-rose-500 line-through font-bold mb-1">Precio regular: $ {{ number_format($user_price, 2, ',', '.') }}</span>
                                        <div class="flex items-baseline gap-3">
                                            <span class="text-5xl font-black text-emerald-400 tracking-tighter font-technical">$ {{ number_format($offer_price, 2, ',', '.') }}</span>
                                            <span class="text-xs font-bold text-white bg-emerald-600/80 px-2 py-0.5 rounded-md uppercase">Oferta</span>
                                        </div>
                                    @else
                                        <span class="text-5xl font-black text-emerald-400 tracking-tighter font-technical">$ {{ number_format($user_price, 2, ',', '.') }}</span>
                                    @endif

                                    @if($product->qtty_unit > 1)
                                        <span class="text-sm font-bold text-neutral-400 mt-2 flex items-center gap-1">
                                            <x-icon name="o-tag" class="w-4 h-4" /> Precio por unidad: $ {{ number_format($this->unitPrice(), 2, ',', '.') }}
                                        </span>
                                    @endif
                                </div>
                            @endif
                        </div>
                    @endif

                    {{-- Stock & Bulto --}}
                    @if(!Auth::guest())
                        <div class="flex justify-between items-center mb-6 px-1">
                            @php($stockBadge = $this->stockBadge())
                            <span class="font-black text-sm px-3 py-1.5 rounded-md border {{ $stockBadge['class'] }}">
                                <x-icon :name="$stockBadge['icon']" class="w-4 h-4 inline mr-1" /> {{ $stockBadge['label'] }}
                            </span>
                            <span class="text-neutral-300 font-black bg-neutral-900/80 px-4 py-1.5 rounded-md border border-neutral-700 text-sm">
                                <x-icon name="o-cube" class="w-4 h-4 inline mr-1" /> Bulto: {{ $product->qtty_package }} un.
                            </span>
                        </div>

                        @if($this->canBuy())
                            <div x-data="{
                                qtty: @entangle('qtty'),
                                step: {{ $product->qtty_package }},
                                add(n) { this.qtty = parseInt(this.qtty) + n },
                                sub(n) { if(this.qtty > n) this.qtty -= n; else this.qtty = 1 }
                            }" class="space-y-4">

                                <div class="flex items-stretch h-14 rounded-lg overflow-hidden border border-neutral-700 focus-within:border-primary focus-within:ring-1 focus-within:ring-primary/50 transition-all bg-neutral-900/90">
                                    <button @click="sub(1)" class="w-20 bg-neutral-800 hover:bg-rose-500/20 hover:text-rose-400 text-2xl font-bold text-neutral-300 transition-colors border-r border-neutral-700">-</button>
                                    @if($product->qtty_package > 1)
                                        <button @click="sub(step)" class="w-24 bg-neutral-900 hover:bg-rose-500/10 text-xs font-black text-neutral-400 hover:text-rose-400 border-r border-neutral-700">-{{ $product->qtty_package }}</button>
                                    @endif

                                    <input type="number" x-model="qtty" class="flex-grow text-center text-xl font-black bg-transparent text-white focus:outline-none font-technical" min="1">

                                    @if($product->qtty_package > 1)
                                        <button @click="add(step)" class="w-24 bg-neutral-900 hover:bg-emerald-500/10 text-xs font-black text-neutral-400 hover:text-emerald-400 border-l border-neutral-700">+{{ $product->qtty_package }}</button>
                                    @endif
                                    <button @click="add(1)" class="w-20 bg-neutral-800 hover:bg-emerald-500/20 hover:text-emerald-400 text-2xl font-bold text-neutral-300 transition-colors border-l border-neutral-700">+</button>
                                </div>

                                <x-button label="AGREGAR AL CARRITO" icon="o-shopping-cart"
                                    class="w-full h-14 btn-primary text-lg font-black shadow-xl shadow-primary/30 rounded-lg"
                                    wire:click="buy({{ $product->id }})"
                                    onclick="flyToCart('detail-img-{{ $product->id }}')"
                                    spinner="buy" />
                            </div>
                        @endif
                    @else
                        <div class="mt-8 p-6 bg-neutral-950/60 rounded-lg border border-white/10 text-center">
                            <x-icon name="o-lock-closed" class="w-8 h-8 text-amber-400 mx-auto mb-2" />
                            <p class="text-neutral-300 font-bold">{{ $this->guestMessage() }}</p>
                            <x-button label="Ingresar ahora" link="/login" class="mt-4 btn-sm btn-primary" />
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @else
        <div class="glass-panel p-12 rounded-lg shadow-xl text-center border border-white/10">
            <x-icon name="o-face-frown" class="w-16 h-16 text-neutral-600 mx-auto mb-4" />
            <h1 class="text-2xl font-black text-neutral-400 uppercase">Producto no disponible</h1>
            <x-button label="Volver a la tienda" link="/" class="mt-6 btn-primary" />
        </div>
    @endif

    {{-- Productos Relacionados --}}
    @if(count($related_products) > 0)
        <div class="mt-16">
            <h2 class="text-2xl font-black text-neutral-100 mb-8 px-2 flex items-center gap-3">
                <span class="w-2 h-8 bg-primary rounded-full"></span>
                PRODUCTOS RELACIONADOS
            </h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                @foreach ($related_products as $rel_product)
                    <livewire:web-product-card :product="$rel_product" :key="'rel-'.$rel_product->id" />
                @endforeach
            </div>
        </div>
    @endif
</div>

