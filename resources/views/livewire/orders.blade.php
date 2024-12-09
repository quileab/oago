<?php

use App\Models\Order;
use Illuminate\Support\Collection;
use Livewire\Volt\Component;
use Mary\Traits\Toast;

new class extends Component {
    use Toast;

    public string $search = '';

    public bool $drawer = false;

    public array $sortBy = ['column' => 'id', 'direction' => 'asc'];

    // Clear filters
    public function clear(): void
    {
        $this->reset();
        $this->success('Filters cleared.', position: 'toast-bottom');
    }

    // Delete action
    public function delete($id): void
    {
        Order::destroy($id);
        $this->success('Order deleted.', position: 'toast-bottom');
    }

    // Table headers
    public function headers(): array
    {
        return [
            ['key' => 'id', 'label' => '#', 'class' => 'w-1'],
            ['key' => 'user.fullName', 'label' => 'Name', 'class' => 'w-56'],
            ['key' => 'total_price', 'label' => 'Total', 'class' => 'w-20 text-right'],
            ['key' => 'order_date', 'label' => 'Fecha', 'class' => 'w-20'],
            ['key' => 'status', 'label' => 'Estado', 'class' => 'w-20'],
        ];
    }

    public function orders(): Collection
    {
        $isAdmin = Auth::user()->role == 'admin';
        return Order::with('user')->get()
            ->sortBy([[...array_values($this->sortBy)]])
            ->when(!$isAdmin, function (Collection $collection) {
                return $collection->where('user_id', Auth::user()->id);                
            })
            // search in full name
            ->when($this->search, function (Collection $collection) {
                return $collection->filter(function (Order $order) {
                    return Str::contains(strtolower($order->user->fullName), strtolower($this->search)) || Str::contains($order->id, $this->search);
                });
            }
        );
    }

    public function with(): array
    {
        return [
            'orders' => $this->orders(),
            'headers' => $this->headers()
        ];
    }
}; ?>

<div>
    <!-- HEADER -->
    <x-header title="Pedidos" separator progress-indicator>
        <x-slot:middle class="!justify-end">
            <x-input placeholder="buscar..." wire:model.live.debounce="search" clearable icon="o-magnifying-glass" />
        </x-slot:middle>
        <x-slot:actions>
            <x-button label="Filters" @click="$wire.drawer = true" responsive icon="o-funnel" />
        </x-slot:actions>
    </x-header>

    <!-- TABLE  -->
    <x-table :headers="$headers" :rows="$orders" :sort-by="$sortBy" 
        link="/order/{id}/edit"
        :cell-decoration="
        ['status' =>  [
            'bg-red-500/25' => fn(Order $order) => $order->status === 'cancelled',
            'bg-green-500/25' => fn(Order $order) => $order->status === 'completed',
            'bg-yellow-500/25' => fn(Order $order) => $order->status === 'on-hold',
            'bg-blue-500/25' => fn(Order $order) => $order->status === 'pending',
            'bg-purple-500/25' => fn(Order $order) => $order->status === 'processing',
        ]]">
        @scope('cell_name', $user)
        ({{ $user->lastname }}), {{ $user->name }}
        @endscope
        @scope('cell_total_price', $order)
        $ {{ number_format($order->total_price, 2,',', '.') }}
        @endscope

        @scope('actions', $order)
        <x-button icon="o-trash" wire:click="delete({{ $order['id'] }})" wire:confirm="Está seguro?" spinner class="btn-ghost btn-sm text-red-500" />
        @endscope
    </x-table>

    <!-- FILTER DRAWER -->
    <x-drawer wire:model="drawer" title="Filtros" right separator with-close-button class="lg:w-1/3">
        <x-input placeholder="Buscar..." wire:model.live.debounce="search" icon="o-magnifying-glass" @keydown.enter="$wire.drawer = false" />

        <x-slot:actions>
            <x-button label="Reset" icon="o-x-mark" wire:click="clear" spinner />
            <x-button label="Done" icon="o-check" class="btn-primary" @click="$wire.drawer = false" />
        </x-slot:actions>
    </x-drawer>
</div>
