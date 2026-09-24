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

    @php
        // Verificar si el producto tiene el tag de cibat o encendido
        $tagsLower = array_map('strtolower', $product->tags_array ?? []);
        $isCibat = in_array('cibat', $tagsLower);
        
        // Asignar colores específicos
        $bgBrand = $isCibat ? 'bg-[#002b6b]' : 'bg-[#e60000]';
        $textBrand = $isCibat ? 'text-[#002b6b]' : 'text-[#e60000]';
        $hoverBgBrand = $isCibat ? 'hover:bg-[#001f4d]' : 'hover:bg-[#cc0000]';
    @endphp

    <div class="card bg-white border border-gray-200 shadow-sm hover:shadow-md transition-all duration-200 flex flex-col h-full relative overflow-hidden group rounded-[12px] hover:-translate-y-0.5">

        {{-- Oferta Pill --}}
        @if($display_offer > 0 || $product->featured)
            <div class="absolute top-0 left-0 z-10">
                <span class="text-[10px] font-black px-3 py-1 text-white {{ $bgBrand }} uppercase tracking-wider rounded-br-lg shadow-sm">
                    {{ $display_offer > 0 ? 'OFERTA' : 'DESTACADO' }}
                </span>
            </div>
        @endif

        {{-- Heart Icon (Favorite) --}}
        <div class="absolute top-2 right-2 z-10 cursor-pointer {{ $textBrand }} hover:scale-110 transition-transform">
            <x-icon name="s-heart" class="w-5 h-5" />
        </div>

        {{-- Image — full width top with grey bg --}}
        <a href="./?product_id={{ $product->id }}" class="block relative bg-[#e5e7eb] flex items-center justify-center p-4" style="aspect-ratio: 4/4;">
            @if($product->image_url)
                <x-image-proxy url="{{ $product->image_url }}"
                    class="w-full h-full object-contain transition-transform duration-300 group-hover:scale-105 {{ $product->stock == 0 ? 'opacity-30 grayscale' : '' }}" />
            @else
                <div class="flex flex-col items-center justify-center text-gray-400 opacity-50 select-none">
                    @if($isCibat)
                        <img src="{{ asset('imgs/Logos/logo_cibat.png') }}" class="w-28 h-auto grayscale mb-4 object-contain opacity-70" alt="Cibat Placeholder" />
                    @else
                        <img src="{{ asset('imgs/Logos/ER50.png') }}" class="w-24 h-auto grayscale mb-4 object-contain opacity-70" alt="Encendido Placeholder" />
                    @endif
                    <span class="text-[10px] font-bold uppercase tracking-widest text-center text-gray-400">IMAGEN NO DISPONIBLE</span>
                </div>
            @endif

            @if($product->stock == 0)
                <div class="absolute inset-0 flex items-center justify-center bg-black/40">
                    <span class="text-[10px] font-black px-3 py-1 text-white bg-red-600 uppercase tracking-widest rounded">Agotado</span>
                </div>
            @endif
        </a>

        {{-- Content --}}
        <div class="flex flex-col flex-grow p-4 bg-white">

            {{-- Title --}}
            <a href="./?product_id={{ $product->id }}" class="mb-3 block group/link min-h-[40px]">
                <h2 class="text-xs sm:text-[13px] font-black text-black leading-tight uppercase group-hover/link:{{ $textBrand }} transition-colors line-clamp-2" title="{{ $product->description }}">
                    {{ $product->description }}
                </h2>
            </a>

            {{-- Divider --}}
            <hr class="border-gray-200 my-2">

            {{-- Price or Guest Message --}}
            <div class="flex-grow flex flex-col justify-end">
                @if($showPrices)
                    @if(($display_offer > 0 ? $display_offer : $display_price) <= 0)
                        <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">Muy pronto</span>
                    @else
                        <div class="mb-2">
                            @if($display_offer > 0)
                                <div class="flex items-baseline gap-2 flex-wrap">
                                    <span class="text-lg font-black text-black leading-none">$ {{ number_format($display_offer, 2, ',', '.') }}</span>
                                    <span class="text-[10px] text-gray-500 line-through font-medium">$ {{ number_format($display_price, 2, ',', '.') }}</span>
                                </div>
                            @else
                                <span class="text-lg font-black text-black leading-none">$ {{ number_format($display_price, 2, ',', '.') }}</span>
                            @endif
                        </div>
                    @endif
                @else
                    <p class="text-[10px] text-gray-400 leading-tight italic mb-3">
                        *Los precios sólo están disponibles<br>para los usuarios registrados.
                    </p>
                @endif
            </div>

            {{-- Controls / Buttons --}}
            <div class="mt-2 flex flex-col gap-1.5">
                @if(Auth::guest())
                    <a href="/registrate" class="w-full bg-gray-400 hover:bg-gray-500 text-white text-[10px] font-bold py-2 rounded-md flex items-center justify-center gap-1.5 transition-colors tracking-wide uppercase">
                        <x-icon name="s-lock-closed" class="w-3.5 h-3.5" /> REGISTRARSE
                    </a>
                    <a href="/login" class="w-full {{ $bgBrand }} {{ $hoverBgBrand }} text-white text-[10px] font-bold py-2 rounded-md flex items-center justify-center gap-1.5 transition-colors tracking-wide uppercase">
                        <x-icon name="s-shopping-cart" class="w-3.5 h-3.5" /> AGREGAR AL CARRITO
                    </a>
                @else
                    {{-- Qtty Control & Buy for Logged In Users --}}
                    @php
                        $currUser = current_user();
                        $canBuy = $product->stock > 0 && $currUser && !in_array($currUser->role->value, ['none', 'guest']) && ($display_offer > 0 ? $display_offer : $display_price) > 0;
                    @endphp
                    @if($canBuy)
                        <div class="flex items-stretch h-8 rounded-md overflow-hidden border border-gray-300 bg-gray-50 mb-1.5">
                            <button wire:click="decrementUnit" class="w-8 bg-gray-200 hover:bg-gray-300 text-gray-600 font-bold transition-colors shrink-0">-</button>
                            <input type="number" wire:model="qtty" class="flex-grow min-w-0 text-center text-xs font-bold bg-transparent text-black focus:outline-none" min="1">
                            <button wire:click="incrementUnit" class="w-8 bg-gray-200 hover:bg-gray-300 text-gray-600 font-bold transition-colors shrink-0">+</button>
                        </div>
                        
                        <button wire:click="buy" onclick="flyToCart('prod-img-{{ $product->id }}')"
                            class="w-full {{ $bgBrand }} {{ $hoverBgBrand }} text-white text-[10px] font-bold py-2 rounded-md flex items-center justify-center gap-1.5 transition-colors tracking-wide uppercase"
                            wire:loading.class="opacity-70 pointer-events-none">
                            <span wire:loading.remove wire:target="buy" class="flex items-center gap-1.5">
                                <x-icon name="s-shopping-cart" class="w-3.5 h-3.5" /> AGREGAR AL CARRITO
                            </span>
                            <span wire:loading wire:target="buy" class="loading loading-spinner loading-xs"></span>
                        </button>
                    @endif
                @endif
            </div>

        </div>

        {{-- Image ID anchor for fly-to-cart --}}
        <div id="prod-img-{{ $product->id }}" class="hidden">
            <x-image-proxy url="{{ $product->image_url }}" class="w-8 h-8 object-contain" />
        </div>
    </div>
</div>
