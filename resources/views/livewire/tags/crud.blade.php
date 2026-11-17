<?php

use App\Models\Product;
use App\Models\Tag;
use Livewire\Volt\Component;
use Mary\Traits\Toast;
use Illuminate\Support\Str;

new class extends Component {
    use Toast;

    public array $formData = [];

    public function mount($tag = null)
    {
        if ($tag) {
            $tag = Tag::findOrFail($tag);
            $this->formData = $tag->toArray();
        } else {
            $this->formData = [
                'id' => null,
                'slug' => '',
                'name' => '',
                'description' => '',
                'type' => Tag::TYPE_COMMERCIAL,
                'color' => '',
                'fallback_image' => '',
                'sort_order' => 0,
            ];
        }
    }

    public function save()
    {
        $validated = $this->validate([
            'formData.name' => 'required|string|max:50|unique:tags,name,' . ($this->formData['id'] ?? null),
            'formData.slug' => 'nullable|string|max:50|unique:tags,slug,' . ($this->formData['id'] ?? null),
            'formData.description' => 'nullable|string|max:255',
            'formData.type' => 'required|in:commercial,division',
            'formData.color' => 'nullable|string|max:20',
            'formData.fallback_image' => 'nullable|string|max:120',
            'formData.sort_order' => 'nullable|integer|min:0',
        ]);

        $data = $validated['formData'];
        $data['type'] = $data['type'] ?: Tag::TYPE_COMMERCIAL;
        $data['description'] = trim((string) ($data['description'] ?? '')) ?: null;
        $data['color'] = trim((string) ($data['color'] ?? '')) ?: null;
        $data['fallback_image'] = trim((string) ($data['fallback_image'] ?? '')) ?: null;

        if (! $data['slug']) {
            $data['slug'] = Str::slug($data['name']);
        }

        $tag = Tag::updateOrCreate(
            ['id' => $this->formData['id'] ?? null],
            $data
        );

        if ($tag->exists) {
            Product::whereHas('tags', fn ($q) => $q->where('tags.id', $tag->id))
                ->update(['updated_at' => now()]);
        }

        Tag::clearCache();
        Product::clearCatalogFiltersCache();

        $this->success($this->formData['id'] ? 'Tag actualizado.' : 'Tag creado.', position: 'toast-bottom');
        return redirect('/tags');
    }

    public function delete()
    {
        $tag = Tag::findOrFail($this->formData['id']);

        Product::whereHas('tags', fn ($q) => $q->where('tags.id', $tag->id))
            ->update(['updated_at' => now()]);

        $tag->delete();
        Tag::clearCache();
        Product::clearCatalogFiltersCache();
        $this->success('Tag eliminado.', position: 'toast-bottom');
        return redirect('/tags');
    }
}; ?>

<div>
    <x-card title="Tag" shadow separator>
        <x-form wire:submit="save">
            <x-input label="Nombre" icon="o-tag" wire:model="formData.name" />
            <x-input label="Slug" icon="o-hashtag" wire:model="formData.slug" />
            <x-select
                label="Tipo de Tag"
                wire:model.live="formData.type"
                :options="[
                    ['id' => 'commercial', 'name' => 'Etiqueta Comercial / Promocional (Ej: Oferta, Nuevo, Edición Limitada)'],
                    ['id' => 'division', 'name' => 'División de Empresa / Unidad de Negocio (Ej: Marvel, DC)'],
                ]"
                hint="Los tags de división separan el catálogo por tienda (?store=), aplican color e imagen fallback y no se muestran como badges comerciales."
            />

            <x-input
                label="Palabras clave / Alias de respaldo (opcional)"
                icon="o-sparkles"
                wire:model="formData.description"
                placeholder="Ej: marvel, asgard, stark"
                hint="Para divisiones: términos separados por coma para asociar productos sin tag (3+ caracteres) o alias de URL (?store=)."
            />

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="label py-1"><span class="label-text text-xs font-semibold">Color identificatorio</span></label>
                    <div class="flex items-center gap-2">
                        <input
                            type="color"
                            value="{{ $formData['color'] ?: '#1b365d' }}"
                            wire:model.live.debounce.150ms="formData.color"
                            class="w-10 h-10 rounded cursor-pointer border border-base-300 bg-transparent p-0"
                        />
                        <x-input
                            wire:model="formData.color"
                            placeholder="#1b365d"
                            clearable
                            class="flex-1 font-mono"
                        />
                    </div>
                </div>

                <x-input
                    label="Imagen Fallback del tema (opcional)"
                    icon="o-photo"
                    wire:model="formData.fallback_image"
                    placeholder="Ej: fallback-marvel.webp"
                    hint="Archivo dentro de public/themes/{tema}/images/."
                />
            </div>

            <x-input label="Orden" type="number" wire:model="formData.sort_order" />

            <x-slot:actions>
                <x-button label="Guardar" icon="o-check" class="btn-primary" type="submit" spinner="save" />
                @if ($this->formData['id'] ?? null)
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
