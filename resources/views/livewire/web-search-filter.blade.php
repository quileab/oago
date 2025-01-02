<div class="md:grid grid-flow-col bg-slate-700 text-gray-100 px-4 py-1">
    <x-drawer wire:model="showFilters" 
        title="Filtros"
        separator
        with-close-button
        close-on-escape
        class="w-11/12 lg:w-1/3">
        <x-select wire:model="category"
            placeholder="Categoría" icon="o-clipboard-document-list" class="w-full mb-2" 
            :options="$categories"
            option-label="category"
            option-value="category" />

        {{-- <x-choices-offline
            label="Marcas"
            wire:model="brand"
            :options="$brands"
            option-label="brand"
            option-value="brand"
            placeholder="Buscar ..."
            single
            searchable /> --}}
        <x-select wire:model="brand"
            placeholder="Marca" icon="o-clipboard-document-list" class="w-full mb-2" 
            :options="$brands"
            option-label="brand"
            option-value="brand" />
        <x-button label="OFERTAS" icon="o-tag" class="btn-success w-full mb-2" />
        <x-button label="REMATES" icon="o-tag" class="btn-success w-full mb-2" />

        <x-slot:actions>
            <x-button label="CERRAR" icon="o-x-mark" class="btn-error" @click="$wire.showFilters = false" />
            <x-button label="BUSCAR" class="btn-primary" icon="o-magnifying-glass" wire:click="goSearch()" />
        </x-slot:actions>
    </x-drawer>
    
    <x-input type="search" placeholder="Descripción" 
        wire:model="search"
        wire:keydown.enter="goSearch()" 
        class="w-full flex-1">
        <x-slot:prepend>
            <x-button label="Filtros {{ $category }}" icon="o-funnel"
                @click="$wire.showFilters = true" 
                class="btn-primary rounded-e-none" />
        
        </x-slot:prepend>
        <x-slot:append>
            {{-- Add `rounded-s-none` class (RTL support) --}}
            <x-button
                wire:click="goSearch()" 
                label="Buscar" icon="o-magnifying-glass" class="btn-primary rounded-s-none" />
        </x-slot:append>
    </x-input>
</div>