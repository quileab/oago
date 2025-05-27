<div>
    <x-drawer wire:model="showFilters" title="Filtros" separator with-close-button close-on-escape
        class="w-8/12 md:w-1/4 top-0 text-white">
        <x-select wire:model="category" placeholder="Categoría" icon="o-clipboard-document-list" :options="$categories"
            option-label="category" option-value="category">
            <x-slot:append>
                {{-- Add `rounded-s-none` (RTL support) --}}
                <x-button label="Borrar" icon="o-trash" class="rounded-s-none btn-primary"
                    wire:click="$set('category', null)" />
            </x-slot:append>
        </x-select>
        <br>
        <x-select wire:model="brand" placeholder="Marca" icon="o-clipboard-document-list" class="w-full mb-2"
            :options="$brands" option-label="brand" option-value="brand">
            <x-slot:append>
                <x-button label="Borrar" icon="o-trash" class="rounded-s-none btn-primary"
                    wire:click="$set('brand', null)" />
            </x-slot:append>
        </x-select>
        <div class="flex flex-wrap gap-2">
            @foreach (\App\Models\Product::getTags() as $tag)
                <x-button label="{{ $tag }}" icon="o-tag" wire:click="addTag('{{ $tag }}')" wire:key="tag-{{ $tag }}"
                    @class(['btn-success' => $tag != session('tag')]) />
            @endforeach
        </div>
        <x-slot:actions>
            <x-button label="CERRAR" icon="o-x-mark" class="btn-error" @click="$wire.showFilters = false" />
            <x-button label="BUSCAR" class="btn-primary" icon="o-magnifying-glass" wire:click="goSearch()" />
        </x-slot:actions>
    </x-drawer>
    {{-- SEARCH BAR START --}}
    <div class="px-3 py-2 text-black bg-white/50 shadow-md backdrop-blur-sm">
        <x-input type="search" placeholder="Buscar" wire:model="search" wire:keydown.enter="goSearch()"
            class="w-full flex-1 bg-white text-black">
            <x-slot:prepend>
                <x-button label="Filtros {{ $category }} {{ $brand }}" icon="o-funnel" @click="$wire.showFilters = true"
                    class="btn-primary rounded-e-none" />
            </x-slot:prepend>
            <x-slot:append>
                {{-- Add `rounded-s-none` class (RTL support) --}}
                <x-button wire:click="goSearch()" label="Buscar" icon="o-magnifying-glass"
                    class="btn-primary rounded-s-none" />
            </x-slot:append>
        </x-input>
    </div>
</div>