<div>
    <h2>Carrito de Compras</h2>

    @if (count($cart) > 0)
    <table>
        <thead>
            <tr>
                <th>Producto</th>
                <th>Precio</th>
                <th>Cantidad</th>
                <th>Total</th>
                <th>Por Bulto</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($cart as $item)
                <tr>
                    <td>{{ $item['name'] }}</td>
                    <td>${{ number_format($item['price'], 2) }}</td>
                    <td>
                        <input type="number" min="1" wire:change="updateQuantity({{ $item['product_id'] }}, $event.target.value)" value="{{ $item['quantity'] }}">
                    </td>
                    <td>${{ number_format($item['price'] * $item['quantity'], 2) }}</td>
                    <td>{{ $item['byBulk'] ? 'Sí' : 'No' }}</td>
                    <td>
                        <button wire:click="removeFromCart({{ $item['product_id'] }})">Eliminar</button>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

        <h3>Total: ${{ number_format($total, 2, ',', '.') }}</h3>

        <button wire:click="placeOrder">Confirmar Pedido</button>
    @else
        <p>El carrito está vacío.</p>
    @endif
</div>