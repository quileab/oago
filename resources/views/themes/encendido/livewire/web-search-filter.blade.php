<div class="sticky top-0 z-40 px-4 py-3 glass-panel shadow-[0_10px_30px_rgba(0,0,0,0.5)] border-b border-white/10 backdrop-blur-2xl">
    <div class="max-w-7xl mx-auto">
        <div class="flex flex-col md:flex-row gap-3">
            <div class="w-full flex-1 flex items-stretch bg-neutral-900/80 border border-neutral-700/60 rounded-xl shadow-inner overflow-hidden focus-within:border-primary/80 focus-within:ring-2 focus-within:ring-primary/40 transition-all duration-300">
                <input type="search" placeholder="Buscar productos, marcas, códigos..." wire:model="search" wire:keydown.enter="goSearch()" id="search-input"
                    class="w-full bg-transparent border-none outline-none focus:ring-0 text-neutral-100 placeholder:text-neutral-500 px-4 py-3" />
                
                <button wire:click="clearSearch()" class="px-4 text-neutral-400 hover:text-white hover:bg-neutral-800 transition-colors flex items-center justify-center">
                    <x-icon name="o-x-mark" class="w-5 h-5" />
                </button>
                
                <button wire:click="goSearch()" class="px-6 bg-primary text-primary-content font-bold tracking-wider uppercase shadow-[0_0_15px_rgba(239,68,68,0.4)] hover:bg-red-600 transition-colors flex items-center gap-2">
                    <x-icon name="o-magnifying-glass" class="w-5 h-5" />
                    <span class="hidden sm:inline">Buscar</span>
                </button>
            </div>

            <div class="flex flex-wrap md:flex-nowrap gap-2 justify-center">
                <x-select wire:model.live="category" placeholder="Categoría" icon="o-tag"
                    :options="$categories" option-label="category" option-value="category"
                    class="bg-neutral-900/80 border border-neutral-700/60 shadow-inner text-neutral-100 focus:ring-2 focus:ring-primary/40 rounded-xl">
                </x-select>
                
                <x-select wire:model.live="brand" placeholder="Marca" icon="o-cube" 
                    :options="$brands" option-label="brand" option-value="brand" 
                    class="bg-neutral-900/80 border border-neutral-700/60 shadow-inner text-neutral-100 focus:ring-2 focus:ring-primary/40 rounded-xl">
                </x-select>
            </div>
        </div>

        <div class="flex flex-wrap items-center justify-start md:justify-center gap-2 mt-3">
            @foreach (\App\Models\Product::getTags() as $tag)
                <button wire:click="addTag('{{ $tag }}')" wire:key="tag-{{ $tag }}"
                    @class([
                        'badge badge-lg shadow-sm cursor-pointer transition-all hover:scale-105 font-black uppercase tracking-wider text-[10px] rounded-lg px-3 py-1',
                        'border border-neutral-700/60 text-neutral-300 bg-neutral-900/60 hover:bg-neutral-800 hover:border-neutral-500' => $tag != session('tag'),
                        'bg-gradient-to-r from-amber-500 to-orange-500 text-neutral-950 border-none shadow-[0_0_15px_rgba(245,158,11,0.5)] scale-105' => $tag == session('tag'),
                    ])>
                    <x-icon name="o-tag" class="w-3 h-3 mr-1" /> {{ $tag }}
                </button>
            @endforeach
            
            @if($brand || $category || $tag || $search)
                <button wire:click="clearFilters()" class="badge badge-lg badge-error badge-outline shadow-[0_0_10px_rgba(239,68,68,0.3)] cursor-pointer transition-all hover:bg-red-600 hover:text-white hover:scale-105 ml-2 font-bold text-[10px] uppercase rounded-lg border-red-500/50 flex items-center">
                    <x-icon name="o-x-mark" class="w-3.5 h-3.5 mr-1" /> Limpiar Filtros
                </button>
            @endif
        </div>
    </div>
</div>