<div class="fixed bottom-4 right-4 z-50">
    <x-button wire:click="$toggle('showCart')"
        icon="o-shopping-cart" class="btn-circle btn-warning relative">
        @if (count($cart) > 0)
        <x-badge value="{{ count($cart) }}" class="badge-info absolute -right-2 -top-2" />
        @endif
    </x-button>

    <x-drawer wire:model="showCart" class="w-11/12 lg:w-2/3 text-gray-50" right
        title="Compra {{ Session::get('updateOrder')?'Actualizando #'.Session::get('updateOrder'):'Nueva' }}"
        with-close-button
        close-on-escape>

    @if (count($cart) > 0)
    <table class="w-full">
        <thead class="font-bold bg-slate-200/25 text-center">
            <tr>
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
                    <td>{{ $item['name'] }}</td>
                    <td class="text-right">${{ number_format($item['price'], 2) }}</td>
                    <td class="px-4">
                        <x-input type="number" min="1" wire:change="updateQuantity({{ $item['product_id'] }}, $event.target.value)" value="{{ $item['quantity'] }}"
                            class="w-16" />
                    </td>
                    <td class="text-right">${{ number_format($item['price'] * $item['quantity'], 2) }}</td>
                    <td class="text-center">
                        <x-button icon="o-trash" wire:click="removeFromCart({{ $item['product_id'] }})" class="btn-ghost btn-sm text-red-500" />
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="p-2 grid grid-cols-3 gap-3">
        <x-button wire:click="placeOrder" label="Confirmar Pedido" icon="o-check" class="btn-success" />
        <div></div>  
        <h3 class="text-2xl"><small class="text-primary">Total:</small> ${{ number_format($total, 2, ',', '.') }}</h3>
    </div>
    @else
        <p>El carrito está vacío.</p>
    @endif
    </x-drawer>
</div>