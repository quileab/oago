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
        $real = real_user();
        $acting = current_user();
        abort_if($this->order->user_id !== $acting?->id && $this->order->user_id !== $real?->id && $real?->role?->value !== 'admin', 403, 'Unauthorized');
        $this->items = $this->order->items->toArray();
    }

    // Actualizar el carrito y redirigir a la tienda
    public function loadCart(bool $update = false)
    {
        // Rearmar el carrito con los items del pedido
        $cartItems = [];
        $priceService = app(\App\Services\PriceListService::class);
        foreach ($this->items as $item) {
            // obtener el precio actualizado del producto según el usuario
            $prodModel = \App\Models\Product::find($item['product_id']);
            if ($prodModel) {
                $item['price'] = current_user()?->getProductPrice($prodModel) ?? $item['price'];
                $item['name'] = $item['product']['description'] ?? $prodModel->description;
            }

            // Precio 0 = producto sin precio: no se vende, se excluye del carrito.
            if (! $prodModel || ! $priceService->isSellable($priceService->resolveListId(current_user()), $prodModel)) {
                continue;
            }

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
        $real = real_user();
        $isOwner = $this->order->user_id === current_user()?->id;
        $isAdmin = $real?->role?->value === 'admin';
        abort_unless($isAdmin || ($isOwner && in_array($this->order->status->value, ['saved', 'on-hold'], true)), 403);
        $this->order->delete();
        return redirect('/orders');
    }

    public function changeStatus(string $status): void
    {
        abort_unless(real_user()?->role?->value === 'admin', 403);
        $resolved = \App\Enums\OrderStatus::tryFrom($status);
        if (! $resolved) {
            $this->error('Estado inválido.', position: 'toast-bottom');
            return;
        }
        $this->order->status = $resolved;
        $this->order->save();

        $this->success('Estado del pedido actualizado a ' . $resolved->label(), position: 'toast-bottom');
    }

}; ?>

<div>
    {{-- Encabezado de Impresión (Solo visible al imprimir) --}}
    <div class="print-only print-header mb-8">
        <div class="flex justify-between items-start">
            <div>
                <img src="{{ asset('imgs/Logos/isoER50.png') }}" class="w-32 mb-2" alt="Encendido Reconquista SRL">
                <p class="text-xs font-bold uppercase tracking-widest text-neutral-500">Comprobante de Pedido API</p>
            </div>
            <div class="text-right">
                <h1 class="text-2xl font-black uppercase">Pedido #{{ $order->id }}</h1>
                <p class="font-bold">Fecha: {{ $order->created_at->format('d/m/Y H:i') }}</p>
            </div>
        </div>
        
        <div class="grid grid-cols-2 gap-8 mt-6 border-t pt-4">
            <div>
                <h3 class="font-black text-sm uppercase mb-1 border-b">Datos del Cliente</h3>
                <p class="font-bold text-lg">{{ $order->user->lastname }}, {{ $order->user->name }}</p>
                <p>{{ $order->user->address }}</p>
                <p>{{ $order->user->city }} ({{ $order->user->postal_code }})</p>
                <p>Tel: {{ $order->user->phone }}</p>
            </div>
            <div>
                <h3 class="font-black text-sm uppercase mb-1 border-b">Detalles de Envío y Pago</h3>
                <p><strong>Envío:</strong> {{ $order->sending_method }}</p>
                <p><strong>Pago:</strong> {{ $order->payment_method }}</p>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 mb-2 no-print gap-4 items-start">
        <div>
            <x-button label="Volver" icon="o-arrow-left" class="btn-outline btn-sm mb-4" onclick="window.history.back()" />
            <h3 class="text-2xl font-black uppercase tracking-tighter"><small class="text-primary">Pedido #</small> {{ $order->id }}</h3>
            <h3 class="text-xl font-bold"><small class="text-primary">Importe:</small>
                ${{ number_format($order->total_price, 2, ',', '.') }}</h3>
        </div>
        <div>
            <h3 class="text-2xl"><small class="text-primary">Estado:</small>
                {{ \App\Models\Order::orderStates($order->status) }}</h3>
            {{-- Si el estado no es completado poder cambiar el estado --}}
            @if($order->status->value !== 'completed' && real_user()?->role?->value == 'admin')
                <x-dropdown label="Cambiar Estado" icon="o-arrow-path-rounded-square" class="btn-primary w-full mt-2">
                    @foreach(['pending', 'processing', 'on-hold', 'completed', 'cancelled'] as $statusOption)
                        @if($statusOption !== $order->status->value)
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
            @if(in_array($order->status->value, ['on-hold', 'saved'], true))
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
            @if($order->status->value === 'pending')
                <x-button wire:click="loadCart(false)" icon="o-shopping-cart" class="mt-2 btn-primary w-full"
                    label="Copiar Pedido" />
            @endif
            <x-button icon="o-printer" class="btn-neutral w-full mt-2" label="Imprimir Pedido" link="/order/{{ $order->id }}/print" external target="_blank" />
        </div>
    </div>

    <table class="table w-full rounded-sm overflow-hidden mt-4">
        <thead class="text-sm font-bold uppercase tracking-widest text-neutral-500">
            <tr>
                <th>Prod. ID</th>
                <th>Producto</th>
                <th class="text-center">Cantidad</th>
                <th class="text-right">Precio</th>
                <th class="text-right">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($items as $index => $item)
                <tr class="hover:bg-base-200 transition-colors">
                    <td class="font-mono text-xs">{{ $item['product_id'] }}</td>
                    <td class="font-bold">{{ $item['product']['description'] }}</td>
                    <td class="text-center font-black">
                        {{ $item['quantity'] }}
                    </td>
                    <td class="text-right">
                        $ {{ number_format($item['price'], 2, ',', '.') }}
                    </td>
                    <td class="text-right font-black">
                        $ {{ number_format($item['price'] * $item['quantity'], 2, ',', '.') }}
                    </td>
                </tr>
            @endforeach
        </tbody>
        <tfoot class="bg-base-200/50">
            <tr>
                <td colspan="4" class="text-right font-black uppercase">Total Pedido:</td>
                <td class="text-right font-black text-lg text-primary">$ {{ number_format($order->total_price, 2, ',', '.') }}</td>
            </tr>
        </tfoot>
    </table>
</div>