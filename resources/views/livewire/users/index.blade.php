<?php

use App\Livewire\Traits\ManagesModelIndex;
use App\Models\User;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;

new class extends Component
{
    use ManagesModelIndex;
    use Toast;
    use WithPagination;

    protected string $modelClass = User::class;

    public array $sortBy = ['column' => 'id', 'direction' => 'asc'];

    public function headers(): array
    {
        return [
            ['key' => 'id', 'label' => '#', 'class' => 'w-1'],
            ['key' => 'fullName', 'label' => 'Nombre'],
            ['key' => 'phone', 'label' => 'Tel.'],
            ['key' => 'email', 'label' => 'E-mail', 'sortable' => false],
            ['key' => 'role', 'label' => 'Role', 'class' => 'w-20'],
        ];
    }

    public function with(): array
    {
        return [
            'users' => $this->getPaginatedRecords($this->sortBy),
            'headers' => $this->headers(),
        ];
    }
}; ?>

<div>
    <!-- HEADER -->
    <x-header title="Usuarios" separator progress-indicator>
        <x-slot:middle class="!justify-end">
            <x-input placeholder="Buscar..." wire:model.live.debounce="search" clearable icon="o-magnifying-glass" />
        </x-slot:middle>
        <x-slot:actions>
        {{-- // placeholder --}}
        </x-slot:actions>
    </x-header>

    <!-- TABLE  -->
    <x-table :headers="$headers" :rows="$users" :sort-by="$sortBy" striped with-pagination link="/user/{id}" >
    </x-table>
</div>
