<div class="sticky top-0 z-40 px-4 md:px-10 py-4  bg-white/90 backdrop-blur-md shadow-md">
    <div class="container mx-auto flex flex-col gap-3">
        
        <!-- Buscador Principal Estilizado -->
        <div class="w-full flex justify-center">
            <div class="bg-white border border-gray-300 rounded-full flex shadow-lg overflow-hidden w-full lg:w-3/4 p-1 items-center transition-shadow focus-within:shadow-xl">
                
                <!-- Input Búsqueda -->
                <div class="flex-grow flex items-center px-4">
                    <x-icon name="o-magnifying-glass" class="w-6 h-6 text-[#8c8c8c]" />
                    <input type="search" wire:model="search" wire:keydown.enter="goSearch()" placeholder="Buscar productos, marcas, códigos..." class="w-full outline-none px-3 py-2 text-gray-700 bg-transparent text-sm md:text-base font-semibold" />
                    
                    @if($search)
                        <button type="button" wire:click="clearSearch()" class="text-gray-400 hover:text-red-500 p-1">
                            <x-icon name="o-x-mark" class="w-5 h-5" />
                        </button>
                    @endif
                </div>

                <!-- Select Categorías -->
                <div class="border-l border-gray-300 hidden md:flex items-center px-2">
                    <select wire:model.live="category" class="select select-ghost bg-transparent focus:bg-transparent outline-none border-none text-sm font-bold text-[#8c8c8c]">
                        <option value="">CATEGORÍAS</option>
                        @foreach($categories as $cat)
                            <option value="{{ is_object($cat) ? $cat->category : $cat['category'] }}">{{ is_object($cat) ? $cat->category : $cat['category'] }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Select Marcas -->
                <div class="border-l border-gray-300 hidden md:flex items-center px-2">
                    <select wire:model.live="brand" class="select select-ghost bg-transparent focus:bg-transparent outline-none border-none text-sm font-bold text-[#8c8c8c]">
                        <option value="">MARCAS</option>
                        @foreach($brands as $b)
                            <option value="{{ is_object($b) ? $b->brand : $b['brand'] }}">{{ is_object($b) ? $b->brand : $b['brand'] }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Botón Buscar -->
                <button type="button" wire:click="goSearch()" class="bg-[#111111] text-white px-6 md:px-10 py-2 md:py-3 rounded-full font-bold hover:bg-gray-800 transition-colors text-sm md:text-base ml-1">
                    BUSCAR
                </button>
            </div>
        </div>

        <!-- Tags / Filtros extra -->
        <div class="w-full flex justify-center">
            @php
                $isCibat = request('store') === 'cibat';
                $brandBgClass = $isCibat ? 'bg-[#3d548f]' : 'bg-[#a6282e]';
                $brandHoverBgClass = $isCibat ? 'hover:bg-[#3d548f]' : 'hover:bg-[#a6282e]';
                $brandHoverBorderClass = $isCibat ? 'hover:border-[#3d548f]' : 'hover:border-[#a6282e]';
            @endphp
            <div class="join flex-wrap gap-2 justify-center">
                @foreach (\App\Models\Tag::allCached() as $tagItem)
                    <button type="button" wire:click="addTag('{{ $tagItem->slug }}')" wire:key="tag-{{ $tagItem->slug }}"
                        class="btn btn-sm join-item rounded-full transition-colors {{ $tag === $tagItem->slug ? $brandBgClass . ' text-white border-transparent' : 'btn-outline border-gray-300 text-gray-600 ' . $brandHoverBgClass . ' hover:text-white ' . $brandHoverBorderClass }}">
                        <x-icon name="o-tag" class="w-4 h-4 mr-1 inline" /> {{ $tagItem->name }}
                    </button>
                @endforeach
                
                @if($brand || $category || $tag || $similar)
                    <button type="button" wire:click="clearFilters()" class="btn btn-sm btn-error text-white join-item rounded-full ml-2">
                        <x-icon name="o-trash" class="w-4 h-4 mr-1 inline" /> Limpiar Filtros
                    </button>
                @endif
            </div>
        </div>

    </div>
</div>
