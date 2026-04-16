<div wire:key="prod-card-{{ $product->id }}" 
    class="card bg-white shadow-md shadow-slate-400 overflow-hidden hover:shadow-lg transition-shadow duration-300 flex flex-col h-full">
    
    <a href="./?product_id={{ $product->id }}" class="flex-grow">
        <div class="grid grid-cols-2 h-full">
            <div class="relative bg-gray-50 flex items-center justify-center overflow-hidden">
                @if($product->featured)
                    <div class="absolute top-0 left-0 z-10">
                        <span class="text-[10px] font-bold px-2 py-1 text-white bg-red-600 rounded-br-lg shadow-sm">
                            DESTACADO ⭐
                        </span>
                    </div>
                @endif

                <div class="aspect-square w-full flex items-center justify-center p-2">
                    <x-image-proxy url="{{ $product->image_url }}"
                        class="max-h-32 w-auto object-contain transition-transform duration-500 hover:scale-110 {{ $product->stock == 0 ? 'grayscale opacity-50' : '' }}" />
                </div>
                
                @if($product->stock == 0)
                    <div class="absolute inset-0 flex items-center justify-center bg-white/40 backdrop-blur-[1px]">
                        <span class="bg-gray-800 text-white text-[10px] font-bold px-2 py-1 rounded-full uppercase tracking-wider">Sin Stock</span>
                    </div>
                @endif
            </div>

            <div class="p-3 bg-white flex flex-col">
                <div class="flex flex-wrap gap-1 mb-1">
                    @foreach (array_filter(explode('|', $product->tags)) as $tag)
                        <span class="px-1.5 py-0.5 text-[10px] font-bold bg-amber-100 text-amber-800 rounded uppercase border border-amber-200">
                            {{ $tag }}
                        </span>
                    @endforeach
                </div>
                
                <h2 class="text-sm font-bold text-gray-900 line-clamp-2 leading-tight mb-1" title="{{ $product->description }}">
                    {{ $product->description }}
                </h2>
                <p class="text-[11px] text-gray-500 mb-2 italic">Marca: {{ $product->brand }}</p>
                
                <div class="mt-auto text-[11px] leading-tight text-gray-600">
                    {!! Str::limit(strip_tags($product->description_html), 60) !!}
                </div>
            </div>
        </div>
    </a>

    @if(Auth::guest())
        <div class="p-2 bg-slate-50 border-t border-slate-100 text-center text-[11px] text-slate-500 font-medium">
            <x-icon name="o-lock-closed" class="w-3 h-3 inline mr-1" /> Regístrese para ver precios
        </div>
    @else
        <div class="p-3 bg-white border-t border-slate-100 grid grid-cols-2 items-end">
            <div class="flex flex-col">
                @if($product->offer_price > 0)
                    <span class="text-[10px] text-red-500 line-through font-medium">
                        $ {{ number_format($product->user_price, 2, ',', '.') }}
                    </span>
                    <span class="text-xl font-black text-green-700 leading-none">
                        $ {{ number_format($product->offer_price, 2, ',', '.') }}
                    </span>
                @else
                    <span class="text-xl font-black text-green-700 leading-none">
                        $ {{ number_format($product->user_price, 2, ',', '.') }}
                    </span>
                @endif
                
                @if($product->qtty_unit > 1)
                    <span class="text-[10px] font-semibold text-slate-400 mt-1">
                        $ {{ number_format(($product->offer_price > 0 ? $product->offer_price : $product->user_price) / $product->qtty_unit, 2, ',', '.') }} x un.
                    </span>
                @endif
            </div>

            <div class="text-[10px] text-right text-slate-400 space-y-0.5">
                <div class="font-mono bg-slate-100 inline-block px-1 rounded">REF: {{ $product->id }}</div>
                <div>
                    @if($product->stock < 10)
                        <span class="text-red-600 font-bold"><x-icon name="s-bolt" class="w-3 h-3 inline" /> Stock Bajo</span>
                    @elseif($product->stock < 100)
                        <span class="text-amber-600 font-bold"><x-icon name="s-bolt" class="w-3 h-3 inline" /> Stock Medio</span>
                    @else
                        <span class="text-green-600 font-bold"><x-icon name="s-check-circle" class="w-3 h-3 inline" /> En stock</span>
                    @endif
                </div>
                <div class="flex items-center justify-end gap-1">
                    <x-icon name="o-cube" class="w-3 h-3" /> Bulto: {{ $product->qtty_package }}
                </div>
            </div>
        </div>

        <div class="p-2 bg-slate-50 border-t border-slate-100">
            @if($product->stock > 0 && !in_array(Auth::user()->role->value, ['none', 'guest']))
                <div x-data="{ 
                    qtty: @entangle('qtty'),
                    step: {{ $product->qtty_package }},
                    add(n) { this.qtty = parseInt(this.qtty) + n },
                    sub(n) { if(this.qtty > n) this.qtty -= n; else this.qtty = 1 }
                }" class="space-y-2">
                    
                    <div class="flex items-stretch h-9 shadow-sm rounded-lg overflow-hidden border border-slate-300">
                        <button @click="sub(1)" class="w-10 bg-white hover:bg-slate-100 text-slate-600 transition-colors border-r">-</button>
                        @if($product->qtty_package > 1)
                            <button @click="sub(step)" class="w-12 bg-slate-100 hover:bg-slate-200 text-[10px] font-bold text-slate-700 border-r">-{{ $product->qtty_package }}</button>
                        @endif
                        
                        <input type="number" x-model="qtty" class="flex-grow text-center text-sm font-bold bg-white focus:outline-none" min="1">
                        
                        @if($product->qtty_package > 1)
                            <button @click="add(step)" class="w-12 bg-slate-100 hover:bg-slate-200 text-[10px] font-bold text-slate-700 border-l">+{{ $product->qtty_package }}</button>
                        @endif
                        <button @click="add(1)" class="w-10 bg-white hover:bg-slate-100 text-slate-600 transition-colors border-l">+</button>
                    </div>

                    <div class="grid grid-cols-2 gap-2">
                        <x-button label="Similares" icon="o-magnifying-glass"
                            class="btn-sm btn-outline text-slate-500 border-slate-300 hover:bg-slate-100"
                            wire:click="searchSimilar({{$product}})" />
                        
                        <x-button label="AGREGAR" icon="o-shopping-cart"
                            class="btn-sm btn-primary shadow-md shadow-primary/20"
                            wire:click="buy({{ $product->id }})"
                            spinner="buy" />
                    </div>
                </div>
            @endif
            
            @if(!empty($cart) && isset($cart[$product->id]))
                <div class="mt-2 flex items-center justify-center gap-1 text-[10px] font-bold text-green-600 bg-green-50 py-1 rounded">
                    <x-icon name="o-check-circle" class="w-3 h-3" />
                    PRODUCTO EN CARRITO ({{ $cart[$product->id]['quantity'] }})
                </div>
            @endif
        </div>
    @endif
</div>
