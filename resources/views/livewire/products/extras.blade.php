<?php

use Livewire\Volt\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;
use \App\Models\Product;


new class extends Component {
    use WithPagination;
    use Toast;

    public $perPage = 30;
    public string $search = '';

    public bool $drawer = false;

    public array $sortBy = ['column' => 'id', 'direction' => 'asc'];
    public array $selected = []; // Add selected property
    public array $tags_list = [
        ['name' => 'Publicado', 'value' => false, 'action' => 'nothing'],
        ['name' => 'Destacado', 'value' => false, 'action' => 'nothing'],
    ];

    public array $actions = [
        ['id' => 0, 'name' => 'nothing', 'value' => 'Nada'],
        ['id' => 1, 'name' => 'apply', 'value' => 'Aplicar'], // Added new action
        ['id' => 2, 'name' => 'remove', 'value' => 'Remover'],
    ];

    public $htmldescription = '';

    public function mount()
    {
        $this->tags_list = [
            ['name' => 'Publicado', 'value' => false, 'action' => 'nothing'],
            ['name' => 'Destacado', 'value' => false, 'action' => 'nothing'],
        ];
        foreach (Product::getTags() as $key => $value) {
            $this->tags_list[] = ['name' => $value, 'value' => false, 'action' => 'nothing'];
        }
    }

    // Table headers
    public function headers(): array
    {
        return [
            ['key' => 'id', 'label' => '#', 'class' => 'w-1'],
            ['key' => 'brand', 'label' => 'Marca'],
            ['key' => 'description_html', 'label' => 'Descripción'],
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

    public function openDrawer()
    {
        $this->drawer = true;
        // if selected products are not empty, show the load description_html into the editor
        if (!empty($this->selected)) {
            $this->htmldescription = Product::find($this->selected[0])->description_html;
        }
    }

    public function applyPromotions()
    {
        // dd($this->tags_list);
        $this->drawer = false;
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
                    if (in_array($tag['name'], Product::getTags())) {
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

        $this->success(
            'Atributos aplicados',
            'La página se recargará para mostrar los cambios...',
            redirectTo: request()->header('Referer')
        );
    }

    public function selectByDescriptionHtml()
    {
        if (empty($this->selected)) {
            $this->warning('Seleccione al menos un producto para usar esta función.');
            return;
        }

        // Get the description_html of the first selected product
        $firstSelectedProductId = $this->selected[0];
        $product = Product::find($firstSelectedProductId);

        if (!$product || empty($product->description_html)) {
            $this->warning('El producto seleccionado no tiene una descripción HTML para buscar.');
            return;
        }

        $descriptionHtmlToMatch = $product->description_html;
        $this->htmldescription = $descriptionHtmlToMatch;

        // Find all products with the same description_html
        $productsToSelect = Product::where('description_html', $descriptionHtmlToMatch)->pluck('id')->toArray();

        // Update the selected property
        $this->selected = $productsToSelect;

        $this->success('Productos seleccionados y descripción cargada.');
    }

}; ?>

<div>
    <!-- HEADER -->
    <div class="sticky top-0 z-50">
        <x-header title="Productos" separator progress-indicator class="backdrop-blur-xl py-1">
            <x-slot:middle class="!justify-end">
                <x-input placeholder="Search..." wire:model.live.debounce="search" clearable
                    icon="o-magnifying-glass" />
            </x-slot:middle>
            <x-slot:actions>
                <x-button label="Opciones" wire:click="openDrawer()" responsive icon="o-bars-3" />
            </x-slot:actions>
        </x-header>
    </div>

    <!-- TABLE  -->
    <x-table :headers="$headers" :rows="$products" :sort-by="$sortBy" with-pagination selectable
        wire:model.live.debounce="selected" row-key="id">
        @scope('cell_brand', $product)
        {{ $product->brand . ' » ' . $product->model . ' » ' . $product->description }}
        @endscope
        @scope('cell_description_html', $product)
        {!! $product->description_html !!}
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
        <x-form wire:submit="applyPromotions" id="promotion">
            <div @if (!count($selected)) style="display: none;" @endif>
                <div>
                    @foreach ($tags_list as $tag)
                        <div class="flex items-center gap-2">
                            <x-group wire:model="tags_list.{{ $loop->index }}.action" :options="$actions" option-value="name"
                                option-label="value" class="[&:checked]:!btn-primary" />
                            {{ $tag['name'] }}
                        </div>
                    @endforeach
                </div>

                @php
                    // Configuración para el editor TinyMCE
                    $config = [
                        'license_key' => 'gpl',
                        'plugins' => 'autoresize link image quickbars',
                        'statusbar' => false,
                        'toolbar' => 'undo redo | bold italic underline | forecolor backcolor | h1 h2 h3 h4 h5 h6 | removeformat',
                        'quickbars_selection_toolbar' => 'bold italic underline',
                    ];
                @endphp

                {{-- Editor de contenido --}}
                <x-editor wire:model="htmldescription" label="Descripción - Items Seleccionados: {{ count($selected) }}"
                    :config="$config" />

                <div class="grid grid-cols-2 gap-4 mt-4">
                    <x-button label="Seleccionar por Descripción HTML" wire:click="selectByDescriptionHtml"
                        class="btn-primary" />

                    <x-button label="Aplicar" icon="o-check" class="btn-primary" type="submit"
                        spinner="applyPromotions" />
                </div>
            </div>
        </x-form>

        @if (!count($selected))
            <x-alert title="NADA SELECCIONADO" description="Seleccione al menos un producto para aplicar promociones"
                icon="o-exclamation-triangle" class="alert-info mt-8" />
        @endif
    </x-drawer>

</div>