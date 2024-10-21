<div class="w-full bg-gray-600 text-gray-100 px-4 py-1">
    <x-input type="search" placeholder="Descripción" wire:model="search" class="w-full">
        <x-slot:prepend>
            <x-select wire:model="category"
            placeholder="Categoría" icon="o-user" class="rounded-e-none" 
            :options="$categories"
            option-label="category"
            option-value="category" />
        </x-slot:prepend>
        <x-slot:append>
            {{-- Add `rounded-s-none` class (RTL support) --}}
            <x-button
                wire:click="goSearch()" 
                label="Buscar" icon="o-magnifying-glass" class="btn-primary rounded-s-none" />
        </x-slot:append>
    </x-input>
</div>