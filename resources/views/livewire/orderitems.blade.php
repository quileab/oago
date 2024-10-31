<?php

use Livewire\Volt\Component;

new class extends Component {
    public $order;
    public $items = [];

    // Recibir el pedido y cargar los items al iniciar
    public function mount($orderId)
    {
        $this->order = \App\Models\Order::with('items.product')->findOrFail($orderId);
        $this->items = $this->order->items->toArray();
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
        Session::put('updateOrder', $this->order->id);

        // Guardar los datos del carrito en la sesión
        Session::put('cart', $cartItems);

        // Redirigir a la tienda
        return redirect('/');
    }

}; ?>

<div>
    <div class="grid grid-cols-2 gap-4">
        <h3 class="text-2xl"><small class="text-primary">Pedido #</small> {{ $order->id }}</h3>
        <h3 class="text-2xl"><small class="text-primary">Estado:</small> {{ $order->status }}</h3>
    </div>

    <table class="table w-full rounded-sm overflow-hidden">
        <thead class="text-sm font-bold bg-slate-200/25 text-center">
            <tr>
                <th>Prod. ID</th>
                <th>Producto</th>
                <th>Cantidad</th>
                <th>Precio</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($items as $index => $item)
                <tr class="even:bg-slate-100/5 odd:bg-slate-100/10">
                    <td class="text-center">{{ $item['product_id'] }}</td>
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

    <x-button wire:click="editOrder" icon="o-shopping-cart" class="mt-2 btn-primary" label="Retomar Pedido" />

</div>
