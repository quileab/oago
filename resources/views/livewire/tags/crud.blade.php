<?php

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
                'sort_order' => 0,
            ];
        }
    }

    public function save()
    {
        $validated = $this->validate([
            'formData.name' => 'required|string|max:50|unique:tags,name,' . ($this->formData['id'] ?? null),
            'formData.slug' => 'nullable|string|max:50|unique:tags,slug,' . ($this->formData['id'] ?? null),
            'formData.sort_order' => 'nullable|integer|min:0',
        ]);

        $data = $validated['formData'];

        if (! $data['slug']) {
            $data['slug'] = Str::slug($data['name']);
        }

        Tag::updateOrCreate(
            ['id' => $this->formData['id'] ?? null],
            $data
        );

        Tag::clearCache();

        $this->success($this->formData['id'] ? 'Tag actualizado.' : 'Tag creado.', position: 'toast-bottom');
        return redirect('/tags');
    }

    public function delete()
    {
        Tag::findOrFail($this->formData['id'])->delete();
        Tag::clearCache();
        $this->success('Tag eliminado.', position: 'toast-bottom');
        return redirect('/tags');
    }
}; ?>

<div>
    <x-card title="Tag" shadow separator>
        <x-form wire:submit="save">
            <x-input label="Nombre" icon="o-tag" wire:model="formData.name" />
            <x-input label="Slug" icon="o-hashtag" wire:model="formData.slug" />
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
