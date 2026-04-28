<?php

use Livewire\Volt\Component;
use App\Models\Setting;
use Mary\Traits\Toast;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Cache;

new class extends Component {
    use Toast;

    public $settings;
    public bool $drawer = false;
    public array $formData = [];
    public bool $isEditing = false;

    public function mount(): void
    {
        $this->refreshSettings();
    }

    public function refreshSettings(): void
    {
        $this->settings = Setting::all();
    }

    public function create(): void
    {
        $this->formData = [
            'key' => '',
            'value' => '',
            'type' => 'string',
            'text' => '',
            'description' => ''
        ];
        $this->isEditing = false;
        $this->drawer = true;
    }

    public function edit($id): void
    {
        $setting = Setting::findOrFail($id);
        $this->formData = $setting->toArray();

        // Convert simple arrays to comma-separated string, keep complex JSON as raw string
        if ($this->formData['type'] === 'json') {
            $value = is_string($this->formData['value']) 
                ? json_decode($this->formData['value'], true) 
                : $this->formData['value'];

            if (is_array($value)) {
                // If it's a simple flat array of strings/numbers, implode it
                if (collect($value)->every(fn($item) => is_string($item) || is_numeric($item))) {
                    $this->formData['value'] = implode(',', $value);
                } else {
                    // It's complex JSON, show as formatted string
                    $this->formData['value'] = json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                }
            }
        }

        $this->isEditing = true;
        $this->drawer = true;
    }

    public function save(): void
    {
        $rules = [
            'formData.key' => ['required', 'string', 'max:255', Rule::unique('settings', 'key')->ignore($this->formData['id'] ?? null)],
            'formData.type' => 'required|in:string,number,boolean,json',
            'formData.text' => 'required|string|max:255',
            'formData.description' => 'nullable|string|max:255',
            'formData.value' => 'nullable',
        ];

        $this->validate($rules);

        $data = $this->formData;

        // Handle JSON type conversion
        if ($data['type'] === 'json' && !empty($data['value'])) {
            $trimmedValue = trim($data['value']);
            
            // Check if it's a JSON object/array string
            if (str_starts_with($trimmedValue, '{') || str_starts_with($trimmedValue, '[')) {
                $decoded = json_decode($trimmedValue, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $data['value'] = json_encode($decoded);
                } else {
                    $this->addError('formData.value', 'El formato JSON no es válido: ' . json_last_error_msg());
                    return;
                }
            } else {
                // Assume it's a comma-separated list
                $data['value'] = json_encode(array_map('trim', explode(',', $data['value'])));
            }
        }

        Setting::updateOrCreate(
            ['id' => $this->formData['id'] ?? null],
            $data
        );

        // Clear cache for this setting
        Cache::forget('settings.' . $data['key']);

        $this->drawer = false;
        $this->refreshSettings();
        $this->success($this->isEditing ? 'Configuración actualizada.' : 'Configuración creada.');
    }

    public function delete($id = null): void
    {
        $id = $id ?? $this->formData['id'];
        $setting = Setting::findOrFail($id);
        // Clear cache before deleting
        Cache::forget('settings.' . $setting->key);

        $setting->delete();
        $this->drawer = false;
        $this->refreshSettings();
        $this->success('Configuración eliminada.');
    }

    public function types(): array
    {
        return [
            ['id' => 'string', 'name' => 'Texto (String)', 'emoji' => '📝'],
            ['id' => 'number', 'name' => 'Número', 'emoji' => '🔢'],
            ['id' => 'boolean', 'name' => 'Booleano (Sí/No)', 'emoji' => '✅'],
            ['id' => 'json', 'name' => 'Lista (JSON)', 'emoji' => '📜'],
        ];
    }

    public function getTypeEmoji($type): string
    {
        $types = collect($this->types());
        $found = $types->firstWhere('id', $type);
        return $found['emoji'] ?? '';
    }
}; ?>

<div>
    <x-header title="Administrar Configuraciones" separator progress-indicator>
        <x-slot:actions>
            <x-button label="Nueva Configuración" @click="$wire.create()" icon="o-plus" class="btn-primary" />
        </x-slot:actions>
    </x-header>

    <x-card>
        <x-table :headers="[
        ['key' => 'key', 'label' => 'Etiqueta / Clave (Key)'],
        ['key' => 'type', 'label' => '🔣'],
        ['key' => 'description', 'label' => 'Descripción'],
        ['key' => 'value', 'label' => 'Valor Actual (Preview)'],
    ]"        :rows="$settings" striped>
            @scope('cell_key', $setting)
            {{ $setting->text }}
            <small class="text-primary">{{ $setting->key }}</small>
            @endscope
            @scope('cell_type', $setting)
            {{ $this->getTypeEmoji($setting->type) }}
            @endscope
            @scope('cell_value', $setting)
            <div class="truncate max-w-xs">
                {{ is_string($setting->value) ? $setting->value : json_encode($setting->value) }}
            </div>
            @endscope
            @scope('actions', $setting)
            <x-button icon="o-pencil" wire:click="edit({{ $setting->id }})" spinner
                class="btn-ghost btn-sm text-blue-500" />
            @endscope
        </x-table>
    </x-card>

    <x-drawer wire:model="drawer" :title="$isEditing ? 'Editar Configuración' : 'Nueva Configuración'" right
        with-close-button class="lg:w-2/3">
        <x-form wire:submit="save">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <x-input label="Clave (Key)" wire:model="formData.key"
                    hint="Identif. único (ej. 'site_name')" />

                <x-select label="Tipo de Dato" wire:model.live="formData.type" :options="$this->types()" option-value="id"
                    option-label="name" />
            </div>

            <x-input label="Etiqueta (Texto)" wire:model="formData.text" hint="Nombre visible para el usuario" />

            <x-textarea label="Descripción" wire:model="formData.description"
                hint="Breve explicación de para qué sirve" />

            <x-textarea 
                label="Valor / Configuración" 
                wire:model="formData.value"
                :rows="isset($formData['type']) && $formData['type'] === 'json' ? 15 : 4"
                class="font-mono text-sm bg-base-300/50"
                :hint="isset($formData['type']) && $formData['type'] === 'json' ? 'Para objetos complejos use JSON válido. Para listas simples separe por comas.' : 'Utilice tipografía monoespaciada para mayor claridad.'"
            />


            <div class="flex justify-between w-full mt-1">
                @if($isEditing)
                    <x-dropdown icon="o-trash" class="btn-error btn-outline btn-sm mt-1">
                        <x-menu-item title="Confirmar Eliminar" wire:click="delete" spinner="delete" icon="o-trash"
                            class="text-red-500" />
                    </x-dropdown>
                @endif
                <div>
                    <x-button label="Cancelar" @click="$wire.drawer = false" />
                    <x-button label="Guardar" class="btn-primary" type="submit" spinner="save" />
                </div>
            </div>

        </x-form>
    </x-drawer>
</div>