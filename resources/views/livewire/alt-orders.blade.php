<?php

use App\Models\AltOrder;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Pagination\LengthAwarePaginator;

new class extends Component {
    use Toast;
    use Livewire\WithPagination;

    public string $search = '';
    public bool $drawer = false;
    public array $sortBy = ['column' => 'id', 'direction' => 'desc'];

    public ?string $dateFrom = null;
    public ?string $dateTo = null;
    public ?string $statusFilter = null;

    public array $orderStates = [
        'pending' => 'Pendiente',
        'on-hold' => 'En espera',
        'processing' => 'Procesando',
        'completed' => 'Completado',
        'cancelled' => 'Cancelado',
    ];

    public function clear(): void
    {
        $this->reset('search', 'dateFrom', 'dateTo', 'statusFilter');
        $this->success('Filtros limpios.', position: 'toast-bottom');
    }

    public function filter(string $status): void
    {
        $this->statusFilter = $status;
        $this->resetPage();
    }

    public function headers(): array
    {
        return [
            ['key' => 'id', 'label' => '#', 'class' => 'w-1'],
            ['key' => 'user.fullName', 'label' => 'Cliente [ALT]', 'class' => 'w-56'],
            ['key' => 'created_at', 'label' => 'Fecha', 'class' => 'w-20'],
            ['key' => 'total_price', 'label' => 'TOTAL', 'class' => 'w-20 text-right'],
            ['key' => 'status', 'label' => 'Estado', 'class' => 'w-20'],
        ];
    }

    public function orders(): LengthAwarePaginator
    {
        $query = AltOrder::with('user');

        $user = current_user();
        $isAltUser = Auth::guard('alt')->check();
        $isAdmin = $user->role->value == 'admin';
        $isSales = $user->role->value == 'sales';

        if ($isAltUser) {
            $query->where('alt_user_id', $user->id);
        } elseif (!$isAdmin && !$isSales) {
            // Un cliente normal no debería ver pedidos alternativos a menos que se defina
            $query->whereRaw('1 = 0');
        }

        // Si es vendedor, por ahora ve todos los pedidos alternativos (ajustar luego)
        // if ($isSales) { ... }

        if ($this->search) {
            $query->where(function ($q) {
                $q->whereHas('user', function ($userQuery) {
                    $userQuery->where(DB::raw('LOWER(CONCAT(name, " ", lastname))'), 'like', '%' . strtolower($this->search) . '%');
                })->orWhere('id', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->dateFrom) {
            $query->whereDate('created_at', '>=', $this->dateFrom);
        }
        if ($this->dateTo) {
            $query->whereDate('created_at', '<=', $this->dateTo);
        }

        if ($this->statusFilter && $this->statusFilter !== 'all') {
            $query->where('status', $this->statusFilter);
        }

        $query->orderBy('created_at', 'desc');

        return $query->paginate(20);
    }

    public function updateStatus($orderId, $status)
    {
        $order = AltOrder::findOrFail($orderId);
        $order->update(['status' => $status]);
        $this->success("Pedido #{$orderId} actualizado a " . AltOrder::orderStates($status));
    }

    public function with(): array
    {
        return [
            'orders' => $this->orders(),
            'headers' => $this->headers(),
            'orderStates' => [
                'pending' => 'Pendiente',
                'on-hold' => 'En espera',
                'processing' => 'Procesando',
                'completed' => 'Completado',
                'cancelled' => 'Cancelado',
            ]
        ];
    }
}; ?>

<div>
    <x-header title="Pedidos Alternativos" subtitle="Gestión de compras de usuarios externos" separator progress-indicator>
        <x-slot:middle class="!justify-end">
            <x-input placeholder="buscar por cliente o ID..." wire:model.live.debounce="search" clearable icon="o-magnifying-glass"
                class="btn-success" />
        </x-slot:middle>
        <x-slot:actions>
            <x-button label="Filtros" @click="$wire.drawer = true" responsive icon="o-funnel" />
        </x-slot:actions>
    </x-header>

    <x-table :headers="$headers" :rows="$orders" link="/alt-order/{id}/edit" with-pagination
        :cell-decoration="
        ['status' =>  [
            'bg-red-500/25' => fn(AltOrder $order) => $order->status === 'cancelled',
            'bg-green-500/25' => fn(AltOrder $order) => $order->status === 'completed',
            'bg-yellow-500/25' => fn(AltOrder $order) => $order->status === 'on-hold',
            'bg-blue-500/25' => fn(AltOrder $order) => $order->status === 'pending',
            'bg-purple-500/25' => fn(AltOrder $order) => $order->status === 'processing',
        ]]">
        
        @scope('cell_user.fullName', $order)
            @if($order->user)
                {{ $order->user->lastname }}, {{ $order->user->name }}
            @else
                <span class="text-error italic">Usuario no encontrado</span>
            @endif
        @endscope

        @scope('cell_created_at', $order)
            {{ $order->created_at->format('d-m-Y H:i') }}
        @endscope

        @scope('cell_status', $order)
            {{ AltOrder::orderStates($order->status) }}
        @endscope

        @scope('cell_total_price', $order)
            <span class="font-black">$ {{ number_format($order->total_price, 2, ',', '.') }}</span>
        @endscope

        @scope('actions', $order)
            <div class="flex gap-2">
                <x-button icon="o-printer" class="btn-ghost btn-sm" title="Imprimir" link="/alt-order/{{ $order->id }}/print" external target="_blank" />
            </div>
        @endscope

    </x-table>

    <!-- FILTER DRAWER -->
    <x-drawer wire:model="drawer" title="Filtros" right separator with-close-button class="lg:w-1/3">
        <x-input placeholder="Buscar..." wire:model.live.debounce="search" icon="o-magnifying-glass"
            @keydown.enter="$wire.drawer = false" />
        
        <div class="space-y-4 mt-6">
            <div class="grid grid-cols-2 gap-2">
                <x-input label="Fecha desde" placeholder="Desde" wire:model.live.debounce="dateFrom" type="date" />
                <x-input label="Fecha hasta" placeholder="Hasta" wire:model.live.debounce="dateTo" type="date" />
            </div>

            <p class="font-bold text-sm">Estado:</p>
            <div class="flex flex-wrap gap-2">
                <x-button label="TODOS" wire:click="filter('all')" class="btn-xs {{ $statusFilter == 'all' ? 'btn-primary' : 'btn-outline' }}" />
                @foreach($orderStates as $key => $label)
                    <x-button :label="$label" wire:click="filter('{{ $key }}')" 
                        class="btn-xs {{ $statusFilter == $key ? 'btn-primary' : 'btn-outline' }}" />
                @endforeach
            </div>
        </div>

        <x-slot:actions>
            <x-button label="Limpiar" icon="o-x-mark" class="btn-ghost" wire:click="clear" spinner />
            <x-button label="Hecho" icon="o-check" class="btn-primary" @click="$wire.drawer = false" />
        </x-slot:actions>
    </x-drawer>
</div>