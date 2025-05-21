<div class="fixed bottom-4 right-4 z-50">
    <x-button wire:click="$toggle('showCart')" icon="o-shopping-cart"
        class="btn-xl btn-circle bg-blue-700 relative text-white">
        @if (count($cart) > 0)
            <x-badge value="{{ count($cart) }}" class="bg-transparent absolute top-0 border-0" />
        @endif
    </x-button>

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
                                <img src="{{ env('qb_public_assets_path', '/public/storage/qb') }}/proxyImg.php?url=http://190.183.254.154/regente_img/{{ $item['product_id'] . '.jpg' }}"
                                    alt="{{ $item['product_id'] }}" class="w-16 h-16 object-cover" />
                            </td>
                            <td>{{ $item['name'] }}</td>
                            <td class="text-right">${{ number_format($item['price'], 2) }}</td>
                            <td class="px-2 w-[8rem]">
                                <x-input type="number" min="{{ $item['bulkQuantity'] }}" step="{{ $item['bulkQuantity'] }}"
                                    wire:change="updateQuantity({{ $item['product_id'] }}, $event.target.value)"
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