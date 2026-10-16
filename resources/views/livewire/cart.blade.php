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

    <x-drawer wire:model="showCart" class="w-11/12 lg:w-2/3 bg-slate-100 text-slate-800 shadow-2xl border-l border-slate-300 !p-0" style="padding: 0px !important;" right
        without-close-button close-on-escape>

        @if (count($cart) > 0)
            {{-- Layout manual con 3 zonas fijas: header, scroll, footer --}}
            <div class="flex flex-col" style="height: 100dvh;">

                {{-- ENCABEZADO FIJO --}}
                <div class="shrink-0 bg-slate-200 border-b border-slate-300 px-2 py-2">
                    {{-- Vista Desktop/Mobile: cabecera de tabla --}}
                    <table class="w-full table-compact table">
                        <thead class="font-bold text-slate-700 text-center text-sm">
                            <tr>
                                <th class="w-20">Imagen</th>
                                <th class="text-left">Producto</th>
                                <th>Precio</th>
                                <th class="w-32">Cantidad</th>
                                <th>Total</th>
                                <th class="w-12">
                                    <x-button icon="o-x-mark" class="btn-ghost btn-sm btn-circle border border-slate-400 text-slate-700 hover:bg-slate-300 hover:text-slate-900" wire:click="$toggle('showCart')" />
                                </th>
                            </tr>
                        </thead>
                    </table>
                </div>

                {{-- ZONA SCROLLEABLE --}}
                <div class="flex-1 overflow-y-auto bg-slate-100">

                    {{-- Vista Desktop/Mobile: filas de tabla --}}
                    <table class="w-full table-compact table">
                        <tbody>
                            @foreach ($cart as $item)
                                <tr class="even:bg-slate-200/70 odd:bg-slate-100 border-b border-slate-200">
                                    <td class="text-center w-20">
                                        <x-image-proxy url="{{ config('services.regente.base_url') . $item['product_id'] . '.jpg' }}"
                                            alt="{{ $item['product_id'] }}" class="w-16 h-16 object-cover rounded border border-slate-300 bg-white" />
                                    </td>
                                    <td class="font-medium text-slate-800">
                                        {{ $item['name'] }}
                                        @if (isset($item['product_model']) && $item['product_model']->hasBonus())
                                            <div class="text-xs font-bold text-red-600 mt-1">
                                                {{ $item['product_model']->bonus_label }}
                                            </div>
                                        @endif
                                    </td>
                                    <td class="text-right {{ ($item['is_price_changed'] ?? false) ? 'text-red-600 font-bold' : 'text-slate-700' }}">
                                        ${{ number_format($item['price'], 2) }}
                                        @if($item['is_price_changed'] ?? false)
                                            <div class="text-[10px] text-red-500">Actual: ${{ number_format($item['current_price'], 2) }}</div>
                                        @endif
                                    </td>
                                    <td class="px-2 w-32 text-slate-700">
                                        <input type="number" min="{{ $item['bulkQuantity'] }}" step="{{ $item['bulkQuantity'] }}"
                                            wire:change="updateQuantity({{ $item['product_id'] }}, $event.target.value)"
                                            wire:key="cart-{{ $item['product_id'] }}-{{ $item['quantity'] }}" id="cart-{{ $item['product_id'] }}-{{ $item['quantity'] }}"
                                            value="{{ $item['quantity'] }}" class="input input-bordered w-full text-center font-semibold {{ ($item['is_stock_insufficient'] ?? false) ? 'border-red-500 bg-red-50 text-red-700' : 'bg-white text-slate-900 border-slate-300' }}" />

                                        @if($item['is_stock_insufficient'] ?? false)
                                            <div class="text-[10px] text-red-600 font-bold text-center mt-1">Disp: {{ $item['available_stock'] }}</div>
                                        @endif

                                        <div class="text-xs text-slate-600 mt-1 text-center">
                                            @if($item['quantity'] % $item['bulkQuantity'] === 0)
                                                <x-icon name="o-squares-2x2"
                                                    label="{{ $item['quantity'] / $item['bulkQuantity']}} x {{ $item['bulkQuantity'] }}" />
                                            @else
                                                <x-icon name="o-squares-plus"
                                                    label="{{ floor($item['quantity'] / $item['bulkQuantity']) }} x {{ $item['bulkQuantity'] }} + {{ $item['quantity'] - (floor($item['quantity'] / $item['bulkQuantity']) * $item['bulkQuantity']) }}" />
                                            @endif
                                        </div>
                                    </td>
                                    <td class="text-right font-bold text-slate-900">${{ number_format($item['total_price'], 2) }}</td>
                                    <td class="text-center w-12">
                                        <x-dropdown>
                                            <x-slot:trigger>
                                                <x-button icon="o-trash" class="text-red-500 hover:bg-red-100 w-full btn-ghost btn-sm" />
                                            </x-slot:trigger>
                                            <div class="text-slate-700">
                                                <x-menu-item title="Confirmar" icon="o-check" class="text-red-600 font-semibold"
                                                    wire:click="removeFromCart({{ $item['product_id'] }})" />
                                                <x-menu-item title="Cancelar" icon="o-x-mark" />
                                            </div>
                                        </x-dropdown>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- PIE FIJO --}}
                <div class="shrink-0 bg-slate-200 border-t border-slate-300 px-4 py-3 shadow-[0_-10px_15px_-3px_rgba(0,0,0,0.08)]">
                    <div class="flex flex-wrap sm:flex-nowrap items-center justify-between gap-2 sm:gap-3">
                        <div class="flex items-center gap-2 w-full sm:w-auto flex-1">
                            <x-button link="{{ route('checkout') }}" label="Confirmar Pedido" icon="o-check" class="btn-success text-white flex-1 text-xs sm:text-sm" />

                            <x-dropdown>
                                <x-slot:trigger>
                                    <x-button label="Guardar" icon="o-bookmark" class="btn-warning text-xs sm:text-sm px-3" />
                                </x-slot:trigger>
                                <div class="text-slate-700 w-64 p-1">
                                    <div class="px-3 py-2 text-xs text-slate-600 border-b border-slate-100 whitespace-normal leading-relaxed">
                                        Guarda tu carrito <strong>en espera</strong> para retomarlo más tarde. <span class="text-amber-700 font-semibold">No finaliza ni envía el pedido.</span>
                                    </div>
                                    <x-menu-item title="Guardar para después" icon="o-bookmark" wire:click="saveCart()" class="text-amber-700 font-semibold" />
                                    <x-menu-item title="Cancelar" icon="o-x-mark" />
                                </div>
                            </x-dropdown>

                            <x-dropdown>
                                <x-slot:trigger>
                                    <x-button icon="o-trash" label="Vaciar" class="btn-error text-white text-xs sm:text-sm px-3" />
                                </x-slot:trigger>
                                <div class="text-slate-700">
                                    <x-menu-item title="Confirmar Vaciar" icon="o-check" wire:click="emptyCart" class="text-red-600 font-semibold" />
                                    <x-menu-item title="Cancelar" icon="o-x-mark" />
                                </div>
                            </x-dropdown>
                        </div>

                        <div class="text-right flex items-center justify-end w-full sm:w-auto shrink-0">
                            <h3 class="text-2xl sm:text-3xl font-black text-slate-900 leading-none">
                                ${{ number_format($total, 2, ',', '.') }}
                            </h3>
                        </div>
                    </div>
                </div>

            </div>
        @else
            <div class="flex flex-col items-center justify-center h-full text-center p-8 text-slate-600">
                <x-icon name="o-shopping-cart" class="w-20 h-20 text-slate-400 mb-4" />
                <p class="text-lg font-bold text-slate-700 mb-4">El carrito está vacío.</p>
                <x-button label="Seguir Comprando" icon="o-arrow-left" class="btn-primary btn-sm" wire:click="$toggle('showCart')" />
            </div>
        @endif
    </x-drawer>
</div>
