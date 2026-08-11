<?php

use App\Models\Tag;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;

new class extends Component {
    use Toast;
    use WithPagination;

    public string $search = '';
    public array $sortBy = ['column' => 'sort_order', 'direction' => 'asc'];

    public function headers(): array
    {
        return [
            ['key' => 'id', 'label' => '#', 'class' => 'w-1'],
            ['key' => 'name', 'label' => 'Nombre'],
            ['key' => 'slug', 'label' => 'Slug'],
            ['key' => 'sort_order', 'label' => 'Orden', 'class' => 'w-20'],
            ['key' => 'products_count', 'label' => '# Productos', 'class' => 'w-24'],
        ];
    }

    public function tags()
    {
        return Tag::query()
            ->withCount('products')
            ->when($this->search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            })
            ->orderBy(...array_values($this->sortBy))
            ->paginate(15);
    }

    public function with(): array
    {
        return [
            'tags' => $this->tags(),
            'headers' => $this->headers(),
        ];
    }

    public function updated($property): void
    {
        if (! is_array($property) && $property !== '') {
            $this->resetPage();
        }
    }
}; ?>

<div>
    <x-header title="Tags" separator progress-indicator>
        <x-slot:middle class="!justify-end">
            <x-input placeholder="Buscar..." wire:model.live.debounce="search" clearable icon="o-magnifying-glass" />
        </x-slot:middle>
        <x-slot:actions>
            <x-button label="Nuevo Tag" icon="o-plus" link="/tags/create" responsive class="btn-primary" />
        </x-slot:actions>
    </x-header>

    <x-table :headers="$headers" :rows="$tags" :sort-by="$sortBy" striped with-pagination link="/tags/{id}">
        @scope('cell_products_count', $tag)
            {{ $tag->products_count }}
        @endscope
    </x-table>
</div>
