<div>
    <div class="fixed bottom-4 right-4 z-50">
        @if (count($cart) > 0)
            <div id="cartCount" class="z-11 relative text-lg text-white -right-8 top-8">
                {{ count($cart) }}
            </div>
        @endif
        <div id="cart-highlight" class="cartButton" wire:ignore>
            {{-- Aquí se activará la animación. Necesitamos un elemento interno para el ping --}}
            <x-button wire:click="$toggle('showCart')" class="w-18 h-18 btn-circle bg-blue-700 relative z-10">
                <x-icon name="o-shopping-cart" class="w-10 h-10 mt-3" />
            </x-button>
        </div>
    </div>

    <x-drawer wire:model="showCart" class="w-11/12 lg:w-2/3 bg-slate-900 text-gray-50 !p-0" style="padding: 0px !important;" right
        with-close-button close-on-escape>

        @if (count($cart) > 0)
            {{-- Layout manual con 3 zonas fijas: header, scroll, footer --}}
            <div class="flex flex-col" style="height: 100dvh;">

                {{-- ENCABEZADO FIJO (Solo móvil) --}}
                <div class="shrink-0 bg-slate-800 border-b border-slate-700 p-3 flex justify-between items-center md:hidden">
                    <span class="font-bold text-slate-300 text-sm uppercase">Tu Pedido</span>
                    <x-button icon="o-x-mark" class="btn-ghost btn-sm btn-circle border border-warning text-warning hover:bg-warning hover:text-warning-content" wire:click="$toggle('showCart')" />
                </div>

                {{-- ZONA SCROLLEABLE --}}
                <div class="flex-1 overflow-y-auto relative">
                    {{-- Desktop Header --}}
                    <div class="hidden md:grid grid-cols-[80px_1fr_100px_120px_100px_48px] gap-2 items-center font-bold text-slate-300 text-sm px-4 py-3 sticky top-0 bg-slate-800 z-10 border-b border-slate-700 shadow-sm">
                        <div class="text-center">Imagen</div>
                        <div class="text-left">Producto</div>
                        <div class="text-right">Precio</div>
                        <div class="text-center">Cantidad</div>
                        <div class="text-right">Total</div>
                        <div class="text-center">
                            <x-button icon="o-x-mark" class="btn-ghost btn-sm btn-circle border border-warning text-warning hover:bg-warning hover:text-warning-content" wire:click="$toggle('showCart')" />
                        </div>
                    </div>

                    <div class="flex flex-col">
                        @foreach ($cart as $item)
                            <div class="even:bg-slate-100/5 odd:bg-slate-100/10 p-3 md:p-2 border-b border-slate-700/50 flex flex-col md:grid md:grid-cols-[80px_1fr_100px_120px_100px_48px] gap-2 md:items-center relative">
                                
                                {{-- Top Section on Mobile (Image + Title) --}}
                                <div class="flex items-start gap-3 mb-2 md:mb-0 md:contents">
                                    {{-- Image --}}
                                    <div class="shrink-0 w-20 h-20 md:w-16 md:h-16 flex justify-center md:mx-auto">
                                        <x-image-proxy url="{{ config('services.regente.base_url') . $item['product_id'] . '.jpg' }}"
                                            alt="{{ $item['product_id'] }}" class="w-full h-full object-cover rounded" />
                                    </div>
                                    
                                    {{-- Title --}}
                                    <div class="flex-1 font-bold text-sm leading-tight pr-6 md:pr-0">
                                        {{ $item['name'] }}
                                        @if (isset($item['product_model']) && $item['product_model']->hasBonus())
                                            <br>
                                            <div class="text-[10px] font-black text-red-500 bg-red-500/10 px-2 py-0.5 rounded-sm inline-block mt-1">
                                                {{ $item['product_model']->bonus_label }}
                                            </div>
                                        @endif
                                    </div>
                                </div>
                                
                                {{-- Delete Button (Mobile Absolute overlay) --}}
                                <div class="absolute top-2 right-2 md:hidden z-10">
                                    <x-dropdown>
                                        <x-slot:trigger>
                                            <x-button icon="o-trash" class="text-red-500 btn-ghost btn-sm btn-circle" />
                                        </x-slot:trigger>
                                        <x-menu-item title="Confirmar" icon="o-check"
                                            wire:click="removeFromCart({{ $item['product_id'] }})" />
                                        <x-menu-item title="Cancelar" icon="o-x-mark" />
                                    </x-dropdown>
                                </div>

                                {{-- Details Grid for Mobile / Columns for Desktop --}}
                                <div class="grid grid-cols-3 gap-2 md:contents items-center text-center">
                                    <div class="text-left md:text-right flex flex-col justify-center h-full {{ ($item['is_price_changed'] ?? false) ? 'text-red-500 font-bold' : '' }}">
                                        <span class="md:hidden text-[10px] uppercase text-slate-400">Precio</span>
                                        ${{ number_format($item['price'], 2) }}
                                        @if($item['is_price_changed'] ?? false)
                                            <div class="text-[10px] text-red-400 leading-tight">Act: ${{ number_format($item['current_price'], 2) }}</div>
                                        @endif
                                    </div>
                                    <div class="flex flex-col items-center">
                                        <span class="md:hidden text-[10px] uppercase text-slate-400 mb-1">Cant</span>
                                        <input type="number" min="{{ $item['bulkQuantity'] }}" step="{{ $item['bulkQuantity'] }}"
                                            wire:change="updateQuantity({{ $item['product_id'] }}, $event.target.value)"
                                            wire:key="cart-{{ $item['product_id'] }}-{{ $item['quantity'] }}" id="cart-{{ $item['product_id'] }}-{{ $item['quantity'] }}"
                                            value="{{ $item['quantity'] }}" class="input input-sm input-bordered w-full max-w-[4rem] md:max-w-[5rem] text-center {{ ($item['is_stock_insufficient'] ?? false) ? 'border-red-500 bg-red-500/10' : '' }}" />

                                        @if($item['is_stock_insufficient'] ?? false)
                                            <div class="text-[10px] text-red-500 font-bold text-center mt-1">Disp: {{ $item['available_stock'] }}</div>
                                        @endif

                                        <div class="mt-1">
                                            @if($item['quantity'] % $item['bulkQuantity'] === 0)
                                                <x-icon name="o-squares-2x2" class="w-4 h-4"
                                                    label="{{ $item['quantity'] / $item['bulkQuantity']}} x {{ $item['bulkQuantity'] }}" />
                                            @else
                                                <x-icon name="o-squares-plus" class="w-4 h-4"
                                                    label="{{ floor($item['quantity'] / $item['bulkQuantity']) }} x {{ $item['bulkQuantity'] }} + {{ $item['quantity'] - (floor($item['quantity'] / $item['bulkQuantity']) * $item['bulkQuantity']) }}" />
                                            @endif
                                        </div>
                                    </div>
                                    <div class="text-right flex flex-col justify-center h-full font-bold">
                                        <span class="md:hidden text-[10px] uppercase text-slate-400">Total</span>
                                        ${{ number_format($item['total_price'], 2) }}
                                    </div>
                                </div>
                                
                                {{-- Desktop Delete Button --}}
                                <div class="hidden md:flex justify-center">
                                    <x-dropdown>
                                        <x-slot:trigger>
                                            <x-button icon="o-trash" class="text-red-500 w-full btn-ghost btn-sm" />
                                        </x-slot:trigger>
                                        <x-menu-item title="Confirmar" icon="o-check"
                                            wire:click="removeFromCart({{ $item['product_id'] }})" />
                                        <x-menu-item title="Cancelar" icon="o-x-mark" />
                                    </x-dropdown>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- PIE FIJO --}}
                <div class="shrink-0 bg-slate-900 border-t border-slate-700 px-4 py-3 shadow-[0_-10px_15px_-3px_rgba(0,0,0,0.3)]">
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 items-center">
                        <x-button link="{{ route('checkout') }}" label="Confirmar Pedido" icon="o-check" class="btn-success w-full text-xs sm:text-sm" />
                        <x-button wire:click="saveCart()" label="Guardar" icon="o-shopping-cart" class="btn-warning w-full text-xs sm:text-sm" />
                        <div class="w-full">
                            <x-dropdown>
                                <x-slot:trigger>
                                    <x-button icon="o-trash" label="Vaciar" class="btn-error w-full text-xs sm:text-sm" />
                                </x-slot:trigger>
                                <x-menu-item title="Confirmar Vaciar" icon="o-check" wire:click="emptyCart" class="text-red-500" />
                                <x-menu-item title="Cancelar" icon="o-x-mark" />
                            </x-dropdown>
                        </div>
                        <div class="text-right flex items-center justify-end h-full">
                            <h3 class="text-2xl sm:text-3xl font-black text-white leading-none">
                                ${{ number_format($total, 2, ',', '.') }}
                            </h3>
                        </div>
                    </div>
                </div>

            </div>
        @else
            <p>El carrito está vacío.</p>
        @endif
    </x-drawer>
</div>
