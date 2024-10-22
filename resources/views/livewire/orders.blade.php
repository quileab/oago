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

    public array $status_class = [
        'pending'=>'primary',
        'completed'=>'secondary',
        'cancelled'=>'danger',
        'processing'=>'info',
        'on-hold'=>'warning',
    ];

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
            ['key' => 'user.name', 'label' => 'Name', 'class' => 'w-56'],
            ['key' => 'total_price', 'label' => 'Total', 'class' => 'w-20'],
            ['key' => 'order_date', 'label' => 'Fecha', 'class' => 'w-20'],
            ['key' => 'status', 'label' => 'Estado', 'class' => 'w-20'],
        ];
    }

    public function orders(): Collection
    {
        return Order::with('user')->get()
            ->sortBy([[...array_values($this->sortBy)]])
            ->when($this->search, function (Collection $collection) {
                return $collection->filter(fn(array $item) => str($item['name'])->contains($this->search, true));
            });
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
    <x-card>
        <x-table :headers="$headers" :rows="$orders" :sort-by="$sortBy">
            @scope('cell_name', $user)
            ({{  $user->lastname }}), {{ $user->name }}
            @endscope
            @scope('cell_total_price', $order)
            $ {{ number_format($order->total_price, 2,',', '.') }}
            @endscope
            @scope('cell_status', $order, $status_class)
            <x-badge :value="$order->status" class="badge-{{ $status_class[$order->status] }}" />        
            @endscope
            @scope('actions', $order)
            <x-button icon="o-trash" wire:click="delete({{ $order['id'] }})" wire:confirm="Está seguro?" spinner class="btn-ghost btn-sm text-red-500" />
            @endscope
        </x-table>
    </x-card>

    <!-- FILTER DRAWER -->
    <x-drawer wire:model="drawer" title="Filtros" right separator with-close-button class="lg:w-1/3">
        <x-input placeholder="Buscar..." wire:model.live.debounce="search" icon="o-magnifying-glass" @keydown.enter="$wire.drawer = false" />

        <x-slot:actions>
            <x-button label="Reset" icon="o-x-mark" wire:click="clear" spinner />
            <x-button label="Done" icon="o-check" class="btn-primary" @click="$wire.drawer = false" />
        </x-slot:actions>
    </x-drawer>
</div>
