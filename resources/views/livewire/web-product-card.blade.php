<div wire:key="prod-card-{{ $product->id }}" 
    class="card bg-white shadow-md shadow-slate-400 overflow-hidden hover:shadow-lg transition-shadow duration-300 flex flex-col h-full">
    
    <a href="./?product_id={{ $product->id }}" class="flex-grow">
        <div class="flex h-full">
            <!-- Columna Imagen (Aumentada al 45%) -->
            <div class="w-[45%] relative bg-gray-50 flex items-center justify-center overflow-hidden border-r border-gray-100">
                @if($product->featured)
                    <div class="absolute top-0 left-0">
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
                    @foreach ($product->tags_array as $tagName)
                        <span class="px-1.5 py-0.5 text-[9px] font-black bg-amber-600 text-white rounded shadow-sm uppercase tracking-wider">
                            {{ $tagName }}
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
                @if($showPrices)
                    @if(($display_offer > 0 ? $display_offer : $display_price) <= 0)
                        <div class="flex flex-col items-end mb-2 pr-1">
                            <span class="text-[10px] font-bold px-2 py-0.5 text-slate-500 bg-slate-100 rounded border border-slate-200 uppercase tracking-wider">
                                Muy pronto
                            </span>
                        </div>
                    @else
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
                    @endif
                @else
                    <div class="mb-2 text-right text-[10px] text-slate-400 italic">Precios solo usuarios</div>
                @endif
                
                <div class="text-[11px] leading-relaxed text-gray-600 line-clamp-3 border-t border-gray-50 pt-1 overflow-hidden">
                    {!! strip_tags($product->description_html, '<p><br><b><strong><i><em><ul><ol><li><a><span><div>') !!}
                </div>
            </div>
        </div>
    </a>

    @if(Auth::guest())
        <div class="p-2 bg-slate-50 border-t border-slate-100 text-center text-[11px] text-slate-500 font-medium">
            <x-icon name="o-lock-closed" class="w-3 h-3 inline mr-1" /> {{ $guestMessage }}
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
            </div>

            @php
                $currUser = current_user();
                $canBuy = $product->stock > 0 && $currUser && !in_array($currUser->role->value, ['none', 'guest']) && ($display_offer > 0 ? $display_offer : $display_price) > 0;
            @endphp
            @if($canBuy)
                <div x-data="{
                    pkg: {{ (int) ($product->qtty_package ?? 1) }},
                    get mode() { return $wire.buyMode },
                    setMode(m) {
                        $wire.buyMode = m;
                        $wire.qtty = m === 'bulk' ? this.pkg : 1;
                    },
                    dec() {
                        const step = this.mode === 'bulk' ? this.pkg : 1;
                        const current = parseInt($wire.qtty) || step;
                        $wire.qtty = current > step ? current - step : step;
                    },
                    inc() {
                        const step = this.mode === 'bulk' ? this.pkg : 1;
                        const current = parseInt($wire.qtty) || 0;
                        $wire.qtty = current + step;
                    },
                    get subtitle() {
                        const q = parseInt($wire.qtty) || 1;
                        if (this.mode === 'bulk') {
                            const b = +(q / this.pkg).toFixed(2);
                            return `${b} bulto${b !== 1 ? 's' : ''} · ${q} un.`;
                        }
                        return `${q} unidad${q !== 1 ? 'es' : ''}`;
                    }
                }" class="space-y-2">

                    {{-- Toggle Bulto / Unidad (solo si hay paquete) --}}
                    @if($product->qtty_package > 1)
                        <div class="flex items-center bg-slate-100 rounded-full p-0.5 gap-0.5">
                            <button
                                type="button"
                                @click="setMode('bulk')"
                                :class="mode === 'bulk' ? 'bg-blue-600 text-white shadow-sm' : 'text-slate-500 hover:text-slate-700'"
                                class="flex-1 flex items-center justify-center gap-1 rounded-full py-1 text-[10px] font-black transition-all duration-200"
                            >
                                <x-icon name="o-cube" class="w-3 h-3 inline shrink-0" />
                                BULTO (x{{ $product->qtty_package }})
                            </button>
                            <button
                                type="button"
                                @click="setMode('unit')"
                                :class="mode === 'unit' ? 'bg-blue-600 text-white shadow-sm' : 'text-slate-500 hover:text-slate-700'"
                                class="flex-1 flex items-center justify-center gap-1 rounded-full py-1 text-[10px] font-black transition-all duration-200"
                            >
                                <x-icon name="o-tag" class="w-3 h-3 inline shrink-0" />
                                UNIDAD
                            </button>
                        </div>
                    @endif

                    {{-- Selector de cantidad --}}
                    <div class="flex items-stretch h-11 shadow-sm rounded-lg overflow-hidden border"
                        :class="mode === 'bulk' ? 'border-blue-300' : 'border-slate-300'">
                        <button type="button" @click="dec()"
                            class="w-10 shrink-0 flex items-center justify-center text-xl font-black transition-colors border-r"
                            :class="mode === 'bulk'
                                ? 'bg-blue-100 hover:bg-blue-200 text-blue-700 border-blue-200'
                                : 'bg-slate-200 hover:bg-slate-300 text-slate-700 border-slate-300'"
                        >−</button>

                        <div class="flex-1 flex flex-col items-center justify-center bg-white">
                            <input type="number" x-model.number="$wire.qtty"
                                class="w-full text-center text-xl font-black bg-transparent focus:outline-none leading-none"
                                min="1"
                            >
                            <span class="text-[9px] leading-none font-semibold"
                                :class="mode === 'bulk' ? 'text-blue-500' : 'text-slate-400'"
                                x-text="subtitle">
                            </span>
                        </div>

                        <button type="button" @click="inc()"
                            class="w-10 shrink-0 flex items-center justify-center text-xl font-black transition-colors border-l"
                            :class="mode === 'bulk'
                                ? 'bg-blue-100 hover:bg-blue-200 text-blue-700 border-blue-200'
                                : 'bg-slate-200 hover:bg-slate-300 text-slate-700 border-slate-300'"
                        >+</button>
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
