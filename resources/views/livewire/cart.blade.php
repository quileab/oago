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

    <x-drawer wire:model="showCart" class="w-11/12 lg:w-2/3 text-gray-50 flex flex-col h-full" right
        title="Compra {{ Session::get('updateOrder') ? 'Actualizando #' . Session::get('updateOrder') : 'Nueva' }}"
        with-close-button close-on-escape>

        @if (count($cart) > 0)
            <div class="flex flex-col h-full -mt-4">
                {{-- Contenedor scrolleable de items --}}
                <div class="flex-1 overflow-y-auto pr-2 pb-4 space-y-4 md:space-y-0">
                    
                    {{-- Vista Desktop: Tabla --}}
                    <div class="hidden md:block overflow-x-auto">
                        <table class="w-full cellspacing-x-1 table-compact table">
                            <thead class="font-bold bg-slate-200/25 text-center">
                                <tr>
                                    <th>Prod. ID</th>
                                    <th>Producto</th>
                                    <th>Precio</th>
                                    <th>Cantidad</th>
                                    <th>Total</th>
                                    <th><x-icon name="o-cog-8-tooth" /></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($cart as $item)
                                    <tr class="even:bg-slate-100/5 odd:bg-slate-100/10">
                                        <td class="text-center">
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
                                        <td class="px-2 w-[8rem]">
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
                                                    label="{{floor($item['quantity'] / $item['bulkQuantity'])}} x {{ $item['bulkQuantity']}} + {{ $item['quantity'] - (floor($item['quantity'] / $item['bulkQuantity']) * $item['bulkQuantity'])}}" />
                                            @endif
                                        </td>
                                        <td class="text-right">${{ number_format($item['total_price'], 2) }}</td>
                                        <td class="text-center">
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

                    {{-- Vista Mobile: Cards --}}
                    <div class="md:hidden space-y-3 mt-4">
                        @foreach ($cart as $item)
                            <div class="bg-slate-800 rounded-2xl p-4 shadow-md border border-slate-700 flex flex-col relative">
                                <div class="absolute top-2 right-2">
                                    <x-dropdown>
                                        <x-slot:trigger>
                                            <x-button icon="o-trash" class="text-red-500 btn-circle btn-ghost btn-sm" />
                                        </x-slot:trigger>
                                        <x-menu-item title="Confirmar" icon="o-check" wire:click="removeFromCart({{ $item['product_id'] }})" />
                                        <x-menu-item title="Cancelar" icon="o-x-mark" />
                                    </x-dropdown>
                                </div>
                                
                                <div class="flex items-start gap-4 mb-3">
                                    <div class="w-20 h-20 rounded-lg overflow-hidden shrink-0 bg-white">
                                        <x-image-proxy url="{{ config('services.regente.base_url') . $item['product_id'] . '.jpg' }}" alt="{{ $item['product_id'] }}" class="w-full h-full object-cover" />
                                    </div>
                                    <div class="flex-1 pr-6">
                                        <h4 class="font-bold text-sm leading-tight text-white line-clamp-2">{{ $item['name'] }}</h4>
                                        <div class="text-xs text-slate-400 mt-1 font-mono">ID: {{ $item['product_id'] }}</div>
                                        @if (isset($item['product_model']) && $item['product_model']->hasBonus())
                                            <div class="text-xs font-bold text-red-400 mt-1 bg-red-900/20 px-2 py-0.5 rounded inline-block">
                                                {{ $item['product_model']->bonus_label }}
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-4 items-center bg-slate-900/50 p-3 rounded-xl">
                                    <div>
                                        <div class="text-xs text-slate-400 mb-1">Precio Un.</div>
                                        <div class="font-bold {{ ($item['is_price_changed'] ?? false) ? 'text-red-500' : '' }}">
                                            ${{ number_format($item['price'], 2) }}
                                            @if($item['is_price_changed'] ?? false)
                                                <span class="block text-[10px] text-red-400">Actual: ${{ number_format($item['current_price'], 2) }}</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-xs text-slate-400 mb-1">Subtotal</div>
                                        <div class="font-black text-green-400 text-lg">${{ number_format($item['total_price'], 2) }}</div>
                                    </div>
                                </div>

                                <div class="mt-3 flex items-center justify-between gap-3">
                                    <div class="text-xs text-slate-400 w-1/3">
                                        Cantidad:
                                        @if($item['is_stock_insufficient'] ?? false)
                                            <div class="text-[10px] text-red-500 font-bold">Disp: {{ $item['available_stock'] }}</div>
                                        @endif
                                    </div>
                                    <div class="flex-1">
                                        <input type="number" min="{{ $item['bulkQuantity'] }}" step="{{ $item['bulkQuantity'] }}"
                                            wire:change="updateQuantity({{ $item['product_id'] }}, $event.target.value)"
                                            wire:key="cart-mob-{{ $item['product_id'] }}-{{ $item['quantity'] }}" id="cart-mob-{{ $item['product_id'] }}-{{ $item['quantity'] }}"
                                            value="{{ $item['quantity'] }}" class="input input-bordered input-sm w-full text-center bg-slate-700 text-white {{ ($item['is_stock_insufficient'] ?? false) ? 'border-red-500' : '' }}" />
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Footer fijo inferior (Sticky Summary) --}}
                <div class="bg-slate-900 border-t border-slate-700 p-4 sm:p-6 -mx-6 -mb-6 mt-4 shrink-0 shadow-[0_-10px_15px_-3px_rgba(0,0,0,0.3)] z-20">
                    <div class="flex items-center justify-between mb-4">
                        <div class="text-slate-400 text-sm flex items-center gap-1">
                            <x-icon name="o-squares-2x2" class="w-4 h-4 text-warning" /> = Bultos
                        </div>
                        <h3 class="text-2xl font-black text-white">
                            <small class="text-primary text-sm uppercase tracking-widest font-bold block text-right">Total Pedido</small>
                            ${{ number_format($total, 2, ',', '.') }}
                        </h3>
                    </div>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <x-button link="{{ route('checkout') }}" label="Confirmar Pedido" icon="o-check" class="btn-success w-full sm:col-span-1" />
                        <x-button wire:click="saveCart()" label="Guardar" icon="o-shopping-cart" class="btn-warning w-full" />
                        <x-dropdown>
                            <x-slot:trigger>
                                <x-button icon="o-trash" label="Vaciar" class="btn-error w-full" />
                            </x-slot:trigger>
                            <x-menu-item title="Confirmar Vaciar" icon="o-check" wire:click="emptyCart" class="text-red-500" />
                            <x-menu-item title="Cancelar" icon="o-x-mark" />
                        </x-dropdown>
                    </div>
                </div>
            </div>
        @else
            <p>El carrito está vacío.</p>
        @endif
    </x-drawer>
</div>
