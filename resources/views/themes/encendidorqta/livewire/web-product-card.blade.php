@php
    $isCibat = stripos($product->brand ?? '', 'CIBAT') !== false || stripos($product->description ?? '', 'BATERIA') !== false;
    $brandColor = $isCibat ? '#1b365d' : '#cc0000'; // CIBAT = blue, ER = red
    $logoSrc = $isCibat ? asset('themes/encendidorqta/cibatlogo.png') : asset('themes/encendidorqta/erlogo.png');
    $hoverTextBrand = $isCibat ? 'hover:text-[#1b365d]' : 'hover:text-[#cc0000]';
@endphp

<div wire:key="prod-card-{{ $product->id }}" 
    class="card bg-white rounded-2xl overflow-hidden hover:shadow-xl transition-all duration-300 flex flex-col h-full border border-gray-100 shadow-md">
    
    <a href="./?product_id={{ $product->id }}" class="flex-grow flex flex-col relative">
        
        <!-- Badges Superiores -->
        <div class="absolute top-3 left-3 z-10 flex flex-col gap-1.5">
            @if(is_array($product->tags_array))
                @foreach($product->tags_array as $tagName)
                    <span class="text-[10px] font-black px-2 py-1 text-white rounded-md tracking-wider shadow-sm uppercase" style="background-color: {{ $brandColor }}">
                        {{ $tagName }}
                    </span>
                @endforeach
            @endif
            @if($display_offer > 0 && (!is_array($product->tags_array) || !in_array('OFERTA', $product->tags_array)))
                <span class="text-[10px] font-black px-2 py-1 text-white rounded-md tracking-wider shadow-sm uppercase" style="background-color: {{ $brandColor }}">
                    OFERTA
                </span>
            @endif
        </div>

        <div class="absolute top-3 right-3 z-10">
            <div class="bg-white rounded-full p-1.5 shadow-md flex items-center justify-center">
                <x-icon name="o-heart" class="w-4 h-4" style="color: {{ $brandColor }}" />
            </div>
        </div>

        <!-- Columna Imagen (Vertical) -->
        <div class="relative flex flex-col items-center justify-center p-4 {{ $product->image_url ? 'bg-gray-100/50' : 'bg-white' }}" style="aspect-ratio: 4/4;" id="prod-img-{{ $product->id }}">
            @if($product->image_url)
                <x-image-proxy url="{{ $product->image_url }}"
                    class="w-full h-full object-contain mix-blend-multiply transition-transform duration-500 hover:scale-110 {{ $product->stock == 0 ? 'opacity-40' : '' }}" />
            @else
                <img src="{{ $logoSrc }}" alt="Placeholder" class="w-2/3 max-w-[160px] h-auto brightness-0 opacity-20">
                <span class="absolute bottom-4 left-0 right-0 text-center text-[11px] font-bold text-gray-400 uppercase tracking-widest">IMAGEN NO DISPONIBLE</span>
            @endif
            
            @if($product->stock == 0)
                <div class="absolute inset-0 flex items-center justify-center bg-white/40">
                    <span class="text-[10px] font-black px-3 py-1 text-gray-600 uppercase tracking-widest border-2 border-gray-400 rounded-lg bg-white shadow-lg">Agotado</span>
                </div>
            @endif
        </div>

        <!-- Columna Contenido (Vertical) -->
        <div class="p-4 bg-white flex flex-col flex-grow">
            @if($product->brand)
                <div class="text-[10px] font-black uppercase mb-1" style="color: {{ $brandColor }};">
                    {{ $product->brand }}
                </div>
            @endif
            
            <div class="h-[2.5rem] mb-3 overflow-hidden">
                <h2 class="text-xs sm:text-[13px] font-black text-black leading-tight uppercase {{ $hoverTextBrand }} transition-colors line-clamp-2" title="{{ $product->description }}">
                    {{ $product->description }}
                </h2>
            </div>
            
            <!-- Separador -->
            <hr class="border-gray-200 w-full mb-3">

            @if(!Auth::guest() && $showPrices)
                <div class="flex flex-col mt-auto">
                    @if(($display_offer > 0 ? $display_offer : $display_price) <= 0)
                        <span class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Muy pronto</span>
                    @else
                        @if($display_offer > 0)
                            <span class="text-[11px] text-gray-400 line-through font-bold mb-0.5">
                                $ {{ number_format($display_price, 2, ',', '.') }}
                            </span>
                            <span class="text-lg font-black text-gray-900 leading-none">
                                $ {{ number_format($display_offer, 2, ',', '.') }}
                            </span>
                        @else
                            <span class="text-lg font-black text-gray-900 leading-none">
                                $ {{ number_format($display_price, 2, ',', '.') }}
                            </span>
                        @endif
                    @endif
                </div>
            @else
                <div class="mt-auto">
                    <p class="text-[10px] text-gray-400 font-medium leading-tight">
                        *Los precios solo están disponibles para los usuarios registrados.
                    </p>
                </div>
            @endif
        </div>
    </a>

    <!-- Footer Botones -->
    <div class="px-4 pb-4 bg-white flex flex-col gap-2">
        <a href="{{ route('register') }}" class="w-full flex items-center justify-center gap-2 py-2.5 bg-gray-400 hover:bg-gray-500 text-white text-[11px] font-black rounded-lg transition-colors uppercase shadow-sm">
            <x-icon name="o-lock-closed" class="w-4 h-4" /> REGISTRARSE
        </a>

        @php
            $currUser = current_user();
            $canBuy = $product->stock > 0 && $currUser && !in_array($currUser->role->value, ['none', 'guest']) && ($display_offer > 0 ? $display_offer : $display_price) > 0;
        @endphp
        
        <button wire:click="buy" onclick="flyToCart('prod-img-{{ $product->id }}')"
                class="w-full flex items-center justify-center gap-2 py-2.5 text-white text-[11px] font-black rounded-lg transition-all uppercase shadow-md hover:brightness-110 active:scale-95 @if(Auth::guest() || !$showPrices || !$canBuy) opacity-50 cursor-not-allowed pointer-events-none @endif"
                style="background-color: {{ $brandColor }};"
                @if(Auth::guest() || !$showPrices || !$canBuy) disabled @endif>
            <x-icon name="o-shopping-cart" class="w-4 h-4" /> AGREGAR AL CARRITO
        </button>
    </div>
</div>
