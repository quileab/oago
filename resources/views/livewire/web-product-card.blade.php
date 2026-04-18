<div wire:key="prod-card-{{ $product->id }}" 
    class="card bg-white shadow-md shadow-slate-400 overflow-hidden hover:shadow-lg transition-shadow duration-300 flex flex-col h-full">
    
    <a href="./?product_id={{ $product->id }}" class="flex-grow">
        <div class="flex h-full">
            <!-- Columna Imagen (Aumentada al 45%) -->
            <div class="w-[45%] relative bg-gray-50 flex items-center justify-center overflow-hidden border-r border-gray-100">
                @if($product->featured)
                    <div class="absolute top-0 left-0 z-10">
                        <span class="text-[9px] font-bold px-1.5 py-0.5 text-white bg-red-600 rounded-br-lg shadow-sm">
                            ⭐
                        </span>
                    </div>
                @endif

                <div class="aspect-square w-full flex items-center justify-center" id="prod-img-{{ $product->id }}">
                    <x-image-proxy url="{{ $product->image_url }}"
                        class="w-full h-full object-contain transition-transform duration-500 hover:scale-110 {{ $product->stock == 0 ? 'opacity-40' : '' }}" />
                </div>
                
                @if($product->stock == 0)
                    <div class="absolute inset-0 flex items-center justify-center">
                        <span class="text-[8px] font-bold px-2 py-0.5 text-gray-500 uppercase tracking-widest border border-gray-300 rounded bg-white/80">Agotado</span>
                    </div>
                @endif
            </div>

            <!-- Columna Contenido (Ajustada al 55%) -->
            <div class="w-[55%] p-3 bg-white flex flex-col justify-start">
                <div class="flex flex-wrap gap-1 mb-1.5">
                    @foreach (array_filter(explode('|', $product->tags)) as $tag)
                        <span class="px-1.5 py-0.5 text-[9px] font-black bg-amber-600 text-white rounded shadow-sm uppercase tracking-wider">
                            {{ $tag }}
                        </span>
                    @endforeach
                </div>
                
                <h2 class="text-sm font-bold text-gray-900 line-clamp-2 leading-snug mb-1" title="{{ $product->description }}">
                    {{ $product->description }}
                </h2>
                
                <div class="flex items-center gap-2 mb-2">
                    <span class="text-[10px] font-medium text-blue-600 bg-blue-50 px-1.5 py-0.5 rounded border border-blue-100">
                        {{ $product->brand }}
                    </span>
                    <span class="text-[10px] text-slate-400 font-mono">#{{ $product->id }}</span>
                </div>

                {{-- Precio Alineado a la Derecha --}}
                @if(!Auth::guest())
                    <div class="flex flex-col items-end mb-2 pr-1">
                        @if($display_offer > 0)
                            <div class="flex flex-col items-end">
                                <span class="text-[11px] text-red-500 line-through font-bold">
                                    $ {{ number_format($display_price, 2, ',', '.') }}
                                </span>
                                <span class="text-2xl font-black text-green-700 leading-none">
                                    $ {{ number_format($display_offer, 2, ',', '.') }}
                                </span>
                            </div>
                        @else
                            <span class="text-xl font-black text-green-700 leading-none">
                                $ {{ number_format($display_price, 2, ',', '.') }}
                            </span>
                        @endif
                        
                        @if($product->qtty_unit > 1)
                            <span class="text-[11px] font-bold text-slate-500 mt-0.5 text-right">
                                $ {{ number_format(($display_offer > 0 ? $display_offer : $display_price) / $product->qtty_unit, 2, ',', '.') }} x un.
                            </span>
                        @endif
                    </div>
                @else
                    <div class="mb-2 text-right text-[10px] text-slate-400 italic">Precios solo usuarios</div>
                @endif
                
                <div class="text-[11px] leading-relaxed text-gray-600 line-clamp-3 border-t border-gray-50 pt-1 overflow-hidden">
                    {!! $product->description_html !!}
                </div>
            </div>
        </div>
    </a>

    @if(Auth::guest())
        <div class="p-2 bg-slate-50 border-t border-slate-100 text-center text-[11px] text-slate-500 font-medium">
            <x-icon name="o-lock-closed" class="w-3 h-3 inline mr-1" /> Regístrese para ver precios
        </div>
    @else
        <div class="p-2.5 bg-white border-t border-slate-100">
            {{-- Stock, Carrito y Bultos --}}
            <div class="grid grid-cols-3 items-center text-[10px] mb-2 px-1">
                <div class="text-left">
                    @if($product->stock < 10)
                        <span class="text-red-600 font-bold"><x-icon name="s-bolt" class="w-3 h-3 inline" /> STOCK BAJO</span>
                    @elseif($product->stock < 100)
                        <span class="text-amber-600 font-bold"><x-icon name="s-bolt" class="w-3 h-3 inline" /> STOCK MEDIO</span>
                    @else
                        <span class="text-green-600 font-bold"><x-icon name="s-check-circle" class="w-3 h-3 inline" /> EN STOCK</span>
                    @endif
                </div>

                <div class="text-center">
                    @if(!empty($cart) && isset($cart[$product->id]))
                        <span class="text-green-700 font-black bg-green-100 px-2 py-0.5 rounded-full border border-green-200" title="Cantidad en carrito">
                            <x-icon name="o-shopping-cart" class="w-3 h-3 inline mr-0.5" />
                            <span class="uppercase">en carrito:</span> {{ $cart[$product->id]['quantity'] }}
                        </span>
                    @endif
                </div>

                <div class="text-right text-slate-500 font-bold bg-slate-100 px-2 py-0.5 rounded-full">
                    <x-icon name="o-cube" class="w-3 h-3 inline mr-0.5" /> Bulto: {{ $product->qtty_package }}
                </div>
            </div>

            @if($product->stock > 0 && !in_array(Auth::user()->role->value, ['none', 'guest']))
                <div x-data="{ 
                    step: {{ $product->qtty_package }}
                }" class="space-y-2">
                    
                    <div class="flex items-stretch h-10 shadow-sm rounded-lg overflow-hidden border border-slate-300">
                        @if($product->qtty_package > 1)
                            <button wire:click="decrementQtty" class="w-1/5 bg-slate-50 hover:bg-slate-200 text-[10px] font-black text-slate-400 transition-colors border-r border-slate-200" title="Restar bulto">
                                -{{ $product->qtty_package }}
                            </button>
                            
                            <button wire:click="decrementUnit" class="w-1/5 bg-slate-100 hover:bg-slate-200 text-xs font-bold text-slate-600 transition-colors border-r border-slate-200">-1</button>
                            
                            <input type="number" wire:model="qtty" class="w-1/5 min-w-0 text-center text-xl font-black bg-white focus:outline-none" min="1">
                            
                            <button wire:click="incrementUnit" class="w-1/5 bg-slate-100 hover:bg-slate-200 text-xs font-bold text-slate-600 transition-colors border-l border-slate-200">+1</button>

                            <button wire:click="incrementQtty" class="w-1/5 bg-slate-50 hover:bg-slate-200 text-[10px] font-black text-slate-400 transition-colors border-l border-slate-200" title="Sumar bulto">
                                +{{ $product->qtty_package }}
                            </button>
                        @else
                            <button wire:click="decrementUnit" class="w-1/3 bg-slate-100 hover:bg-slate-200 text-xl font-bold text-slate-600 transition-colors border-r border-slate-200">-</button>
                            <input type="number" wire:model="qtty" class="w-1/3 min-w-0 text-center text-xl font-black bg-white focus:outline-none" min="1">
                            <button wire:click="incrementUnit" class="w-1/3 bg-slate-100 hover:bg-slate-200 text-xl font-bold text-slate-600 transition-colors border-l border-slate-200">+</button>
                        @endif
                    </div>

                    <div class="grid grid-cols-2 gap-2">
                        <x-button label="Similares" icon="o-magnifying-glass"
                            class="btn-sm bg-orange-500/80 text-white border-none hover:bg-orange-600/90 font-bold"
                            wire:click="searchSimilar" />
                        
                        <x-button label="AGREGAR" icon="o-shopping-cart"
                            class="btn-sm btn-primary shadow-md shadow-primary/20 font-bold"
                            wire:click="buy"
                            onclick="flyToCart('prod-img-{{ $product->id }}')"
                            spinner="buy" />
                    </div>
                </div>
            @endif
        </div>
    @endif
</div>
