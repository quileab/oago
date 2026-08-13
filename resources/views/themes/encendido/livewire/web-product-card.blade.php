<div wire:key="prod-card-{{ $product->id }}"
    x-data="{
        init() {
            gsap.fromTo($el,
                { opacity: 0, y: 20 },
                { opacity: 1, y: 0, duration: 0.5, ease: 'power2.out' }
            );
        }
    }"
    class="prod-card-item h-full">

    <div class="card ring-1 ring-white/5 shadow-lg hover:shadow-xl transition-all duration-200 flex flex-col h-full overflow-hidden group rounded-lg bg-neutral-900 hover:ring-white/15 hover:-translate-y-0.5">

        {{-- Image — full width top --}}
        <a href="./?product_id={{ $product->id }}" class="block relative bg-neutral-950 overflow-hidden" style="aspect-ratio: 4/3;">
            @if($product->featured)
                <div class="absolute top-0 left-0 z-10">
                    <span class="text-[9px] font-black px-2 py-1 text-white bg-primary uppercase tracking-wider flex items-center gap-1">
                        <x-icon name="s-star" class="w-2.5 h-2.5" /> Destacado
                    </span>
                </div>
            @endif

            <x-image-proxy url="{{ $product->image_url }}"
                class="w-full h-full object-contain p-4 transition-transform duration-300 group-hover:scale-105 {{ $product->stock == 0 ? 'opacity-30 grayscale' : '' }}" />

            @if($product->stock == 0)
                <div class="absolute inset-0 flex items-center justify-center bg-neutral-950/70">
                    <span class="text-xs font-black px-3 py-1.5 text-white bg-red-700 uppercase tracking-widest">Agotado</span>
                </div>
            @endif
        </a>

        {{-- Content --}}
        <div class="flex flex-col flex-grow p-4 border-t border-white/5">

            {{-- Tags --}}
            @if(!empty($product->tags_array))
                <div class="flex flex-wrap gap-1 mb-2">
                    @foreach ($product->tags_array as $tag)
                        <span class="px-1.5 py-0.5 text-[9px] font-black bg-amber-500/15 text-amber-400 border border-amber-500/25 uppercase tracking-wider">
                            {{ $tag }}
                        </span>
                    @endforeach
                </div>
            @endif

            {{-- Price — prominent --}}
            <div class="mb-3">
                @if($showPrices)
                    @if(($display_offer > 0 ? $display_offer : $display_price) <= 0)
                        <span class="text-xs font-bold text-neutral-500 uppercase tracking-wider">Muy pronto</span>
                    @else
                        @if($display_offer > 0)
                            <div class="flex items-baseline gap-2 flex-wrap">
                                <span class="text-2xl font-black text-emerald-400 font-technical leading-none">$ {{ number_format($display_offer, 2, ',', '.') }}</span>
                                <span class="text-xs text-neutral-500 line-through font-medium">$ {{ number_format($display_price, 2, ',', '.') }}</span>
                            </div>
                        @else
                            <span class="text-2xl font-black text-emerald-400 font-technical leading-none">$ {{ number_format($display_price, 2, ',', '.') }}</span>
                        @endif
                        @if($product->qtty_unit > 1)
                            <div class="text-[10px] text-neutral-500 mt-1 font-technical">
                                $ {{ number_format(($display_offer > 0 ? $display_offer : $display_price) / $product->qtty_unit, 2, ',', '.') }} / un.
                            </div>
                        @endif
                    @endif
                @else
                    <span class="text-xs text-neutral-500 italic">Precios solo para usuarios</span>
                @endif
            </div>

            {{-- Name & Brand --}}
            <a href="./?product_id={{ $product->id }}" class="flex-grow mb-3 block group/link">
                <h2 class="text-sm font-bold text-neutral-200 leading-snug line-clamp-2 group-hover/link:text-white transition-colors" title="{{ $product->description }}">
                    {{ $product->description }}
                </h2>
                <div class="flex items-center gap-2 mt-2">
                    @if($product->brand)
                        <span class="text-[10px] font-bold text-primary/80 uppercase tracking-wider">{{ $product->brand }}</span>
                        <span class="text-neutral-700">·</span>
                    @endif
                    <span class="text-[10px] text-neutral-600 font-technical">#{{ $product->id }}</span>
                </div>
            </a>

            {{-- Controls --}}
            @if(Auth::guest())
                <div class="text-center text-xs text-neutral-500 border-t border-white/5 pt-3">
                    <x-icon name="o-lock-closed" class="w-3.5 h-3.5 inline mr-1 text-amber-500" /> {{ $guestMessage }}
                </div>
            @else
                {{-- Stock & Bulto info --}}
                <div class="flex justify-between items-center text-[10px] mb-3 border-t border-white/5 pt-3">
                    <div>
                        @if($product->stock < 10)
                            <span class="text-rose-400 font-black flex items-center gap-1"><x-icon name="s-bolt" class="w-3 h-3" /> STOCK BAJO</span>
                        @elseif($product->stock < 100)
                            <span class="text-amber-400 font-black flex items-center gap-1"><x-icon name="s-bolt" class="w-3 h-3" /> STOCK MEDIO</span>
                        @else
                            <span class="text-emerald-400 font-black flex items-center gap-1"><x-icon name="s-check-circle" class="w-3 h-3" /> EN STOCK</span>
                        @endif
                    </div>
                    <span class="text-neutral-500 flex items-center gap-1">
                        <x-icon name="o-cube" class="w-3 h-3" /> <span class="font-technical">{{ $product->qtty_package }}</span> un/bto
                    </span>
                </div>

                {{-- In Cart --}}
                @if(!empty($cart) && isset($cart[$product->id]))
                    <div class="mb-2 bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-[10px] font-bold px-2 py-1 flex items-center justify-center gap-1">
                        <x-icon name="o-shopping-cart" class="w-3 h-3" /> EN CARRITO: {{ $cart[$product->id]['quantity'] }} un.
                    </div>
                @endif

                @if($product->stock > 0 && !in_array(Auth::user()->role->value, ['none', 'guest']) && ($display_offer > 0 ? $display_offer : $display_price) > 0)
                    <div class="flex items-stretch h-10 overflow-hidden border border-neutral-700 bg-neutral-950/60 focus-within:border-primary transition-colors">
                        @if($product->qtty_package > 1)
                            <button wire:click="decrementQtty" class="w-10 bg-neutral-800 hover:bg-rose-500/20 hover:text-rose-400 text-[10px] font-black text-neutral-400 transition-colors border-r border-neutral-700 shrink-0" title="-{{ $product->qtty_package }}">
                                -{{ $product->qtty_package }}
                            </button>
                        @endif
                        <button wire:click="decrementUnit" class="w-10 bg-neutral-900 hover:bg-rose-500/15 hover:text-rose-400 text-base font-black text-neutral-400 transition-colors border-r border-neutral-700 shrink-0">-</button>
                        <input type="number" wire:model="qtty" class="flex-grow min-w-0 text-center text-base font-black bg-transparent text-white focus:outline-none font-technical" min="1">
                        <button wire:click="incrementUnit" class="w-10 bg-neutral-900 hover:bg-emerald-500/15 hover:text-emerald-400 text-base font-black text-neutral-400 transition-colors border-l border-neutral-700 shrink-0">+</button>
                        @if($product->qtty_package > 1)
                            <button wire:click="incrementQtty" class="w-10 bg-neutral-800 hover:bg-emerald-500/20 hover:text-emerald-400 text-[10px] font-black text-neutral-400 transition-colors border-l border-neutral-700 shrink-0" title="+{{ $product->qtty_package }}">
                                +{{ $product->qtty_package }}
                            </button>
                        @endif
                    </div>

                    <div class="grid grid-cols-2 gap-1.5 mt-1.5">
                        <button wire:click="searchSimilar" class="btn btn-xs btn-ghost border border-neutral-700 text-neutral-400 hover:border-neutral-500 hover:text-neutral-200 font-bold rounded-none text-[10px]">
                            <x-icon name="o-magnifying-glass" class="w-3 h-3" /> Similares
                        </button>
                        <button wire:click="buy" onclick="flyToCart('prod-img-{{ $product->id }}')"
                            class="btn btn-xs btn-primary font-black rounded-none text-[10px] tracking-wide"
                            wire:loading.class="btn-disabled">
                            <span wire:loading.remove wire:target="buy" class="flex items-center gap-1">
                                <x-icon name="o-shopping-cart" class="w-3 h-3" /> AGREGAR
                            </span>
                            <span wire:loading wire:target="buy" class="loading loading-spinner loading-xs"></span>
                        </button>
                    </div>
                @endif
            @endif
        </div>

        {{-- Image ID anchor for fly-to-cart --}}
        <div id="prod-img-{{ $product->id }}" class="hidden">
            <x-image-proxy url="{{ $product->image_url }}" class="w-8 h-8 object-contain" />
        </div>
    </div>
</div>
