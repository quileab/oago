<?php

use App\Livewire\Traits\ManagesModelIndex;
use App\Models\AltUser;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;

new class extends Component
{
    use ManagesModelIndex;
    use Toast;
    use WithPagination;

    protected string $modelClass = AltUser::class;

    public array $sortBy = ['column' => 'created_at', 'direction' => 'desc'];

    public function headers(): array
    {
        return [
            ['key' => 'id', 'label' => '#', 'class' => 'w-1'],
            ['key' => 'created_at', 'label' => 'Creado', 'class' => 'w-20'],
            ['key' => 'fullName', 'label' => 'Nombre'],
            ['key' => 'phone', 'label' => 'Tel.'],
            ['key' => 'email', 'label' => 'E-mail', 'sortable' => false],
            ['key' => 'role', 'label' => 'Role', 'class' => 'w-20'],
        ];
    }

    public function with(): array
    {
        return [
            'altUsers' => $this->getPaginatedRecords($this->sortBy),
            'headers' => $this->headers(),
        ];
    }
}; ?>

<div>
    <!-- HEADER -->
    <x-header title="Usuarios Alternativos" separator progress-indicator>
        <x-slot:middle class="!justify-end">
            <x-input placeholder="Buscar..." wire:model.live.debounce="search" clearable icon="o-magnifying-glass" />
        </x-slot:middle>
        <x-slot:actions>
            <x-button label="Agregar" link="/alt-users/create" responsive icon="o-user-plus" class="btn-primary" />
        </x-slot:actions>
    </x-header>

    <!-- TABLE  -->
    <x-table :headers="$headers" :rows="$altUsers" :sort-by="$sortBy" striped with-pagination link="/alt/{id}">
        @scope('cell_created_at', $altUser)
        {{ $altUser->created_at->format('d/m/Y') }}
        @endscope
    </x-table>

</div>