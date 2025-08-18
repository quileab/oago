<?php

use Livewire\Volt\Component;
use Mary\Traits\Toast; // Added this line

new class extends Component {
    use Toast; // Added this line
    public $order;
    public $items = [];
    public string $newStatus = '';

    // Recibir el pedido y cargar los items al iniciar
    public function mount($orderId)
    {
        $this->order = \App\Models\Order::with('items.product')->findOrFail($orderId);
        $this->items = $this->order->items->toArray();
    }

    // Actualizar el carrito y redirigir a la tienda
    public function loadCart(bool $update = false)
    {
        // Rearmar el carrito con los items del pedido
        $cartItems = [];
        foreach ($this->items as $item) {
            // obtener el precio actualizado del producto según el usuario
            $prod = \App\Models\ListPrice::where('product_id', $item['product_id'])
                ->where('list_id', auth()->user()->list_id)
                ->first();
            // si el producto no tiene precio de lista, usar el precio del pedido
            $item['price'] = $prod->price ?? $item['price'];


            $cartItems[$item['product_id']] = [
                'product_id' => $item['product_id'],
                'name' => $item['product']['description'],
                'quantity' => $item['quantity'],
                'price' => $item['price'],
                'bulkQuantity' => $item['product']['qtty_package'],
                'byBulk' => $item['product']['by_bulk'] ?? false,
            ];
        }

        if ($update) {
            // guardar los datos de la orden para actualizarlo luego
            Session::put('updateOrder', $this->order->id);
        }

        // Guardar los datos del carrito en la sesión
        Session::put('cart', $cartItems);

        // Redirigir a la tienda
        return redirect('/');
    }

    // delete order
    public function delete()
    {
        $this->order->delete();
        return redirect('/orders');
    }

    public function changeStatus(string $status): void
    {
        $this->order->status = $status;
        $this->order->save();

        $this->success('Estado del pedido actualizado a ' . $status, position: 'toast-bottom');
    }

}; ?>

<div>
    <div class="grid grid-cols-3 mb-2">
        <div>
            <x-button label="Volver" icon="o-arrow-left" class="btn-primary" onclick="window.history.back()" />
            <h3 class="text-2xl"><small class="text-primary">Pedido #</small> {{ $order->id }}</h3>
            <h3 class="text-2xl"><small class="text-primary">Importe:</small>
                ${{ number_format($order->total_price, 2, ',', '.') }}</h3>
        </div>
        <div>
            <h3 class="text-2xl"><small class="text-primary">Estado:</small>
                {{ \App\Models\Order::orderStates($order->status) }}</h3>
            {{-- Si el estado no es completado poder cambiar el estado --}}
            @if($order->status != 'completed' && Auth::user()->role == 'admin')
                <x-dropdown label="Cambiar Estado" icon="o-arrow-path-rounded-square" class="btn-primary w-full mt-2">
                    @foreach(['pending', 'on-hold', 'cancelled'] as $statusOption)
                        @if($statusOption != $order->status)
                            <x-menu-item title="{{ \App\Models\Order::orderStates($statusOption) }}"
                                wire:click="changeStatus('{{ $statusOption }}')"
                                wire:confirm="¿Está seguro de cambiar el estado a {{ \App\Models\Order::orderStates($statusOption) }}?"
                                spinner="changeStatus('{{ $statusOption }}')" />
                        @endif
                    @endforeach
                </x-dropdown>
            @endif
        </div>
        <div>
            @if($order->status == 'on-hold')
                <x-button wire:click="loadCart(true)" icon="o-shopping-cart" class="btn-primary w-full mt-2"
                    label="Retomar Pedido" />
                <div class="flex gap-1">
                    <x-alert title="IMPORTANTE"
                        description="Al retomar el pedido, los precios pueden sufrir actualizaciones"
                        icon="o-exclamation-triangle" class="bg-yellow-500/50" />
                    <x-dropdown label="Eliminar" icon="o-trash" class="btn-error w-full mt-1">
                        <x-menu-item title="Confirmar" wire:click.stop="delete" spinner="delete" icon="o-trash"
                            class="bg-error" />
                    </x-dropdown>
                </div>
            @endif
            @if($order->status == 'pending')
                <x-button wire:click="loadCart(false)" icon="o-shopping-cart" class="mt-2 btn-primary w-full"
                    label="Copiar Pedido" />
            @endif

        </div>
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
</div>