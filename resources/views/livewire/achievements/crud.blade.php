<?php

use Livewire\Volt\Component;
use Mary\Traits\Toast;
use App\Models\Achievement;

new class extends Component {
    use Toast;

    public array $formData;

    public function mount($achievement = null)
    {
        if ($achievement) {
            $achievement = Achievement::findOrFail($achievement);
            $this->formData = $achievement->toArray();
            $this->formData['data'] = json_encode($achievement->data);
        } else {
            $achievement = new Achievement();
            $achievement->data = [];
            $this->formData = $achievement->toArray();
            $this->formData['data'] = json_encode($this->formData['data']);
        }
    }

    protected function rules()
    {
        return [
            'formData.type' => 'required|string',
            'formData.name' => 'required|string',
            'formData.description' => 'nullable|string',
            'formData.data' => 'nullable|json',
        ];
    }

    public function save()
    {
        $this->validate();

        // Decode JSON string to array before saving
        if (isset($this->formData['data']) && is_string($this->formData['data'])) {
            $this->formData['data'] = json_decode($this->formData['data'], true);
        }

        Achievement::updateOrCreate(
            ['id' => $this->formData['id'] ?? null],
            $this->formData
        );

        $this->success('Logro guardado.', position: 'toast-bottom');
        return redirect('/achievements');
    }

    public function delete()
    {
        Achievement::findOrFail($this->formData['id'])->delete();
        $this->success('Logro eliminado.', position: 'toast-bottom');
        return redirect('/achievements');
    }

    public function achievementTypes()
    {
        return [
            ['name' => 'points'],
            ['name' => 'medal'],
            ['name' => 'badge'],
        ];
    }
}; ?>

<div>
    <x-card title="Logro" shadow separator>
        <x-form wire:submit="save">
            <x-select label="Tipo" icon="o-tag" :options="$this->achievementTypes()" option-value="name"
                wire:model="formData.type" />
            <x-input label="Nombre" wire:model="formData.name" icon="o-pencil" />
            <x-textarea label="Descripción" wire:model="formData.description" />
            <x-textarea label="Datos (JSON)" wire:model="formData.data" rows="5" />

            <x-slot:actions>
                <x-button label="Guardar" icon="o-check" class="btn-primary" type="submit" spinner="save" />
                @if($formData['id'] ?? null)
                    <x-dropdown>
                        <x-slot:trigger>
                            <x-button label="Eliminar" icon="o-trash" class="btn-error" />
                        </x-slot:trigger>
                        <x-menu-item label="¿Seguro?" icon="o-question-mark-circle" />
                        <x-menu-separator />
                        <x-menu-item label="¡Sí, bórralo!" icon="o-trash" wire:click="delete" spinner="delete" />
                    </x-dropdown>
                @endif
            </x-slot:actions>
        </x-form>
    </x-card>
</div>