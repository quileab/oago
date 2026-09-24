<div>
    <div class="fixed bottom-4 right-4 z-50">
        @if (count($cart) > 0)
            <div id="cartCount" class="absolute -top-2 -right-2 z-20 bg-red-600 text-white font-black text-sm w-7 h-7 flex items-center justify-center rounded-full shadow-lg border-2 border-white">
                {{ count($cart) }}
            </div>
        @endif
        <div id="cart-highlight" class="cartButton transition-transform hover:scale-110" wire:ignore>
            {{-- Aquí se activará la animación. Necesitamos un elemento interno para el ping --}}
            <button wire:click="$toggle('showCart')" class="btn btn-circle btn-primary btn-lg shadow-xl shadow-primary/30 border-2 border-primary-focus relative z-10 w-16 h-16">
                <x-icon name="o-shopping-cart" class="w-8 h-8" />
            </button>
        </div>
    </div>

    <x-drawer wire:model="showCart" class="w-full md:w-3/4 lg:w-1/2 xl:w-5/12 bg-white text-slate-800 shadow-2xl border-l border-slate-200 !p-0" style="padding: 0px !important;" right
        without-close-button close-on-escape>

        @if (count($cart) > 0)
            {{-- Layout manual con 3 zonas fijas: header, scroll, footer --}}
            <div class="flex flex-col" style="height: 100dvh;">

                {{-- ENCABEZADO FIJO --}}
                <div class="shrink-0 bg-slate-50 border-b border-slate-200 px-4 py-3 flex items-center justify-between">
                    <h2 class="text-base sm:text-lg font-black text-blue-600 flex items-center gap-2 uppercase tracking-wider">
                        <x-icon name="o-shopping-cart" class="w-5 h-5" /> Tu Pedido
                    </h2>
                    <x-button label="Seguir Comprando" icon-right="o-x-mark" class="btn-ghost btn-sm text-slate-500 hover:text-slate-800 hover:bg-slate-200 font-bold uppercase text-[10px] sm:text-xs tracking-wider" wire:click="$toggle('showCart')" />
                </div>

                {{-- ZONA SCROLLEABLE --}}
                <div class="flex-1 overflow-y-auto bg-slate-50 relative">
                    <div class="hidden md:grid grid-cols-[80px_1fr_100px_120px_100px_48px] gap-2 items-center font-bold text-slate-500 text-center text-xs uppercase tracking-wider sticky top-0 bg-white z-10 shadow-sm border-b border-slate-200 px-2 py-3">
                        <div class="text-center">Imagen</div>
                        <div class="text-left">Producto</div>
                        <div class="text-right">Precio</div>
                        <div class="text-center">Cantidad</div>
                        <div class="text-right">Total</div>
                        <div class="text-center"></div>
                    </div>
                    
                    <div class="flex flex-col">
                        @foreach ($cart as $item)
                            @php
                                $isCibat = stripos($item['brand'] ?? '', 'CIBAT') !== false || stripos($item['name'] ?? '', 'BATERIA') !== false;
                                $fallbackLogo = $isCibat ? asset('themes/encendidorqta/cibatlogo.png') : asset('themes/encendidorqta/erlogo.png');
                            @endphp
                            <div class="border-b border-slate-200 hover:bg-white transition-colors p-3 md:p-2 flex flex-col md:grid md:grid-cols-[80px_1fr_100px_120px_100px_48px] gap-2 md:items-center relative">
                                
                                {{-- Top Section on Mobile (Image + Title) --}}
                                <div class="flex items-start gap-3 mb-2 md:mb-0 md:contents">
                                    {{-- Image --}}
                                    <div class="shrink-0 w-20 h-20 md:w-16 md:h-16 flex justify-center md:mx-auto" x-data="{ isFallback: false }">
                                        <div class="w-full h-full rounded-md overflow-hidden border border-slate-200"
                                            x-bind:class="isFallback ? 'bg-gray-100 p-2' : 'bg-white p-1'">
                                            <x-image-proxy url="{{ config('services.regente.base_url') . $item['product_id'] . '.jpg' }}"
                                                fallback="{{ $fallbackLogo }}"
                                                alt="{{ $item['product_id'] }}" 
                                                class="w-full h-full object-contain"
                                                onerror=""
                                                x-on:error="isFallback = true; $el.src = '{{ $fallbackLogo }}';"
                                                x-bind:class="isFallback ? 'brightness-0 opacity-20' : ''" />
                                        </div>
                                    </div>
                                    
                                    {{-- Title --}}
                                    <div class="flex-1 font-bold text-sm text-slate-800 line-clamp-2 leading-tight pr-6 md:pr-0">
                                        {{ $item['name'] }}
                                        @if (isset($item['product_model']) && $item['product_model']->hasBonus())
                                            <br>
                                            <div class="text-[10px] font-black text-blue-600 bg-blue-50 px-2 py-0.5 rounded-sm inline-block mt-1">
                                                {{ $item['product_model']->bonus_label }}
                                            </div>
                                        @endif
                                    </div>
                                </div>
                                
                                {{-- Delete Button (Mobile Absolute overlay) --}}
                                <div class="absolute top-2 right-2 md:hidden z-10">
                                    <x-dropdown>
                                        <x-slot:trigger>
                                            <x-button icon="o-trash" class="text-red-400 hover:text-red-600 hover:bg-red-50 btn-ghost btn-sm btn-circle transition-colors" />
                                        </x-slot:trigger>
                                        <div class="bg-white border border-slate-200 shadow-xl rounded-lg overflow-hidden z-[100]">
                                            <x-menu-item title="Eliminar" icon="o-trash" class="text-red-500 hover:bg-red-50 font-bold"
                                                wire:click="removeFromCart({{ $item['product_id'] }})" />
                                            <x-menu-item title="Cancelar" icon="o-x-mark" class="hover:bg-slate-50 text-slate-600" />
                                        </div>
                                    </x-dropdown>
                                </div>

                                {{-- Details Grid for Mobile / Columns for Desktop --}}
                                <div class="grid grid-cols-3 gap-2 md:contents items-center text-center">
                                    <div class="text-left md:text-right flex flex-col justify-center h-full {{ ($item['is_price_changed'] ?? false) ? 'text-red-500 font-bold' : 'text-slate-600 font-medium' }}">
                                        <span class="md:hidden text-[10px] uppercase text-slate-400">Precio</span>
                                        ${{ number_format($item['price'], 2) }}
                                        @if($item['is_price_changed'] ?? false)
                                            <div class="text-[10px] text-red-500 font-bold bg-red-50 px-1 rounded inline-block mt-0.5">Act: ${{ number_format($item['current_price'], 2) }}</div>
                                        @endif
                                    </div>
                                    <div class="flex flex-col items-center">
                                        <span class="md:hidden text-[10px] uppercase text-slate-400 mb-1">Cant</span>
                                        <input type="number" min="{{ $item['bulkQuantity'] }}" step="{{ $item['bulkQuantity'] }}"
                                            wire:change="updateQuantity({{ $item['product_id'] }}, $event.target.value)"
                                            wire:key="cart-{{ $item['product_id'] }}-{{ $item['quantity'] }}" id="cart-{{ $item['product_id'] }}-{{ $item['quantity'] }}"
                                            value="{{ $item['quantity'] }}" class="input input-sm input-bordered w-full max-w-[4rem] md:max-w-[5rem] text-center font-bold {{ ($item['is_stock_insufficient'] ?? false) ? 'border-red-500 bg-red-50 text-red-600' : 'bg-white focus:border-blue-500 focus:ring-1 focus:ring-blue-500' }}" />

                                        @if($item['is_stock_insufficient'] ?? false)
                                            <div class="text-[10px] text-red-500 font-black text-center mt-1">Disp: {{ $item['available_stock'] }}</div>
                                        @endif

                                        <div class="text-[10px] text-slate-400 font-bold text-center mt-1">
                                            @if($item['quantity'] % $item['bulkQuantity'] === 0)
                                                <x-icon name="o-cube" class="w-3 h-3 inline mr-0.5" /> {{ $item['quantity'] / $item['bulkQuantity']}} x {{ $item['bulkQuantity'] }}
                                            @else
                                                <x-icon name="o-cube" class="w-3 h-3 inline mr-0.5" /> {{ floor($item['quantity'] / $item['bulkQuantity']) }} x {{ $item['bulkQuantity'] }} <br/> + {{ $item['quantity'] - (floor($item['quantity'] / $item['bulkQuantity']) * $item['bulkQuantity']) }} un.
                                            @endif
                                        </div>
                                    </div>
                                    <div class="text-right flex flex-col justify-center h-full font-black text-slate-800 text-lg">
                                        <span class="md:hidden text-[10px] uppercase text-slate-400">Total</span>
                                        ${{ number_format($item['total_price'], 2) }}
                                    </div>
                                </div>
                                
                                {{-- Desktop Delete Button --}}
                                <div class="hidden md:flex justify-center">
                                    <x-dropdown>
                                        <x-slot:trigger>
                                            <x-button icon="o-trash" class="text-red-400 hover:text-red-600 hover:bg-red-50 w-full btn-ghost btn-sm btn-circle transition-colors" />
                                        </x-slot:trigger>
                                        <div class="bg-white border border-slate-200 shadow-xl rounded-lg overflow-hidden z-[100]">
                                            <x-menu-item title="Eliminar" icon="o-trash" class="text-red-500 hover:bg-red-50 font-bold"
                                                wire:click="removeFromCart({{ $item['product_id'] }})" />
                                            <x-menu-item title="Cancelar" icon="o-x-mark" class="hover:bg-slate-50 text-slate-600" />
                                        </div>
                                    </x-dropdown>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- PIE FIJO --}}
                <div class="shrink-0 bg-white border-t border-slate-200 px-4 py-4 shadow-[0_-10px_20px_-5px_rgba(0,0,0,0.05)]">
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 items-center">
                        <x-button link="{{ route('checkout') }}" label="Confirmar Pedido" icon="o-check" class="btn-primary w-full text-xs sm:text-sm shadow-md" />
                        <x-button wire:click="saveCart()" label="Guardar" icon="o-shopping-cart" class="btn-outline w-full text-xs sm:text-sm text-slate-600 border-slate-300 hover:bg-slate-50 hover:text-slate-800" />
                        <div class="w-full">
                            <x-dropdown class="w-full">
                                <x-slot:trigger>
                                    <x-button icon="o-trash" label="Vaciar" class="btn-ghost text-red-500 hover:bg-red-50 w-full text-xs sm:text-sm" />
                                </x-slot:trigger>
                                <div class="bg-white border border-slate-200 shadow-xl rounded-lg overflow-hidden mb-2">
                                    <x-menu-item title="Confirmar Vaciar" icon="o-check" wire:click="emptyCart" class="text-red-500 hover:bg-red-50 font-bold" />
                                    <x-menu-item title="Cancelar" icon="o-x-mark" class="hover:bg-slate-50 text-slate-600" />
                                </div>
                            </x-dropdown>
                        </div>
                        <div class="text-right flex items-center justify-end h-full">
                            <h3 class="text-2xl sm:text-3xl font-black text-blue-700 leading-none">
                                ${{ number_format($total, 2, ',', '.') }}
                            </h3>
                        </div>
                    </div>
                </div>

            </div>
        @else
            <div class="flex flex-col items-center justify-center h-full text-center p-8 text-slate-500">
                <x-icon name="o-shopping-cart" class="w-24 h-24 text-slate-200 mb-6" />
                <h2 class="text-2xl font-black text-slate-700 mb-2">Tu carrito está vacío</h2>
                <p class="mb-8">Parece que aún no has agregado ningún producto.</p>
                <x-button label="Seguir Comprando" icon="o-arrow-left" class="btn-primary uppercase font-bold tracking-wider" wire:click="$toggle('showCart')" />
            </div>
        @endif
    </x-drawer>
</div>
