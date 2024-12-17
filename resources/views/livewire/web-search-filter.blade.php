<div class="md:grid grid-flow-col bg-slate-700  text-gray-100 px-4 py-1">
    <x-select wire:model="category"
    placeholder="Categoría" icon="o-user" class="w-full" 
    :options="$categories"
    option-label="category"
    option-value="category" />
    <x-input type="search" placeholder="Descripción" 
        wire:model="search"
        wire:keydown.enter="goSearch()" 
        class="w-full flex-1">
        <x-slot:append>
            {{-- Add `rounded-s-none` class (RTL support) --}}
            <x-button
                wire:click="goSearch()" 
                label="Buscar" icon="o-magnifying-glass" class="btn-primary rounded-s-none" />
        </x-slot:append>
    </x-input>
</div>