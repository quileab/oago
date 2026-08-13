<div>
    <div class="fixed bottom-4 right-4 z-50">
        @if (count($cart) > 0)
            <div id="cartCount" class="absolute -top-2 -right-2 z-20 bg-accent text-accent-content font-black text-sm w-7 h-7 flex items-center justify-center rounded-full shadow-lg border-2 border-base-100">
                {{ count($cart) }}
            </div>
        @endif
        <div id="cart-highlight" class="cartButton transition-transform hover:scale-110" wire:ignore>
            <button wire:click="$toggle('showCart')" class="btn btn-circle btn-primary btn-lg shadow-xl shadow-primary/30 border-2 border-primary-focus relative z-10 w-16 h-16">
                <x-icon name="o-shopping-cart" class="w-8 h-8" />
            </button>
        </div>
    </div>

    <x-drawer wire:model="showCart" class="w-11/12 lg:w-2/3 bg-base-100 text-base-content shadow-2xl border-l border-base-300 !p-0" style="padding: 0px !important;" right
        with-close-button close-on-escape>

        @if (count($cart) > 0)
            <div class="flex flex-col" style="height: 100dvh;">

                {{-- ENCABEZADO FIJO --}}
                <div class="shrink-0 bg-base-200 border-b border-base-300 px-4 py-3 flex items-center justify-between">
                    <h2 class="text-lg font-black text-primary flex items-center gap-2 uppercase tracking-wider">
                        <x-icon name="o-shopping-cart" class="w-5 h-5" /> Tu Pedido
                    </h2>
                    <x-button icon="o-x-mark" class="btn-ghost btn-sm btn-circle" wire:click="$toggle('showCart')" />
                </div>
                <div class="shrink-0 bg-base-200/50 px-2 py-2">
                    <table class="w-full table-compact table">
                        <thead class="font-bold text-base-content/70 text-center text-xs uppercase">
                            <tr>
                                <th class="w-20">Imagen</th>
                                <th class="text-left">Producto</th>
                                <th>Precio</th>
                                <th class="w-32">Cantidad</th>
                                <th>Total</th>
                                <th class="w-12"></th>
                            </tr>
                        </thead>
                    </table>
                </div>

                {{-- ZONA SCROLLEABLE --}}
                <div class="flex-1 overflow-y-auto bg-base-100">
                    <table class="w-full table-compact table">
                        <tbody>
                            @foreach ($cart as $item)
                                <tr class="border-b border-base-200/50 hover:bg-base-200/20 transition-colors">
                                    <td class="text-center w-20 p-2">
                                        <div class="w-16 h-16 rounded-md overflow-hidden border border-base-200 bg-white">
                                            <x-image-proxy url="{{ config('services.regente.base_url') . $item['product_id'] . '.jpg' }}"
                                                alt="{{ $item['product_id'] }}" class="w-full h-full object-contain" />
                                        </div>
                                    </td>
                                    <td class="p-2">
                                        <div class="font-bold text-sm text-base-content line-clamp-2 leading-tight">
                                            {{ $item['name'] }}
                                        </div>
                                        @if (isset($item['product_model']) && $item['product_model']->hasBonus())
                                            <div class="text-[10px] font-black text-accent bg-accent/10 px-2 py-0.5 rounded-sm inline-block mt-1">
                                                {{ $item['product_model']->bonus_label }}
                                            </div>
                                        @endif
                                    </td>
                                    <td class="text-right p-2 {{ ($item['is_price_changed'] ?? false) ? 'text-error font-bold' : 'text-base-content/80 font-medium' }}">
                                        ${{ number_format($item['price'], 2) }}
                                        @if($item['is_price_changed'] ?? false)
                                            <div class="text-[10px] text-error font-bold bg-error/10 px-1 rounded inline-block mt-0.5">Act: ${{ number_format($item['current_price'], 2) }}</div>
                                        @endif
                                    </td>
                                    <td class="p-2 w-32">
                                        <input type="number" min="{{ $item['bulkQuantity'] }}" step="{{ $item['bulkQuantity'] }}"
                                            wire:change="updateQuantity({{ $item['product_id'] }}, $event.target.value)"
                                            wire:key="cart-{{ $item['product_id'] }}-{{ $item['quantity'] }}" id="cart-{{ $item['product_id'] }}-{{ $item['quantity'] }}"
                                            value="{{ $item['quantity'] }}" class="input input-sm input-bordered w-full text-center font-bold {{ ($item['is_stock_insufficient'] ?? false) ? 'border-error bg-error/10 text-error' : 'bg-base-100 focus:border-primary' }}" />

                                        @if($item['is_stock_insufficient'] ?? false)
                                            <div class="text-[10px] text-error font-black text-center mt-1">Disp: {{ $item['available_stock'] }}</div>
                                        @endif

                                        <div class="text-[10px] text-base-content/50 font-bold text-center mt-1">
                                        @if($item['quantity'] % $item['bulkQuantity'] === 0)
                                            <x-icon name="o-cube" class="w-3 h-3 inline mr-0.5" /> {{ $item['quantity'] / $item['bulkQuantity']}} x {{ $item['bulkQuantity'] }}
                                        @else
                                            <x-icon name="o-cube" class="w-3 h-3 inline mr-0.5" /> {{ floor($item['quantity'] / $item['bulkQuantity']) }} x {{ $item['bulkQuantity'] }} <br/> + {{ $item['quantity'] - (floor($item['quantity'] / $item['bulkQuantity']) * $item['bulkQuantity']) }} un.
                                        @endif
                                        </div>
                                    </td>
                                    <td class="text-right p-2 font-black text-base-content text-lg">
                                        ${{ number_format($item['total_price'], 2) }}
                                    </td>
                                    <td class="text-center w-12 p-2">
                                        <x-dropdown>
                                            <x-slot:trigger>
                                                <x-button icon="o-trash" class="text-error/70 hover:text-error hover:bg-error/10 w-full btn-ghost btn-sm btn-circle transition-colors" />
                                            </x-slot:trigger>
                                            <div class="bg-base-100 border border-base-200 shadow-xl rounded-lg overflow-hidden z-[100]">
                                                <x-menu-item title="Eliminar" icon="o-trash" class="text-error hover:bg-error/10 font-bold"
                                                    wire:click="removeFromCart({{ $item['product_id'] }})" />
                                                <x-menu-item title="Cancelar" icon="o-x-mark" class="hover:bg-base-200" />
                                            </div>
                                        </x-dropdown>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- PIE FIJO --}}
                <div class="shrink-0 bg-base-200 border-t border-base-300 px-4 py-4 shadow-[0_-10px_20px_-5px_rgba(0,0,0,0.1)] relative z-10">
                    <div class="flex flex-col md:flex-row justify-between items-center gap-4">
                        <div class="flex flex-wrap sm:flex-nowrap gap-2 w-full md:w-auto">
                            <x-button link="{{ route('checkout') }}" label="CONFIRMAR PEDIDO" icon="o-check" class="btn-success magnetic hover:shadow-[0_0_25px_rgba(34,197,94,0.6)] hover:scale-105 transition-all duration-300 font-black tracking-wide text-xs flex-1 sm:flex-none" />
                            <x-button wire:click="saveCart()" label="Guardar" icon="o-bookmark" class="btn-info btn-outline hover:bg-info hover:text-info-content hover:border-info font-bold text-xs flex-1 sm:flex-none" />
                            <div class="flex-1 sm:flex-none">
                                <x-dropdown class="w-full">
                                    <x-slot:trigger>
                                        <x-button icon="o-trash" label="Vaciar" class="btn-error btn-outline hover:bg-error hover:text-error-content hover:border-error font-bold text-xs w-full" />
                                    </x-slot:trigger>
                                    <div class="bg-base-100 border border-base-200 shadow-xl rounded-lg overflow-hidden w-full z-[100]">
                                        <x-menu-item title="Sí, vaciar" icon="o-check" wire:click="emptyCart" class="text-error hover:bg-error/10 font-bold" />
                                        <x-menu-item title="Cancelar" icon="o-x-mark" class="hover:bg-base-200" />
                                    </div>
                                </x-dropdown>
                            </div>
                        </div>
                        <div class="text-right flex items-center justify-end w-full md:w-auto bg-base-100 px-4 py-2 rounded-lg border border-base-300 shadow-inner">
                            <span class="text-xs font-bold text-base-content/50 uppercase mr-3">Total:</span>
                            <h3 class="text-2xl sm:text-3xl font-black text-primary leading-none">
                                ${{ number_format($total, 2, ',', '.') }}
                            </h3>
                        </div>
                    </div>
                </div>

            </div>
        @else
            <div class="flex flex-col items-center justify-center h-full text-base-content/40 p-8 space-y-4 bg-base-100">
                <x-icon name="o-shopping-cart" class="w-24 h-24 opacity-20" />
                <p class="text-lg font-bold">El carrito está vacío.</p>
                <x-button label="Seguir Comprando" icon="o-arrow-left" class="btn-primary btn-outline mt-4" wire:click="$toggle('showCart')" />
            </div>
        @endif
    </x-drawer>
</div>
