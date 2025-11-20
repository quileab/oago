<?php
use App\Models\AltUser;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Illuminate\Pagination\LengthAwarePaginator;
use Mary\Traits\Toast;
use App\Livewire\Traits\ManagesModelIndex; // Import the new trait

new class extends Component {
    use Toast;
    use WithPagination;
    use ManagesModelIndex; // Use the new trait

    protected string $modelClass = AltUser::class; // Configure the model for the trait

    public array $sortBy = ['column' => 'created_at', 'direction' => 'desc'];

    // --- delete() is now handled by ManagesModelIndex trait ---

    // Table headers
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

    public function altUsers(): LengthAwarePaginator //Collection
    {
        return AltUser::query()
            ->when(
                $this->search,
                fn($q) => $q->where(DB::raw('concat(name, " ", lastname, " ", email)'), 'like', "%$this->search%")
            )
            ->orderBy(...array_values($this->sortBy))->paginate(20);
    }

    public function with(): array
    {
        return [
            'altUsers' => $this->altUsers(),
            'headers' => $this->headers()
        ];
    }

    // Reset pagination when any component property changes
    public function updated($property): void
    {
        if (!is_array($property) && $property != "") {
            $this->resetPage();
        }
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