{{-- SEARCH BAR START --}}
<div class="px-3 py-2 text-black bg-gray-400/50 shadow-md backdrop-blur-lg">
    <x-input type="search" placeholder="Buscar" wire:model.live.debounce.250ms="search" wire:keydown.enter="goSearch()"
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
        <div>
            @foreach (\App\Models\Product::getTags() as $tag)
                <x-button label="{{ $tag }}" icon="o-tag" wire:click="addTag('{{ $tag }}')" wire:key="tag-{{ $tag }}"
                    @class([
                        'btn-outline text-primary' => $tag != session('tag'),
                        'btn-success' => $tag == session('tag'),
                        'hover:bg-primary hover:text-white' => $tag != session('tag'),
                    ]) />
            @endforeach
            @if($brand || $category || session()->has('tag'))
                <x-button label="Limpiar Filtros" icon="o-eye-slash" class="btn-primary" wire:click="clearFilters()" />
            @endif
        </div>
    </div>
</div>