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

    public bool $createUnitPair = true;

    public function lists()
    {
        $all = ListName::all();
        $grouped = [];

        foreach ($all as $list) {
            $name = trim($list->name);
            $isUnit = str_ends_with($name, ' U');
            $baseName = $isUnit ? preg_replace('/ U$/', '', $name) : $name;

            if (!isset($grouped[$baseName])) {
                $grouped[$baseName] = [
                    'base' => null,
                    'unit' => null,
                    'baseName' => $baseName
                ];
            }

            if ($isUnit) {
                $grouped[$baseName]['unit'] = $list;
            } else {
                $grouped[$baseName]['base'] = $list;
            }
        }

        return $grouped;
    }

    public function create()
    {
        $this->reset(['listName', 'editingListId', 'createUnitPair']);
        $this->listModal = true;
    }

    public function edit(int $id)
    {
        $list = ListName::findOrFail($id);
        $this->editingListId = $id;
        $this->listName = $list->name;
        $this->createUnitPair = false; // Ocultar para edición simple por ahora
        $this->listModal = true;
    }

    public function save()
    {
        $this->validate([
            'listName' => 'required|string|max:48',
        ]);

        $name = trim($this->listName);

        DB::transaction(function () use ($name) {
            $baseList = ListName::updateOrCreate(
                ['id' => $this->editingListId],
                ['name' => $name]
            );

            if ($this->createUnitPair && !$this->editingListId) {
                ListName::updateOrCreate(
                    ['name' => $name . ' U'],
                    ['name' => $name . ' U']
                );
            }
        });

        $this->listModal = false;
        $this->success('Lista(s) guardada(s) correctamente.');
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
            'groupedLists' => $this->lists(),
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

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($groupedLists as $baseName => $pair)
            <x-card class="bg-base-100 shadow-md border border-base-300 overflow-hidden" no-shadow>
                <div class="flex items-center justify-between bg-base-200/50 p-4 border-b border-base-300">
                    <h3 class="font-black uppercase tracking-tight text-primary">{{ $baseName }}</h3>
                </div>
                
                <div class="p-4 space-y-3">
                    {{-- Par por Bulto --}}
                    <div class="flex items-center justify-between p-3 bg-base-200/30 rounded-xl border border-base-content/5">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-blue-500/10 flex items-center justify-center text-blue-600 font-bold text-xs">
                                B
                            </div>
                            <div>
                                <div class="text-xs opacity-50 font-mono">ID: {{ $pair['base']?->id ?? '---' }}</div>
                                <div class="text-sm font-bold {{ !$pair['base'] ? 'italic opacity-30' : '' }}">
                                    {{ $pair['base'] ? 'Por Bulto' : 'No configurada' }}
                                </div>
                            </div>
                        </div>
                        <div class="flex gap-1">
                            @if($pair['base'])
                                <x-button icon="o-pencil" class="btn-sm btn-ghost btn-circle" wire:click="edit({{ $pair['base']->id }})" />
                                <x-button icon="o-trash" class="btn-sm btn-ghost btn-circle text-error" 
                                          wire:click="delete({{ $pair['base']->id }})" 
                                          wire:confirm="¿Está seguro de eliminar esta lista?" />
                            @endif
                        </div>
                    </div>

                    {{-- Par por Unidad --}}
                    <div class="flex items-center justify-between p-3 bg-base-200/30 rounded-xl border border-base-content/5">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-green-500/10 flex items-center justify-center text-green-600 font-bold text-xs">
                                U
                            </div>
                            <div>
                                <div class="text-xs opacity-50 font-mono">ID: {{ $pair['unit']?->id ?? '---' }}</div>
                                <div class="text-sm font-bold {{ !$pair['unit'] ? 'italic opacity-30' : '' }}">
                                    {{ $pair['unit'] ? 'Por Unidad (U)' : 'No configurada' }}
                                </div>
                            </div>
                        </div>
                        <div class="flex gap-1">
                            @if($pair['unit'])
                                <x-button icon="o-pencil" class="btn-sm btn-ghost btn-circle" wire:click="edit({{ $pair['unit']->id }})" />
                                <x-button icon="o-trash" class="btn-sm btn-ghost btn-circle text-error" 
                                          wire:click="delete({{ $pair['unit']->id }})" 
                                          wire:confirm="¿Está seguro de eliminar esta lista?" />
                            @endif
                        </div>
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
            <x-input label="Nombre de la Lista" wire:model="listName" placeholder="Ej: Lista Minorista, Mayorista..." hint="El par 'U' se creará automáticamente si está marcado." />
            
            @if(!$editingListId)
                <x-checkbox label="Crear automáticamente el par por Unidad (U)" wire:model="createUnitPair" class="checkbox-primary" />
            @endif
        </div>
        <x-slot:actions>
            <x-button label="Cancelar" @click="$wire.listModal = false" />
            <x-button label="Guardar" class="btn-primary" wire:click="save" spinner="save" />
        </x-slot:actions>
    </x-modal>
</div>
