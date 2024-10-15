<div class="w-full bg-gray-800 text-gray-100 flex gap-2 px-4 pb-1">
    <x-select placeholder="Categoría" icon="o-user" 
    :options="[
        ['id' => 1, 'name' => 'Galletita Dulce Simple'],
        ['id' => 2, 'name' => 'Alfajores Triples'],
        ['id' => 3, 'name' => 'Reposteros'],
    ]"  />
    <x-input placeholder="Buscar" icon="o-magnifying-glass" class="flex-1" />
</div>