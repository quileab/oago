{{-- SEARCH BAR START --}}
<div class="sticky top-0 z-40 px-3 py-2 text-black bg-gray-400/50 shadow-md backdrop-blur-lg">
    <x-input type="search" placeholder="Buscar" wire:model="search" wire:keydown.enter="goSearch()"
        class="w-full flex-1 bg-white text-black shadow-sm before:text-black after:text-black" id="search-input">
        <x-slot:append>
            {{-- Add `rounded-s-none` class (RTL support) --}}
            <x-button wire:click="clearSearch()" icon="o-x-mark" class="btn-primary rounded-none" />
            <x-button wire:click="goSearch()" label="Buscar" icon="o-magnifying-glass"
                class="btn-primary rounded-s-none" />
        </x-slot:append>
    </x-input>

    <div class="flex flex-wrap gap-2 mt-1 justify-center">
        <x-select wire:model.live="category" placeholder="Categoría" icon="o-clipboard-document-list"
            :options="$categories" option-label="category" option-value="category"
            class="bg-white text-black shadow-sm">
        </x-select>
        <x-select wire:model.live="brand" placeholder="Marca" icon="o-clipboard-document-list" class="w-full mb-2"
            :options="$brands" option-label="brand" option-value="brand" class="bg-white text-black shadow-sm">
        </x-select>
        <div class="join flex-wrap gap-y-1 justify-center">
            @foreach (\App\Models\Tag::allCached() as $tagItem)
                <x-button label="{{ $tagItem->name }}" icon="o-tag" wire:click="addTag('{{ $tagItem->slug }}')" wire:key="tag-{{ $tagItem->slug }}"
                    @class([
                        'join-item',
                        'btn-outline text-primary' => $tag !== $tagItem->slug,
                        'btn-success' => $tag === $tagItem->slug,
                        'hover:bg-primary hover:text-white' => $tag !== $tagItem->slug,
                    ]) />
            @endforeach
            @if($brand || $category || $tag || $similar)
                <x-button icon="o-trash" class="btn-primary join-item" wire:click="clearFilters()" tooltip="Limpiar Filtros" />
            @endif
        </div>
        </div>
    </div>
</div>