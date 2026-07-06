<?php

use App\Helpers\SettingsHelper;
use App\Models\ListName;
use Illuminate\Support\Facades\DB;
use Livewire\Volt\Component;
use Mary\Traits\Toast;

new class extends Component
{
    use Toast;

    public bool $listModal = false;

    public string $listName = '';

    public $listId = null;

    public ?int $editingListId = null;

    public ?int $promoListId = null;

    public function mount()
    {
        $this->promoListId = (int) SettingsHelper::settings('promo_list_id');
    }

    public function setAsPromo(int $id): void
    {
        SettingsHelper::update_setting('promo_list_id', $id);
        $this->promoListId = $id;
        $this->success('Lista configurada como promocional.');
    }

    public function clearPromo(): void
    {
        SettingsHelper::update_setting('promo_list_id', null);
        $this->promoListId = null;
        $this->success('Lista de ofertas desactivada.');
    }

    public function lists()
    {
        return ListName::orderBy('id')->get();
    }

    public function create()
    {
        $this->reset(['listName', 'listId', 'editingListId']);
        $this->listModal = true;
    }

    public function edit(int $id)
    {
        $list = ListName::findOrFail($id);
        $this->editingListId = $id;
        $this->listId = $list->id;
        $this->listName = $list->name;
        $this->listModal = true;
    }

    public function save()
    {
        $this->validate([
            'listName' => 'required|string|max:48',
            'listId' => 'required|integer|min:1',
        ]);

        $name = trim($this->listName);
        $id = (int) $this->listId;

        if ($this->editingListId !== $id && ListName::where('id', $id)->exists()) {
            $this->error('El ID de lista ya está en uso.');

            return;
        }

        try {
            DB::transaction(function () use ($name, $id) {
                if ($this->editingListId) {
                    $list = ListName::findOrFail($this->editingListId);

                    if ($this->editingListId !== $id) {
                        // Cambiar el ID (asume que ON UPDATE CASCADE está configurado o no hay relaciones estrictas en cascada manual que fallen)
                        $list->id = $id;
                    }

                    $list->name = $name;
                    $list->save();
                } else {
                    $list = new ListName;
                    $list->id = $id;
                    $list->name = $name;
                    $list->save();
                }
            });

            $this->listModal = false;
            $this->success('Lista guardada correctamente.');
        } catch (Exception $e) {
            $this->error('Error al guardar: '.$e->getMessage());
        }
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
            'listsData' => $this->lists(),
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
        @forelse($listsData as $list)
            <x-card class="bg-base-100 shadow-md border border-base-300 overflow-hidden" no-shadow>
                <div class="flex items-center justify-between bg-base-200/50 p-4 border-b border-base-300">
                    <h3 class="font-black uppercase tracking-tight text-primary">{{ $list->name }}</h3>
                    <div class="text-xs opacity-50 font-mono">ID: {{ $list->id }}</div>
                </div>
                
                <div class="p-4 space-y-3">
                    <div class="flex gap-2 justify-end items-center">
                        @if($promoListId === $list->id)
                            <span class="badge badge-success badge-sm font-bold text-white mr-auto">OFERTAS</span>
                            <x-button icon="o-x-mark" class="btn-sm btn-ghost btn-circle text-error"
                                      wire:click="clearPromo"
                                      wire:confirm="¿Desactivar la lista de ofertas?"
                                      tooltip="Quitar como Ofertas" spinner />
                        @else
                            <x-button icon="o-star" class="btn-sm btn-ghost btn-circle text-amber-500"
                                      wire:click="setAsPromo({{ $list->id }})"
                                      wire:confirm="¿Usar esta lista como la lista de ofertas?"
                                      tooltip="Usar para Ofertas" spinner />
                        @endif
                        <x-button icon="o-pencil" class="btn-sm btn-ghost btn-circle" wire:click="edit({{ $list->id }})" />
                        <x-button icon="o-trash" class="btn-sm btn-ghost btn-circle text-error" 
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
            <x-input label="ID Legacy" wire:model="listId" type="number" placeholder="Ej: 2" hint="El ID de la lista en el sistema legado." />
            <x-input label="Nombre de la Lista" wire:model="listName" placeholder="Ej: Lista A" />
        </div>
        <x-slot:actions>
            <x-button label="Cancelar" @click="$wire.listModal = false" />
            <x-button label="Guardar" class="btn-primary" wire:click="save" spinner="save" />
        </x-slot:actions>
    </x-modal>
</div>

