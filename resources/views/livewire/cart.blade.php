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

    <x-drawer wire:model="showCart" class="w-11/12 lg:w-2/3 text-gray-50" right
        title="Compra {{ Session::get('updateOrder') ? 'Actualizando #' . Session::get('updateOrder') : 'Nueva' }}"
        with-close-button close-on-escape>

        @if (count($cart) > 0)
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
                                <x-image-proxy url="http://190.183.254.154/regente_img/{{ $item['product_id'] . '.jpg' }}"
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
                            <td class="text-right">${{ number_format($item['price'], 2) }}</td>
                            <td class="px-2 w-[8rem]">
                                <x-input type="number" min="{{ $item['bulkQuantity'] }}" step="{{ $item['bulkQuantity'] }}"
                                    wire:change="updateQuantity({{ $item['product_id'] }}, $event.target.value)"
                                    wire:key="cart-{{ $item['product_id'] }}" id="cart-{{ $item['product_id'] }}"
                                    value="{{ $item['quantity'] }}" class="input-control w-full text-center" />
                                @if($item['quantity'] % $item['bulkQuantity'] === 0)
                                    <x-icon name="o-squares-2x2"
                                        label="{{ $item['quantity'] / $item['bulkQuantity']}} x {{ $item['bulkQuantity'] }}" />
                                @else
                                    <x-icon name="o-squares-plus"
                                        label="{{floor($item['quantity'] / $item['bulkQuantity'])}} x {{ $item['bulkQuantity']}} + {{ $item['quantity'] - (floor($item['quantity'] / $item['bulkQuantity']) * $item['bulkQuantity'])}}" />
                                @endif
                            </td>
                            <td class="text-right">${{ number_format($item['price'] * $item['quantity'], 2) }}</td>
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

            <div class="pt-2 grid grid-cols-3 gap-2">

                {{-- <x-button wire:click="placeOrder" label="Confirmar Pedido" icon="o-check" class="btn-success w-full" />
                --}}
                <x-button link="{{ route('checkout') }}" label="Confirmar Pedido" icon="o-check"
                    class="btn-success w-full" />
                <x-icon name="o-squares-2x2" label="= Cantidad de Bultos" class="text-warning" />
                <h3 class="text-2xl"><small class="text-primary">Total:</small> ${{ number_format($total, 2, ',', '.') }}
                </h3>
                <x-button wire:click="saveCart()" label="Guardar Carrito" icon="o-shopping-cart"
                    class="btn-warning w-full" />
                <x-dropdown>
                    <x-slot:trigger>
                        <x-button icon="o-trash" label="Vaciar Carrito" class="btn-error w-full" />
                    </x-slot:trigger>

                    <x-menu-item title="Confirmar" icon="o-check" wire:click="emptyCart" />
                    <x-menu-item title="Cancelar" icon="o-x-mark" />
                </x-dropdown>
            </div>
        @else
            <p>El carrito está vacío.</p>
        @endif
    </x-drawer>
</div>