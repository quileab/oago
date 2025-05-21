<?php

use Livewire\Volt\Component;
use Livewire\WithPagination;
//use Livewire\WithoutUrlPagination;
//use Illuminate\Pagination\LengthAwarePaginator;
use Mary\Traits\Toast;
use \App\Models\Product;


new class extends Component {
    use WithPagination;
    //use WithoutUrlPagination;
    use Toast;

    public $perPage = 12;
    public string $search = '';

    public bool $drawer = false;

    public array $sortBy = ['column' => 'id', 'direction' => 'asc'];
    public array $selected = []; // Add selected property
    public array $tags_list = [
        ['name' => 'Publicado', 'value' => false, 'action' => 'nothing'],
        ['name' => 'Destacado', 'value' => false, 'action' => 'nothing'],
        ['name' => 'OFERTA', 'value' => false, 'action' => 'nothing'],
        ['name' => 'REMATE', 'value' => false, 'action' => 'nothing'],
        ['name' => 'NUEVOS', 'value' => false, 'action' => 'nothing'],
    ];
    public array $actions = [
        ['id' => 0, 'name' => 'nothing', 'value' => 'Nada'],
        ['id' => 1, 'name' => 'apply', 'value' => 'Aplicar'], // Added new action
        ['id' => 2, 'name' => 'remove', 'value' => 'Remover'],
    ];

    public $htmldescription = '';

    // Table headers
    public function headers(): array
    {
        return [
            ['key' => 'id', 'label' => '#', 'class' => 'w-1'],
            ['key' => 'brand', 'label' => 'Marca'],
            ['key' => 'published', 'label' => 'Publicado'],
            ['key' => 'visibility', 'label' => 'Visibilidad'],
            ['key' => 'featured', 'label' => 'Destacado'],
            ['key' => 'tags', 'label' => 'Etiquetas'],
        ];
    }

    public function with(): array
    {
        return [
            'products' => $this->products(),
            'headers' => $this->headers(),
        ];
    }

    // Reset pagination when any component property changes
    public function updated($property): void
    {
        if (in_array($property, ['search', 'sortBy', 'perPage'])) {
            $this->resetPage();
        }
    }

    public function products()//: LengthAwarePaginator //Collection
    {
        $query = Product::query();
        // split search string into words
        $search_multiple = explode(' ', $this->search);


        if ($this->search) {
            //$query->where(DB::raw('concat(brand," ",ifnull(model,"")," ",description)'), 'like', "%$this->search%");
            $query->where(function ($query) use ($search_multiple) {
                foreach ($search_multiple as $word) {
                    $query->where(
                        DB::raw('concat(brand," ",ifnull(model,"")," ",description, " ",product_type," ",category)'),
                        'like',
                        '%' . $word . '%'
                    );
                }
            });
        }

        return $query->orderBy(...array_values($this->sortBy))
            ->paginate($this->perPage);
        //->limit($this->perPage)->get();
    }

    public function applyPromotions()
    {
        $this->drawer = false;
        // dd($this->tags_list);

        // Apply promotions to selected products
        foreach ($this->selected as $productId) {
            $product = Product::find($productId);
            if ($product) {
                $tags = explode('|', $product->tags);
                foreach ($this->tags_list as $tag) {
                    if ($tag['action'] != 'nothing' && $tag['name'] == 'Destacado') {
                        $product->featured = $tag['action'] == 'apply' ? true : false;
                    }
                    if ($tag['action'] != 'nothing' && $tag['name'] == 'Publicado') {
                        $product->published = $tag['action'] == 'apply' ? true : false;
                    }
                    if (in_array($tag['name'], ['OFERTA', 'REMATE', 'NUEVOS'])) {
                        if ($tag['action'] == 'apply' && !in_array($tag['name'], $tags)) {
                            $tags[] = $tag['name'];
                        }
                        if ($tag['action'] == 'remove' && in_array($tag['name'], $tags)) {
                            $key = array_search($tag['name'], $tags);
                            unset($tags[$key]);
                        }
                    }
                }
                $product->tags = implode('|', $tags);
                $product->description_html = $this->htmldescription;
                $product->save();
            }
        }
        $this->reset('selected', 'tags_list');
        $this->success('Atributos aplicados 👍');
    }

}; ?>

<div>
    <!-- HEADER -->
    <x-header title="Productos" separator progress-indicator>
        <x-slot:middle class="!justify-end">
            <x-input placeholder="Search..." wire:model.live.debounce="search" clearable icon="o-magnifying-glass" />
        </x-slot:middle>
        <x-slot:actions>
            <x-button label="Opciones" @click="$wire.drawer = true" responsive icon="o-bars-3" />
        </x-slot:actions>
    </x-header>

    <!-- TABLE  -->
    <x-table :headers="$headers" :rows="$products" :sort-by="$sortBy" with-pagination selectable
        wire:model.live.debounce="selected">
        @scope('cell_brand', $product)
        {{ $product->brand . ' » ' . $product->model . ' » ' . $product->description }}
        @endscope
        @scope('cell_published', $product)
        {{ $product->published ? 'Si' : 'No' }}
        @endscope
        @scope('cell_featured', $product)
        {{ $product->featured ? 'Si' : 'No' }}
        @endscope
        @scope('cell_tags', $product)
        {{ $product->tags ? str_replace('|', ' ', $product->tags) : 'N/A' }}
        @endscope
    </x-table>

    <!-- FILTER DRAWER -->
    <x-drawer wire:model="drawer" title="Atributos" right separator with-close-button class="lg:w-1/3">
        @if(count($selected))
            <x-form wire:submit="applyPromotions" id="promotion">
                <div>
                    @foreach ($tags_list as $tag)
                        <div class="flex items-center gap-2">
                            {{-- <x-checkbox label="{{ $tag['name'] }}" wire:model="tags_list.{{ $loop->index }}.value" /> --}}
                            <x-group wire:model="tags_list.{{ $loop->index }}.action" :options="$actions" option-value="name"
                                option-label="value" class="[&:checked]:!btn-primary" />
                            {{ $tag['name'] }}
                        </div>
                    @endforeach

                    @php
                        // Configuración para el editor TinyMCE
                        $config = [
                            'license_key' => 'gpl',
                            'plugins' => 'autoresize link image quickbars', // Añadido 'image' plugin
                            'statusbar' => false,
                            'toolbar' => 'undo redo | bold italic underline | forecolor backcolor | h1 h2 h3 h4 h5 h6 | removeformat', // Añadido 'image' a la toolbar
                            'quickbars_selection_toolbar' => 'bold italic underline',
                            // Opcional: Configuración para subida de imágenes en TinyMCE (si quieres esa funcionalidad)
                            // 'images_upload_url' => '/your-image-upload-handler', // Define tu ruta de subida de imágenes
                            // 'automatic_uploads' => true,
                            // 'file_picker_types' => 'image',
                        ];
                    @endphp
                    {{-- Editor de contenido --}}
                    <x-editor wire:model="htmldescription" label="Descripción" :config="$config" />

                    <x-slot:actions>
                        <x-button label="Aplicar" icon="o-check" class="btn-primary mr-4" type="submit"
                            spinner="applyPromotions" />
                    </x-slot:actions>
                </div>
            </x-form>
        @else
            <x-input placeholder="Buscar..." wire:model.live.debounce="search" icon="o-magnifying-glass"
                @keydown.enter="$wire.drawer = false" />
            <x-alert title="NADA SELECCIONADO" description="Seleccione al menos un producto para aplicar promociones"
                icon="o-exclamation-triangle" class="alert-info mt-2" />
        @endif
    </x-drawer>

</div>