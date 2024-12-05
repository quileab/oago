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
    <table class="w-full cellspacing-x-2 table-compact table">
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
                    <td class="text-center">{{ $item['product_id'] }}</td>
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

    <div class="pt-2 grid grid-cols-3 gap-2">
        
        <x-button wire:click="placeOrder" label="Confirmar Pedido" icon="o-check" class="btn-success w-full" />
        <div></div>  
        <h3 class="text-2xl"><small class="text-primary">Total:</small> ${{ number_format($total, 2, ',', '.') }}</h3>
        <x-button wire:click="placeOrder('later')" label="Guardar Carrito" icon="o-shopping-cart" class="btn-warning w-full" />
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