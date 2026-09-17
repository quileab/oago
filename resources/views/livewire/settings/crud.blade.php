<?php

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;
use Livewire\Volt\Component;
use Mary\Traits\Toast;

new class extends Component
{
    use Toast;

    public $settings;

    public bool $drawer = false;

    public array $formData = [];

    public bool $isEditing = false;

    public bool $editorMode = false;

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
            'description' => '',
        ];
        $this->isEditing = false;
        $this->drawer = true;
    }

    public function edit($id): void
    {
        $setting = Setting::findOrFail($id);
        $this->formData = $setting->toArray();

        if ($this->formData['type'] === 'boolean') {
            $this->formData['value'] = filter_var($this->formData['value'], FILTER_VALIDATE_BOOLEAN);
        } elseif ($this->formData['type'] === 'json') {
            $value = is_string($this->formData['value'])
                ? json_decode($this->formData['value'], true)
                : $this->formData['value'];

            if (is_array($value)) {
                if (collect($value)->every(fn ($item) => is_string($item) || is_numeric($item))) {
                    $this->formData['value'] = implode(',', $value);
                } else {
                    $this->formData['value'] = json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                }
            }
        }

        $this->isEditing = true;
        $this->drawer = true;
    }

    public function save(): void
    {
        if ($this->editorMode) {
            $rules = [
                'formData.key' => ['required', 'string', 'max:255', Rule::unique('settings', 'key')->ignore($this->formData['id'] ?? null)],
                'formData.type' => 'required|in:string,number,boolean,json',
                'formData.text' => 'required|string|max:255',
                'formData.description' => 'nullable|string|max:255',
                'formData.value' => 'nullable',
            ];

            $this->validate($rules);

            $data = $this->formData;

            if ($data['type'] === 'boolean') {
                $data['value'] = filter_var($data['value'], FILTER_VALIDATE_BOOLEAN) ? '1' : '0';
            } elseif ($data['type'] === 'json' && ! empty($data['value'])) {
                $trimmedValue = trim($data['value']);

                if (str_starts_with($trimmedValue, '{') || str_starts_with($trimmedValue, '[')) {
                    $decoded = json_decode($trimmedValue, true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $data['value'] = json_encode($decoded);
                    } else {
                        $this->addError('formData.value', 'El formato JSON no es válido: '.json_last_error_msg());

                        return;
                    }
                } else {
                    $data['value'] = json_encode(array_map('trim', explode(',', $data['value'])));
                }
            }

            Setting::updateOrCreate(
                ['id' => $this->formData['id'] ?? null],
                $data
            );

            Cache::forget('settings.'.$data['key']);
        } else {
            $this->validate(['formData.value' => 'nullable']);

            $setting = Setting::findOrFail($this->formData['id']);
            $rawValue = $this->formData['value'];

            if ($setting->type === 'boolean') {
                $rawValue = filter_var($rawValue, FILTER_VALIDATE_BOOLEAN) ? '1' : '0';
            } elseif ($setting->type === 'json' && ! empty($rawValue)) {
                $trimmedValue = trim($rawValue);

                if (str_starts_with($trimmedValue, '{') || str_starts_with($trimmedValue, '[')) {
                    $decoded = json_decode($trimmedValue, true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $rawValue = json_encode($decoded);
                    } else {
                        $this->addError('formData.value', 'El formato JSON no es válido: '.json_last_error_msg());

                        return;
                    }
                } else {
                    $rawValue = json_encode(array_map('trim', explode(',', $rawValue)));
                }
            }

            $setting->update(['value' => $rawValue]);
            Cache::forget('settings.'.$setting->key);
        }

        $this->drawer = false;
        $this->refreshSettings();
        $this->success('Configuración actualizada.');
    }

    public function delete($id = null): void
    {
        $id = $id ?? $this->formData['id'];
        $setting = Setting::findOrFail($id);
        Cache::forget('settings.'.$setting->key);

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

    public function getTypeIcon($type): string
    {
        return match ($type) {
            'number' => 'o-calculator',
            'boolean' => 'o-check-circle',
            'json' => 'o-code-bracket',
            default => 'o-document-text',
        };
    }
}; ?>

<div>
    <x-header title="Administrar Configuraciones" separator progress-indicator>
        <x-slot:actions>
            <x-toggle
                wire:model.live="editorMode"
                label="Modo Editor"
                class="mr-2"
            />
            @if($editorMode)
                <x-button label="Nueva Configuración" @click="$wire.create()" icon="o-plus" class="btn-primary" />
            @endif
        </x-slot:actions>
    </x-header>

    <x-card>
        <x-table :headers="[
            ['key' => 'key', 'label' => 'Configuración'],
            ['key' => 'value', 'label' => 'Valor'],
        ]" :rows="$settings" striped @row-click="$wire.edit($event.detail.id)">
            @scope('cell_key', $setting)
            <div>
                <span class="font-medium">{{ $setting->text }}</span>
                @if($setting->description)
                    <p class="text-xs text-base-content/50 mt-0.5">{{ $setting->description }}</p>
                @endif
            </div>
            @endscope
            @scope('cell_value', $setting)
            <div class="flex items-center gap-2">
                <x-icon :name="$this->getTypeIcon($setting->type)" class="w-4 h-4 text-base-content/60 shrink-0" />
                <span class="font-mono text-sm truncate max-w-xs block">
                    @if($setting->type === 'boolean')
                        <span @class(['badge badge-sm font-sans', filter_var($setting->value, FILTER_VALIDATE_BOOLEAN) ? 'badge-success' : 'badge-ghost text-base-content/60'])>
                            {{ filter_var($setting->value, FILTER_VALIDATE_BOOLEAN) ? 'Sí' : 'No' }}
                        </span>
                    @else
                        {{ is_string($setting->value) ? $setting->value : json_encode($setting->value) }}
                    @endif
                </span>
            </div>
            @endscope
        </x-table>
    </x-card>

    <x-drawer wire:model="drawer" :title="$isEditing ? ($editorMode ? 'Editar Configuración' : 'Cambiar Valor') : 'Nueva Configuración'" right
        with-close-button class="lg:w-1/2">
        <x-form wire:submit="save">

            @if($editorMode)
                <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
                    <x-input label="Clave (Key)" wire:model="formData.key"
                        hint="Identif. único (ej. 'site_name')" />

                    <x-select label="Tipo de Dato" wire:model.live="formData.type" :options="$this->types()" option-value="id"
                        option-label="name" />

                    <x-input label="Etiqueta (Texto)" wire:model="formData.text" hint="Nombre visible" />
                </div>

                <x-textarea label="Descripción" wire:model="formData.description" rows="2"
                    hint="Breve explicación de para qué sirve" />
            @else
                <div class="mb-4">
                    <p class="font-semibold text-base">{{ $formData['text'] ?? '' }}</p>
                    @if(!empty($formData['description']))
                        <p class="text-sm text-base-content/50 mt-0.5">{{ $formData['description'] }}</p>
                    @endif
                </div>
            @endif

            @php $currentType = $formData['type'] ?? 'string'; @endphp

            @if($currentType === 'boolean')
                <x-toggle label="Valor" wire:model="formData.value" />
            @elseif($currentType === 'number')
                <x-input label="Valor" wire:model="formData.value" type="number" />
            @else
                <x-textarea
                    label="Valor"
                    wire:model="formData.value"
                    rows="3"
                    class="font-mono text-sm"
                    :hint="$currentType === 'json' ? 'Para listas simples separe por comas. Para JSON complejo use sintaxis { }.' : null"
                />
            @endif

            <div class="flex justify-between w-full mt-4">
                @if($isEditing && $editorMode)
                    <x-dropdown icon="o-trash" class="btn-error btn-outline btn-sm">
                        <x-menu-item title="Confirmar Eliminar" wire:click="delete" spinner="delete" icon="o-trash"
                            class="text-red-500" />
                    </x-dropdown>
                @endif
                <div class="{{ ($isEditing && $editorMode) ? '' : 'ml-auto' }} flex gap-2">
                    <x-button label="Cancelar" @click="$wire.drawer = false" />
                    <x-button label="Guardar" class="btn-primary" type="submit" spinner="save" />
                </div>
            </div>

        </x-form>
    </x-drawer>
</div>