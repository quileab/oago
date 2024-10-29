<?php

use Livewire\Volt\Component;

new class extends Component {
    public $orderId;
    public $items = [];

    // Recibir el pedido y cargar los items al iniciar
    public function mount($orderId)
    {
        $this->orderId = (int)$orderId;

        $order = Order::find($orderId);
        $this->items = $order->items->toArray();
        dd($this->items);
    }

    // Actualizar el carrito y redirigir a la tienda
    public function editOrder()
    {
        // Rearmar el carrito con los items del pedido
        $cartItems = [];
        foreach ($this->items as $item) {
            $cartItems[$item['product_id']] = [
                'quantity' => $item['quantity'],
                'price' => $item['price']
            ];
        }

        // Guardar los datos del carrito en la sesión
        Session::put('cart', $cartItems);

        // Redirigir a la tienda
        return redirect('/');
    }

}; ?>

<div>
    <h2>Editar Items del Pedido #{{ $orderId }}</h2>

    <table>
        <thead>
            <tr>
                <th>Producto</th>
                <th>Cantidad</th>
                <th>Precio</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($items as $index => $item)
                <tr>
                    <td>{{ $item['product']['description'] }}</td>
                    <td>
                        <input type="number" wire:model="items.{{ $index }}.quantity" min="1">
                    </td>
                    <td>
                        <input type="number" wire:model="items.{{ $index }}.price" step="0.01">
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <button wire:click="editOrder" class="btn btn-primary">Actualizar y Volver a la Tienda</button>

</div>
