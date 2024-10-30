<?php

use Livewire\Volt\Component;

new class extends Component {
    public $orderId;
    public $items = [];

    // Recibir el pedido y cargar los items al iniciar
    public function mount($orderId)
    {
        $this->orderId = (int)$orderId;
        $order = \App\Models\Order::with('items.product')->findOrFail($orderId);
        $this->items = $order->items->toArray();
    }

    // Actualizar el carrito y redirigir a la tienda
    public function editOrder()
    {
        // Rearmar el carrito con los items del pedido
        $cartItems = [];
        foreach ($this->items as $item) {
            $cartItems[$item['product_id']] = [
                'product_id' => $item['product_id'],
                'name' => $item['product']['description'],
                'quantity' => $item['quantity'],
                'price' => $item['price'],
                'byBulk' => $item['product']['by_bulk']??false,
            ];
        }
        // guardar los datos del la orden para actualizarlo luego
        Session::put('updateOrder', $this->orderId);

        // Guardar los datos del carrito en la sesión
        Session::put('cart', $cartItems);

        // Redirigir a la tienda
        return redirect('/');
    }

}; ?>

<div>
    <x-header title="Items del Pedido #{{ $orderId }}" size="text-xl" />

    <table class="table w-full rounded-sm overflow-hidden">
        <thead class="text-sm font-bold bg-slate-200/25 text-center">
            <tr>
                <th>Producto</th>
                <th>Cantidad</th>
                <th>Precio</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($items as $index => $item)
                <tr class="even:bg-slate-100/5 odd:bg-slate-100/10">
                    <td>{{ $item['product']['description'] }}</td>
                    <td class="text-center">
                        {{ $item['quantity'] }}
                    </td>
                    <td class="text-right">
                        ${{ number_format($item['price'], 2) }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <x-button wire:click="editOrder" class="mt-2 btn-primary" label="Actualizar y Volver a la Tienda" />

</div>
