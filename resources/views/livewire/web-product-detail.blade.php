<?php
use Livewire\Volt\Component;
use App\Models\Product;
use Illuminate\Support\Str;
use Mary\Traits\Toast;

new class extends Component {
    use Toast;
    public $product;
    public $qtty = 1;
    public $related_products = [];
    public $user_price = 0;
    public $offer_price = 0;

    public function mount(Product $prod_id)
    {
        $this->product = app(\App\Services\ProductSearchService::class)
            ->searchProducts(['id' => $prod_id->id], 1);

        if (!$this->product) {
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
            $this->user_price = $this->product->user_price ?? current_user()?->getProductPrice($this->product) ?? 0;
            $this->offer_price = $this->product->offer_price ?? 0;
            $this->qtty = $this->product->qtty_package;
            $this->related_products = app(\App\Services\ProductSearchService::class)
                ->searchRelatedProducts($this->product, 12);
        }
    }

    public function buy($productId)
    {
        $this->dispatch('addToCart', product: $productId, quantity: (int)$this->qtty);
        
        // Resetear cantidad al valor por bulto tras agregar
        $this->qtty = $this->product->qtty_package ?? 1;
    }
}; ?>

<div class="max-w-7xl mx-auto p-4 lg:p-6">
    <!-- Botón Volver -->
    <div class="mb-6">
        <x-button label="Volver al catálogo" icon="o-arrow-left" class="btn-sm btn-ghost text-blue-600 font-bold"
            onclick="window.history.back()" />
    </div>

    @if ($product && $product->id !== 0)
        <div class="bg-white rounded-2xl shadow-xl overflow-hidden border border-gray-100">
            <div class="grid grid-cols-1 md:grid-cols-2">
                <!-- Columna Imagen -->
                <div class="relative bg-gray-50 flex items-center justify-center p-8 border-b md:border-b-0 md:border-r border-gray-100">
                    @if($product->featured)
                        <div class="absolute top-4 left-4 z-10">
                            <span class="px-3 py-1 text-xs font-black text-white bg-red-600 rounded-full shadow-lg">
                                PRODUCTO DESTACADO ⭐
                            </span>
                        </div>
                    @endif

                    <div class="relative group" id="detail-img-{{ $product->id }}">
                        <x-image-proxy url="{{ $product->image_url }}" 
                            class="max-h-[400px] w-auto object-contain transition-transform duration-700 group-hover:scale-105 {{ $product->stock == 0 ? 'opacity-40 grayscale' : '' }}" />
                        
                        @if($product->stock == 0)
                            <div class="absolute inset-0 flex items-center justify-center">
                                <span class="px-6 py-2 bg-white/90 backdrop-blur text-gray-500 font-black rounded-xl border-2 border-gray-200 shadow-xl uppercase tracking-[0.2em]">Agotado</span>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Columna Información -->
                <div class="p-6 lg:p-10 flex flex-col">
                    <!-- Tags -->
                    <div class="flex flex-wrap gap-2 mb-4">
                        @foreach (array_filter(explode('|', $product->tags)) as $tag)
                            <span class="px-2.5 py-1 text-[10px] font-black bg-amber-600 text-white rounded-md shadow-sm uppercase tracking-wider">
                                {{ $tag }}
                            </span>
                        @endforeach
                    </div>

                    <h1 class="text-3xl font-black text-gray-900 leading-tight mb-2">
                        {{ $product->description }}
                    </h1>

                    <div class="flex items-center gap-4 mb-4">
                        <span class="text-sm font-bold text-blue-600 bg-blue-50 px-3 py-1 rounded-lg border border-blue-100">
                            {{ $product->brand }}
                        </span>
                        <span class="text-sm text-slate-400 font-mono font-medium tracking-tighter">REF: {{ $product->id }}</span>
                    </div>

                    <!-- Descripción HTML (Movida debajo de la marca) -->
                    <div class="mb-8 prose prose-slate max-w-none text-gray-600 leading-relaxed text-sm">
                        {!! $product->description_html !!}
                    </div>

                    <!-- Bloque Precios -->
                    @if(!Auth::guest())
                        <div class="bg-slate-50 p-6 rounded-2xl mb-8 border border-slate-100">
                            <div class="flex flex-col">
                                @if($offer_price > 0)
                                    <span class="text-sm text-red-500 line-through font-bold mb-1">
                                        Precio regular: $ {{ number_format($user_price, 2, ',', '.') }}
                                    </span>
                                    <div class="flex items-baseline gap-2">
                                        <span class="text-5xl font-black text-green-700 tracking-tighter">
                                            $ {{ number_format($offer_price, 2, ',', '.') }}
                                        </span>
                                        <span class="text-xs font-bold text-white bg-green-600 px-2 py-0.5 rounded uppercase">Oferta</span>
                                    </div>
                                @else
                                    <span class="text-5xl font-black text-green-700 tracking-tighter">
                                        $ {{ number_format($user_price, 2, ',', '.') }}
                                    </span>
                                @endif
                                
                                @if($product->qtty_unit > 1)
                                    <span class="text-sm font-bold text-slate-500 mt-2 flex items-center gap-1">
                                        <x-icon name="o-tag" class="w-4 h-4" />
                                        Precio por unidad: $ {{ number_format(($offer_price > 0 ? $offer_price : $user_price) / $product->qtty_unit, 2, ',', '.') }}
                                    </span>
                                @endif
                            </div>
                        </div>

                        <!-- Disponibilidad -->
                        <div class="flex justify-between items-center mb-8 px-2">
                            <div>
                                @if($product->stock < 10)
                                    <span class="text-red-600 font-black text-sm px-3 py-1.5 bg-red-50 rounded-full border border-red-100">
                                        <x-icon name="s-bolt" class="w-4 h-4 inline mr-1" /> STOCK BAJO
                                    </span>
                                @elseif($product->stock < 100)
                                    <span class="text-amber-600 font-black text-sm px-3 py-1.5 bg-amber-50 rounded-full border border-amber-100">
                                        <x-icon name="s-bolt" class="w-4 h-4 inline mr-1" /> STOCK MEDIO
                                    </span>
                                @else
                                    <span class="text-green-600 font-black text-sm px-3 py-1.5 bg-green-50 rounded-full border border-green-100">
                                        <x-icon name="s-check-circle" class="w-4 h-4 inline mr-1" /> EN STOCK
                                    </span>
                                @endif
                            </div>
                            <div class="text-slate-600 font-black bg-white px-4 py-1.5 rounded-full border border-slate-200 shadow-sm text-sm">
                                <x-icon name="o-cube" class="w-4 h-4 inline mr-1" /> Venta por bulto: {{ $product->qtty_package }} un.
                            </div>
                        </div>

                        <!-- Acciones de Compra -->
                        @if($product->stock > 0 && !in_array(Auth::user()->role->value, ['none', 'guest']))
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
                        @endif
                    @else
                        <div class="mt-8 p-6 bg-blue-50 rounded-2xl border border-blue-100 text-center">
                            <x-icon name="o-lock-closed" class="w-8 h-8 text-blue-400 mx-auto mb-2" />
                            <p class="text-blue-700 font-bold">Inicie sesión para ver precios y comprar</p>
                            <x-button label="Ingresar ahora" link="/login" class="mt-4 btn-sm btn-primary" />
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @else
        <div class="bg-white p-12 rounded-3xl shadow-xl text-center border-2 border-dashed border-gray-200">
            <x-icon name="o-face-frown" class="w-16 h-16 text-gray-300 mx-auto mb-4" />
            <h1 class="text-2xl font-black text-gray-400 uppercase">Producto no disponible</h1>
            <x-button label="Volver a la tienda" link="/" class="mt-6 btn-primary" />
        </div>
    @endif

    <!-- Productos Relacionados -->
    @if(count($related_products) > 0)
        <div class="mt-16">
            <h2 class="text-2xl font-black text-gray-900 mb-8 px-2 flex items-center gap-3">
                <span class="w-2 h-8 bg-blue-600 rounded-full"></span>
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
