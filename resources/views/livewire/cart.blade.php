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

                {{-- ENCABEZADO FIJO --}}
                <div class="shrink-0 bg-slate-800 border-b border-slate-700 px-2 py-2">
                    {{-- Vista Desktop/Mobile: cabecera de tabla --}}
                    <table class="w-full table-compact table">
                        <thead class="font-bold text-slate-300 text-center text-sm">
                            <tr>
                                <th class="w-20">Imagen</th>
                                <th class="text-left">Producto</th>
                                <th>Precio</th>
                                <th class="w-32">Cantidad</th>
                                <th>Total</th>
                                <th class="w-12">
                                    <x-button icon="o-x-mark" class="btn-ghost btn-sm btn-circle border border-warning text-warning hover:bg-warning hover:text-warning-content" wire:click="$toggle('showCart')" />
                                </th>
                            </tr>
                        </thead>
                    </table>
                </div>

                {{-- ZONA SCROLLEABLE --}}
                <div class="flex-1 overflow-y-auto">

                    {{-- Vista Desktop/Mobile: filas de tabla --}}
                    <table class="w-full table-compact table">
                        <tbody>
                            @foreach ($cart as $item)
                                <tr class="even:bg-slate-100/5 odd:bg-slate-100/10">
                                    <td class="text-center w-20">
                                        <x-image-proxy url="{{ config('services.regente.base_url') . $item['product_id'] . '.jpg' }}"
                                            alt="{{ $item['product_id'] }}" class="w-16 h-16 object-cover" />
                                    </td>
                                    <td>
                                        {{ $item['name'] }}
                                        @if (isset($item['product_model']) && $item['product_model']->hasBonus())
                                            <div class="text-xs font-bold text-red-500 mt-1">
                                                {{ $item['product_model']->bonus_label }}
                                            </div>
                                        @endif
                                    </td>
                                    <td class="text-right {{ ($item['is_price_changed'] ?? false) ? 'text-red-500 font-bold' : '' }}">
                                        ${{ number_format($item['price'], 2) }}
                                        @if($item['is_price_changed'] ?? false)
                                            <div class="text-[10px] text-red-400">Actual: ${{ number_format($item['current_price'], 2) }}</div>
                                        @endif
                                    </td>
                                    <td class="px-2 w-32">
                                        <input type="number" min="{{ $item['bulkQuantity'] }}" step="{{ $item['bulkQuantity'] }}"
                                            wire:change="updateQuantity({{ $item['product_id'] }}, $event.target.value)"
                                            wire:key="cart-{{ $item['product_id'] }}-{{ $item['quantity'] }}" id="cart-{{ $item['product_id'] }}-{{ $item['quantity'] }}"
                                            value="{{ $item['quantity'] }}" class="input input-bordered w-full text-center {{ ($item['is_stock_insufficient'] ?? false) ? 'border-red-500 bg-red-500/10' : '' }}" />

                                        @if($item['is_stock_insufficient'] ?? false)
                                            <div class="text-[10px] text-red-500 font-bold text-center mt-1">Disp: {{ $item['available_stock'] }}</div>
                                        @endif

                                        @if($item['quantity'] % $item['bulkQuantity'] === 0)
                                            <x-icon name="o-squares-2x2"
                                                label="{{ $item['quantity'] / $item['bulkQuantity']}} x {{ $item['bulkQuantity'] }}" />
                                        @else
                                            <x-icon name="o-squares-plus"
                                                label="{{ floor($item['quantity'] / $item['bulkQuantity']) }} x {{ $item['bulkQuantity'] }} + {{ $item['quantity'] - (floor($item['quantity'] / $item['bulkQuantity']) * $item['bulkQuantity']) }}" />
                                        @endif
                                    </td>
                                    <td class="text-right">${{ number_format($item['total_price'], 2) }}</td>
                                    <td class="text-center w-12">
                                        <x-dropdown>
                                            <x-slot:trigger>
                                                <x-button icon="o-trash" class="text-red-500 w-full btn-ghost btn-sm" />
                                            </x-slot:trigger>
                                            <x-menu-item title="Confirmar" icon="o-check"
                                                wire:click="removeFromCart({{ $item['product_id'] }})" />
                                            <x-menu-item title="Cancelar" icon="o-x-mark" />
                                        </x-dropdown>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
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
