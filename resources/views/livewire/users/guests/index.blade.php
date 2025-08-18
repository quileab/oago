<?php
use App\Models\GuestUser;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Illuminate\Pagination\LengthAwarePaginator;
use Mary\Traits\Toast;
use App\Livewire\Traits\ManagesModelIndex; // Import the new trait

new class extends Component {
    use Toast;
    use WithPagination;
    use ManagesModelIndex; // Use the new trait

    protected string $modelClass = GuestUser::class; // Configure the model for the trait
    

    // Properties from Trait: public string $search
    // Methods from Trait: public function delete($id)

    public bool $drawer = false;

    public array $sortBy = ['column' => 'id', 'direction' => 'asc'];

    // Clear filters
    public function clear(): void
    {
        $this->reset();
        $this->resetPage();
        $this->success('Filters cleared.', position: 'toast-bottom');
    }

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

    public function guestUsers(): LengthAwarePaginator //Collection
        {
            return GuestUser::query()
                ->when(
                    $this->search,
                    fn($q) => $q->where(DB::raw('concat(name, " ", lastname, " ", email)'), 'like', "%$this->search%")
                )
                ->orderBy(...array_values($this->sortBy))->paginate(20);
        }

    public function with(): array
        {
            return [
                'guestUsers' => $this->guestUsers(),
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
    <x-header title="Usuarios Invitados" separator progress-indicator>
        <x-slot:middle class="!justify-end">
            <x-input placeholder="Search..." wire:model.live.debounce="search" clearable icon="o-magnifying-glass" />
        </x-slot:middle>
        <x-slot:actions>
            <x-button label="Filters" @click="$wire.drawer = true" responsive icon="o-funnel" />
        </x-slot:actions>
    </x-header>

    <!-- TABLE  -->
    <x-table :headers="$headers" :rows="$guestUsers" :sort-by="$sortBy" striped with-pagination link="/guest/{id}">
        @scope('actions', $guestUser)
            <x-button icon="o-trash" wire:click="delete({{ $guestUser->id }})" spinner class="btn-ghost btn-sm text-red-500" />
        @endscope
    </x-table>

    <!-- FILTER DRAWER -->
    <x-drawer wire:model="drawer" title="Filters" right separator with-close-button class="lg:w-1/3">
        <x-input placeholder="Search..." wire:model.live.debounce="search" icon="o-magnifying-glass"
            @keydown.enter="$wire.drawer = false" />

        <x-slot:actions>
            <x-button label="Reset" icon="o-x-mark" wire:click="clear" spinner />
            <x-button label="Done" icon="o-check" class="btn-primary" @click="$wire.drawer = false" />
        </x-slot:actions>
    </x-drawer>
</div>