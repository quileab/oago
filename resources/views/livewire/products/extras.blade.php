<?php

use Livewire\Volt\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;
use \App\Models\Product;
use \App\Models\Tag;


new class extends Component {
    use WithPagination;
    use Toast;

    public $perPage = 30;
    public string $search = '';

    public bool $drawer = false;

    public array $sortBy = ['column' => 'id', 'direction' => 'asc'];
    public array $selected = []; // Add selected property
    public array $tags_list = [
        ['name' => 'Publicado', 'value' => false, 'action' => 'nothing', 'slug' => null, 'is_tag' => false],
        ['name' => 'Destacado', 'value' => false, 'action' => 'nothing', 'slug' => null, 'is_tag' => false],
    ];

    public array $actions = [
        ['id' => 0, 'name' => 'nothing', 'value' => 'Nada'],
        ['id' => 1, 'name' => 'apply', 'value' => 'Aplicar'], // Added new action
        ['id' => 2, 'name' => 'remove', 'value' => 'Remover'],
    ];

    public $htmldescription = '';
    public ?int $bonus_threshold = null;
    public ?int $bonus_amount = null;

    public function mount()
    {
        $this->tags_list = [
            ['name' => 'Publicado', 'value' => false, 'action' => 'nothing', 'slug' => null, 'is_tag' => false],
            ['name' => 'Destacado', 'value' => false, 'action' => 'nothing', 'slug' => null, 'is_tag' => false],
        ];
        foreach (Tag::allCached() as $tag) {
            $this->tags_list[] = [
                'name' => $tag->name,
                'slug' => $tag->slug,
                'value' => false,
                'action' => 'nothing',
                'is_tag' => true,
            ];
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

    public function products()
    {
        $query = Product::query()->with('tags');
        $search_multiple = explode(' ', $this->search);

        if ($this->search) {
            $query->where(function ($query) use ($search_multiple) {
                foreach ($search_multiple as $word) {
                    $query->where(
                        DB::raw('concat(brand," ",ifnull(model,"")," ",description, " ",product_type," ",category)'),
                        'like',
                        '%'.$word.'%'
                    );
                }
            });
        }

        return $query->orderBy(...array_values($this->sortBy))
            ->paginate($this->perPage);
    }

    public function openDrawer()
    {
        $this->drawer = true;
        if (!empty($this->selected)) {
            $firstSelectedProduct = Product::find($this->selected[0]);
            $this->htmldescription = $firstSelectedProduct->description_html;
            $this->bonus_threshold = $firstSelectedProduct->bonus_threshold;
            $this->bonus_amount = $firstSelectedProduct->bonus_amount;
        } else {
            $this->htmldescription = '';
            $this->bonus_threshold = null;
            $this->bonus_amount = null;
        }
    }

    public function applyPromotions()
    {
        $this->drawer = false;

        // Collect tag operations from tags_list
        $tagsToApply = [];
        $tagsToRemove = [];
        $publishedAction = 'nothing';
        $featuredAction = 'nothing';

        foreach ($this->tags_list as $tag) {
            if (! $tag['is_tag']) {
                if ($tag['name'] === 'Publicado') {
                    $publishedAction = $tag['action'];
                } elseif ($tag['name'] === 'Destacado') {
                    $featuredAction = $tag['action'];
                }
            } else {
                if ($tag['action'] === 'apply') {
                    $tagsToApply[] = $tag['slug'];
                } elseif ($tag['action'] === 'remove') {
                    $tagsToRemove[] = $tag['slug'];
                }
            }
        }

        $htmldescription = $this->htmldescription;
        $bonusThreshold = $this->bonus_threshold;
        $bonusAmount = $this->bonus_amount;

        Product::withoutEvents(function () use ($tagsToApply, $tagsToRemove, $publishedAction, $featuredAction, $htmldescription, $bonusThreshold, $bonusAmount) {
            foreach ($this->selected as $productId) {
                $product = Product::find($productId);
                if (! $product) {
                    continue;
                }

                // Handle Published/Featured boolean actions
                if ($publishedAction === 'apply') {
                    $product->published = true;
                } elseif ($publishedAction === 'remove') {
                    $product->published = false;
                }

                if ($featuredAction === 'apply') {
                    $product->featured = true;
                } elseif ($featuredAction === 'remove') {
                    $product->featured = false;
                }

                // Handle tags via pivot
                if (! empty($tagsToApply) || ! empty($tagsToRemove)) {
                    $currentTagIds = $product->tags()->allRelatedIds()->all();

                    if (! empty($tagsToRemove)) {
                        $removeIds = Tag::whereIn('slug', $tagsToRemove)->pluck('id')->all();
                        $currentTagIds = array_values(array_diff($currentTagIds, $removeIds));
                    }

                    if (! empty($tagsToApply)) {
                        $applyIds = Tag::whereIn('slug', $tagsToApply)->pluck('id')->all();
                        $currentTagIds = array_values(array_unique(array_merge($currentTagIds, $applyIds)));
                    }

                    $product->tags()->sync($currentTagIds);

                    // Update legacy string column
                    $tagNames = Tag::whereIn('id', $currentTagIds)->pluck('name')->all();
                    $product->tags = implode('|', $tagNames);
                }

                $product->description_html = $htmldescription;

                if (! is_null($bonusThreshold)) {
                    $product->bonus_threshold = $bonusThreshold;
                }
                if (! is_null($bonusAmount)) {
                    $product->bonus_amount = $bonusAmount;
                }

                $product->save();
            }
        });

        Tag::clearCache();

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

    public function cycleTagAction($index)
    {
        $currentAction = $this->tags_list[$index]['action'];
        $nextAction = match ($currentAction) {
            'nothing' => 'apply',
            'apply' => 'remove',
            'remove' => 'nothing',
            default => 'nothing',
        };
        $this->tags_list[$index]['action'] = $nextAction;
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
        {{ collect($product->tags_array)->join(' ') ?: 'N/A' }}
        @endscope
    </x-table>

    <!-- FILTER DRAWER -->
    <x-drawer wire:model="drawer" title="Atributos" right separator with-close-button class="lg:w-1/2">
        <x-form wire:submit="applyPromotions" id="promotion">
            <div @if (!count($selected)) style="display: none;" @endif>
                <div class="grid grid-cols-2 gap-2 mb-4">
                    @foreach ($tags_list as $tag)
                        <div class="flex items-center justify-between p-2 border border-base-200 rounded-lg">
                            <span class="font-bold text-sm">{{ $tag['name'] }}</span>
                            <x-button wire:click="cycleTagAction({{ $loop->index }})"
                                :label="$tag['action'] === 'nothing' ? 'Ignorar' : ($tag['action'] === 'apply' ? 'Aplicar' : 'Remover')"
                                :class="$tag['action'] === 'nothing' ? 'btn-ghost btn-xs' : ($tag['action'] === 'apply' ? 'btn-success text-white btn-xs' : 'btn-error text-white btn-xs')"
                                :icon="$tag['action'] === 'nothing' ? 'o-minus' : ($tag['action'] === 'apply' ? 'o-plus' : 'o-trash')" />
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

                <hr class="my-4" />

                <h3 class="text-lg font-bold mb-2">Descuento por Cantidad (Bonificación)</h3>
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <x-input label="Umbral de Bonificación (Cantidad)" wire:model="bonus_threshold" type="number" min="0" placeholder="Ej: 23" />
                    <x-input label="Cantidad de Bonificación (Regalo)" wire:model="bonus_amount" type="number" min="0" placeholder="Ej: 1" />
                </div>

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