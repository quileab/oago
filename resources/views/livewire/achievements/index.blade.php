<?php

use App\Models\Achievement;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;

new class extends Component {
    use Toast;
    use WithPagination;

    public string $search = '';

    public array $sortBy = ['column' => 'id', 'direction' => 'desc'];

    // Table headers
    public function headers(): array
    {
        return [
            ['key' => 'id', 'label' => '#', 'class' => 'w-1'],
            ['key' => 'type', 'label' => 'Tipo'],
            ['key' => 'name', 'label' => 'Nombre'],
            ['key' => 'description', 'label' => 'Descripción'],
            ['key' => 'data', 'label' => 'Datos'],
        ];
    }

    public function achievements()
    {
        return Achievement::query()
            ->when($this->search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
            })
            ->orderBy(...array_values($this->sortBy))
            ->paginate(10);
    }

    public function with(): array
    {
        return [
            'achievements' => $this->achievements(),
            'headers' => $this->headers(),
        ];
    }
}; ?>

<div>
    <x-header title="Logros" separator progress-indicator>
        <x-slot:middle class="!justify-end">
            <x-input placeholder="Buscar..." wire:model.live.debounce="search" clearable icon="o-magnifying-glass" />
        </x-slot:middle>
        <x-slot:actions>
            <x-button label="Crear Logro" icon="o-plus" link="/achievement/create" responsive class="btn-primary" />
        </x-slot:actions>
    </x-header>

    <x-table :headers="$headers" :rows="$achievements" :sort-by="$sortBy" link="/achievement/{id}/edit" with-pagination>
        @scope('cell_data', $achievement)
            {{ json_encode($achievement->data) }}
        @endscope
    </x-table>
</div>