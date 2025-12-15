<?php

use App\Models\Order;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;

new class extends Component {
    use Toast;
    use Livewire\WithPagination;

    public string $search = '';

    public bool $drawer = false;

    public array $sortBy = ['column' => 'id', 'direction' => 'asc'];

    public ?string $dateFrom = null;
    public ?string $dateTo = null;
    public ?string $statusFilter = null;

    // Clear filters
    public function clear(): void
    {
        $this->reset('search', 'dateFrom', 'dateTo', 'statusFilter'); // Reset specific filters
        $this->success('Filtros limpios.', position: 'toast-bottom');
    }

    public function filter(string $status): void
    {
        $this->statusFilter = $status;
        $this->resetPage(); // Reset pagination when filter changes
    }

    // Table headers
    public function headers(): array
    {
        return [
            ['key' => 'id', 'label' => '#', 'class' => 'w-1'],
            ['key' => 'user.fullName', 'label' => 'Name', 'class' => 'w-56'],
            ['key' => 'order_date', 'label' => 'Fecha', 'class' => 'w-20'],
            ['key' => 'total_price', 'label' => 'TOTAL', 'class' => 'w-20 text-right'], // Added total_price
            ['key' => 'status', 'label' => 'Estado', 'class' => 'w-20'],
        ];
    }

    public function orders(): LengthAwarePaginator
    {
        $query = Order::with('user');

        // Apply admin/customer filter
        $user = current_user();
        $isAdmin = $user->role->value == 'admin';
        if (!$isAdmin) {
            $query->where('user_id', $user->id);
        }

        // Apply search filter (moved to DB query)
        if ($this->search) {
            $query->where(function ($q) {
                $q->whereHas('user', function ($userQuery) {
                    $userQuery->where(DB::raw('LOWER(CONCAT(name, " ", lastname))'), 'like', '%' . strtolower($this->search) . '%');
                })->orWhere('id', 'like', '%' . $this->search . '%');
            });
        }

        // Apply date filters
        if ($this->dateFrom) {
            $query->whereDate('created_at', '>=', $this->dateFrom);
        }
        if ($this->dateTo) {
            $query->whereDate('created_at', '<=', $this->dateTo);
        }

        // Apply status filter
        if ($this->statusFilter && $this->statusFilter !== 'all') {
            $query->where('status', $this->statusFilter);
        }

        // Order by created_at descending as requested
        $query->orderBy('created_at', 'desc');

        return $query->paginate(20);
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
            <x-input placeholder="buscar..." wire:model.live.debounce="search" clearable icon="o-magnifying-glass"
                class="btn-success" />
        </x-slot:middle>
        <x-slot:actions>
            <x-button label="Filtros" @click="$wire.drawer = true" responsive icon="o-funnel" />
        </x-slot:actions>
    </x-header>

    <!-- TABLE  -->
    <x-table :headers="$headers" :rows="$orders" :sort-by="$sortBy" link="/order/{id}/edit" with-pagination
        :cell-decoration="
        ['status' =>  [
            'bg-red-500/25' => fn(Order $order) => $order->status === 'cancelled',
            'bg-green-500/25' => fn(Order $order) => $order->status === 'completed',
            'bg-yellow-500/25' => fn(Order $order) => $order->status === 'on-hold',
            'bg-blue-500/25' => fn(Order $order) => $order->status === 'pending',
            'bg-purple-500/25' => fn(Order $order) => $order->status === 'processing',
        ]]">
        @scope('cell_order_date', $order)
        {{ $order->created_at->format('d-m-Y H:i') }}
        @endscope
        @scope('cell_status', $order)
        {{ Order::orderStates($order->status) }}
        @endscope
        @scope('cell_name', $user)
        ({{ $user->lastname }}), {{ $user->name }}
        @endscope
        @scope('cell_total_price', $order)
        $ {{ number_format($order->total_price, 2, ',', '.') }}
        @endscope

    </x-table>

    <!-- FILTER DRAWER -->
    <x-drawer wire:model="drawer" title="Filtros" right separator with-close-button class="lg:w-1/3">
        <x-input placeholder="Buscar..." wire:model.live.debounce="search" icon="o-magnifying-glass"
            @keydown.enter="$wire.drawer = false" />
        {{-- fechas desde ->hasta --}}
        <div class="grid grid-cols-2 gap-2 mt-4">
            <x-input label="Fecha desde" placeholder="Desde" wire:model.live.debounce="dateFrom" type="date" />
            <x-input label="Fecha hasta" placeholder="Hasta" wire:model.live.debounce="dateTo" type="date" />
            {{-- botones de filtro de estado de pedido --}}

            <p>Filtrar por estado:</p>
            <x-button label="TODOS" wire:click="filter('all')" class="btn-outline" />
            <x-button label="Pendientes" wire:click="filter('pending')" class="btn-primary" />
            <x-button label="Completados" wire:click="filter('completed')" class="btn-success" />
            <x-button label="Cancelados" wire:click="filter('cancelled')" class="btn-error" />
            <x-button label="En Espera" wire:click="filter('on-hold')" class="btn-warning" />
        </div>
        <x-slot:actions>
            <x-button label="Limpiar" icon="o-x-mark" class="btn-warning" wire:click="clear" spinner />
            <x-button label="Hecho" icon="o-check" class="btn-success" @click="$wire.drawer = false" />
        </x-slot:actions>
    </x-drawer>
</div>