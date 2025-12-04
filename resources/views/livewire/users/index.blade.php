<?php
use App\Models\User;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Illuminate\Pagination\LengthAwarePaginator;
use Mary\Traits\Toast;
use App\Livewire\Traits\ManagesModelIndex; // Import the new trait
use Illuminate\Support\Facades\DB;

new class extends Component {
    use Toast;
    use WithPagination;
    use ManagesModelIndex; // Use the new trait

    // Properties from Trait: public string $search
    // Methods from Trait: public function delete($id)

    protected string $modelClass = User::class; // Configure the model for the trait
    
    public array $sortBy = ['column' => 'id', 'direction' => 'asc'];

    // --- delete() is now handled by ManagesModelIndex trait ---

    // Table headers
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

    // The query is specific to this component, so we keep it here.
    public function users(): LengthAwarePaginator
    {
        return User::query()
        ->when($this->search,
            fn($q) => $q->where(DB::raw('concat(name, " ", lastname, " ", email)'), 'like', "%$this->search%")
        )
        ->orderBy(...array_values($this->sortBy))->paginate(20);
    }

    public function with(): array
    {
        return [
            'users' => $this->users(),
            'headers' => $this->headers()
        ];
    }

    public function updated($property): void
    {
        if (! is_array($property) && $property != "") {
            $this->resetPage();
        }
    }
}; ?>

<div>
    <!-- HEADER -->
    <x-header title="Usuarios" separator progress-indicator>
        <x-slot:middle class="!justify-end">
            <x-input placeholder="Buscar..." wire:model.live.debounce="search" clearable icon="o-magnifying-glass" />
        </x-slot:middle>
        <x-slot:actions>
            <x-button icon="o-plus" class="btn-primary" link="/user" />
            <x-button icon="o-users" class="btn-secondary" link="/users/bulk-role-update" label="Actualización Masiva de Roles" />
        </x-slot:actions>
    </x-header>

    <!-- TABLE  -->
    <x-table :headers="$headers" :rows="$users" :sort-by="$sortBy" striped with-pagination link="/user/{id}" >
    </x-table>
</div>
