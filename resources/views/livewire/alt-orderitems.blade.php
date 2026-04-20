<?php

use App\Models\AltOrder;
use Livewire\Volt\Component;
use Mary\Traits\Toast;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

new class extends Component {
    use Toast;
    public $order;
    public $items = [];

    public function mount($orderId)
    {
        $this->order = AltOrder::with('items.product')->findOrFail($orderId);
        $this->items = $this->order->items->toArray();
    }

    public function loadCart(bool $update = false)
    {
        $cartItems = [];
        $user = current_user();
        
        foreach ($this->items as $item) {
            $prod = \App\Models\ListPrice::where('product_id', $item['product_id'])
                ->where('list_id', $user->list_id)
                ->first();
                
            $item['price'] = $prod->price ?? $item['price'];

            $cartItems[$item['product_id']] = [
                'product_id' => $item['product_id'],
                'name' => $item['product']['description'],
                'quantity' => $item['quantity'],
                'price' => $item['price'],
                'bulkQuantity' => $item['product']['qtty_package'],
                'byBulk' => ($item['quantity'] % ($item['product']['qtty_package'] ?? 1)) == 0,
            ];
        }

        if ($update) {
            Session::put('updateOrder', $this->order->id);
        }

        Session::put('cart', $cartItems);
        return redirect('/');
    }

    public function delete()
    {
        $this->order->delete();
        return redirect('/alt-orders');
    }

    public function changeStatus(string $status): void
    {
        $this->order->update(['status' => $status]);
        $this->success('Estado del pedido actualizado a ' . AltOrder::orderStates($status), position: 'toast-bottom');
    }
}; ?>

<div>
    {{-- Encabezado de Impresión (Solo visible al imprimir) --}}
    <div class="print-only print-header mb-8">
        <div class="flex justify-between items-start">
            <div>
                <img src="{{ asset('imgs/brand-logo.webp') }}" class="w-32 mb-2" alt="Logo">
                <p class="text-xs font-bold uppercase tracking-widest text-slate-500">Comprobante de Pedido Externo</p>
            </div>
            <div class="text-right">
                <h1 class="text-2xl font-black uppercase">Pedido #ALT-{{ $order->id }}</h1>
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
                @if($order->transport_detail) <p><strong>Transporte:</strong> {{ $order->transport_detail }}</p> @endif
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6 items-start no-print">
        <div>
            <x-button label="Volver" icon="o-arrow-left" class="btn-outline btn-sm mb-4" onclick="window.history.back()" />
            <h2 class="text-3xl font-black uppercase tracking-tighter">Pedido <small class="text-primary">#ALT-{{ $order->id }}</small></h2>
            <p class="text-xl font-bold mt-2">TOTAL: <span class="text-primary">${{ number_format($order->total_price, 2, ',', '.') }}</span></p>
        </div>
        
        <div class="p-4 rounded-xl border border-base-300">
            <h3 class="font-bold uppercase text-xs text-slate-500 mb-2">Estado y Gestión</h3>
            <div class="flex flex-col gap-2">
                <x-badge :value="\App\Models\Order::orderStates($order->status)" class="w-full py-3 text-sm font-bold" />
                
                @if($order->status != 'completed' && Auth::user()->role->value == 'admin')
                    <x-dropdown label="Cambiar Estado" icon="o-arrow-path-rounded-square" class="btn-primary btn-sm w-full">
                        @foreach(['pending', 'on-hold', 'cancelled', 'completed'] as $statusOption)
                            @if($statusOption != $order->status)
                                <x-menu-item title="{{ \App\Models\Order::orderStates($statusOption) }}"
                                    wire:click="changeStatus('{{ $statusOption }}')"
                                    wire:confirm="¿Está seguro de cambiar el estado?"
                                    spinner="changeStatus" />
                            @endif
                        @endforeach
                    </x-dropdown>
                @endif
            </div>
        </div>

        <div class="flex flex-col gap-2">
            @if($order->status == 'on-hold')
                <x-button wire:click="loadCart(true)" icon="o-shopping-cart" class="btn-primary w-full" label="Retomar Pedido" />
                <x-button wire:click="delete" icon="o-trash" class="btn-error btn-outline btn-sm w-full" label="Eliminar Pedido" wire:confirm="¿Borrar este pedido permanente?" />
            @endif
            
            @if($order->status == 'pending' || $order->status == 'completed')
                <x-button wire:click="loadCart(false)" icon="o-document-duplicate" class="btn-outline w-full" label="Copiar a Carrito" />
            @endif
            
            <x-button icon="o-printer" class="btn-neutral w-full" label="Imprimir Pedido" link="/alt-order/{{ $order->id }}/print" external target="_blank" />
        </div>
    </div>

    <x-card shadow>
        <table class="table w-full">
            <thead class="uppercase text-[10px] font-black tracking-widest text-slate-500">
                <tr>
                    <th class="w-20">ID</th>
                    <th>Producto</th>
                    <th class="text-center">Cantidad</th>
                    <th class="text-right">Precio</th>
                    <th class="text-right">Subtotal</th>
                </tr>
            </thead>
            <tbody class="text-sm">
                @foreach ($items as $item)
                    <tr class="hover:bg-base-200 transition-colors">
                        <td class="font-mono text-xs">{{ $item['product_id'] }}</td>
                        <td class="font-bold">{{ $item['product']['description'] }}</td>
                        <td class="text-center font-black">{{ $item['quantity'] }}</td>
                        <td class="text-right">$ {{ number_format($item['price'], 2, ',', '.') }}</td>
                        <td class="text-right font-black">$ {{ number_format($item['price'] * $item['quantity'], 2, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </x-card>
</div>