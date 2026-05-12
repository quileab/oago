<?php

use App\Models\ListName;
use Livewire\Volt\Component;
use Mary\Traits\Toast;

new class extends Component
{
    use Toast;

    public bool $listModal = false;

    public string $listName = '';

    public ?int $editingListId = null;

    public function lists()
    {
        return ListName::all();
    }

    public function create()
    {
        $this->reset(['listName', 'editingListId']);
        $this->listModal = true;
    }

    public function edit(int $id)
    {
        $list = ListName::findOrFail($id);
        $this->editingListId = $id;
        $this->listName = $list->name;
        $this->listModal = true;
    }

    public function save()
    {
        $this->validate([
            'listName' => 'required|string|max:50',
        ]);

        ListName::updateOrCreate(
            ['id' => $this->editingListId],
            ['name' => $this->listName]
        );

        $this->listModal = false;
        $this->success('Lista de precios guardada correctamente.');
    }

    public function delete(int $id)
    {
        $list = ListName::findOrFail($id);

        if ($list->listPrices()->exists() || $list->users()->exists() || $list->altUsers()->exists()) {
            $this->error('No se puede eliminar la lista porque tiene precios o usuarios asociados.');

            return;
        }

        $list->delete();
        $this->success('Lista de precios eliminada.');
    }

    public function with(): array
    {
        return [
            'lists' => $this->lists(),
        ];
    }
}; ?>

<div>
    <x-header title="Administrador de Listas" subtitle="Gestione las listas de precios disponibles para productos y usuarios." separator progress-indicator>
        <x-slot:actions>
            <x-button label="Nueva Lista" icon="o-plus" class="btn-primary" wire:click="create" />
            <x-button label="Volver a Productos" icon="o-arrow-left" link="/products" class="btn-ghost" />
        </x-slot:actions>
    </x-header>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
        @forelse($lists as $list)
            <x-card class="bg-base-100 shadow-sm border border-base-200">
                <div class="flex items-start justify-between">
                    <div>
                        <div class="text-xs text-base-content/50 font-mono mb-1">ID: {{ $list->id }}</div>
                        <div class="text-lg font-bold">{{ $list->name }}</div>
                    </div>
                    <div class="flex gap-2">
                        <x-button icon="o-pencil" class="btn-sm btn-circle btn-ghost" wire:click="edit({{ $list->id }})" />
                        <x-button icon="o-trash" class="btn-sm btn-circle btn-ghost text-error" 
                                  wire:click="delete({{ $list->id }})" 
                                  wire:confirm="¿Está seguro de eliminar esta lista?" />
                    </div>
                </div>
            </x-card>
        @empty
            <div class="col-span-full text-center py-12">
                <x-icon name="o-inbox" class="w-12 h-12 mx-auto mb-3 text-base-content/30" />
                <div class="text-base-content/50">No hay listas configuradas</div>
            </div>
        @endforelse
    </div>

    <x-modal wire:model="listModal" title="{{ $editingListId ? 'Editar Lista' : 'Nueva Lista' }}" separator>
        <div class="grid gap-4">
            <x-input label="Nombre de la Lista" wire:model="listName" placeholder="Ej: Lista Minorista, Mayorista..." />
        </div>
        <x-slot:actions>
            <x-button label="Cancelar" @click="$wire.listModal = false" />
            <x-button label="Guardar" class="btn-primary" wire:click="save" spinner="save" />
        </x-slot:actions>
    </x-modal>
</div>
