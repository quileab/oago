<?php

use App\Models\Product;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Mary\Traits\Toast;

new class extends Component
{
    use Toast, WithFileUploads, WithPagination;

    public string $search = '';

    /** @var array<int, string> */
    public array $searchFields = [
        'product_type',
        'brand',
        'model',
        'category',
        'description',
    ];

    /**
     * @return array<string, string>
     */
    public function availableSearchFields(): array
    {
        return [
            'id' => 'ID',
            'sku' => 'SKU',
            'product_type' => 'Tipo',
            'brand' => 'Marca',
            'model' => 'Modelo',
            'category' => 'Categoría',
            'description' => 'Descripción',
        ];
    }

    public function products(): LengthAwarePaginator
    {
        $allowed = array_keys($this->availableSearchFields());
        $activeFields = array_values(array_intersect($this->searchFields, $allowed));

        if (empty($activeFields)) {
            $activeFields = ['product_type', 'brand', 'model', 'category', 'description'];
        }

        $query = Product::query();
        $terms = array_filter(explode(' ', trim($this->search)), fn ($t) => $t !== '');

        if (! empty($terms)) {
            $concatParts = array_map(
                fn (string $field) => "ifnull(products.{$field}, '')",
                $activeFields
            );
            $concatSql = 'concat('.implode(', " ", ', $concatParts).')';

            $query->where(function ($q) use ($terms, $concatSql) {
                foreach ($terms as $term) {
                    $q->whereRaw("{$concatSql} like ?", ["%{$term}%"]);
                }
            });
        }

        return $query
            ->orderBy('id', 'desc')
            ->paginate(15);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedSearchFields(): void
    {
        $this->resetPage();
    }

    public function with(): array
    {
        return [
            'products' => $this->products(),
            'availableFields' => $this->availableSearchFields(),
        ];
    }
}; ?>

<div>
    <x-header title="Gestión de Productos" separator progress-indicator>
        <x-slot:actions>
            <div class="flex flex-col items-end gap-2">
                <div class="flex items-center gap-2">
                    <x-input placeholder="Buscar..." wire:model.live.debounce="search" icon="o-magnifying-glass" clearable class="input-sm" />
                    <x-button label="Nuevo Producto" icon="o-plus" class="btn-primary btn-sm" link="/product" />
                </div>
                <div class="flex flex-wrap items-center justify-end gap-2 text-xs">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-base-content/60">
                        Buscar en:
                    </span>
                    @foreach ($availableFields as $fieldKey => $fieldLabel)
                        <x-checkbox
                            :label="$fieldLabel"
                            wire:model.live="searchFields"
                            :value="$fieldKey"
                            wire:key="search-field-{{ $fieldKey }}"
                            class="checkbox-xs checkbox-primary text-xs"
                        />
                    @endforeach
                </div>
            </div>
        </x-slot:actions>
    </x-header>

    <x-card>
        <x-table :headers="[
            ['key' => 'id', 'label' => 'ID', 'class' => 'w-16'],
            ['key' => 'sku', 'label' => 'SKU', 'class' => 'w-28'],
            ['key' => 'image_url', 'label' => 'Imagen', 'class' => 'w-24'],
            ['key' => 'description', 'label' => 'Descripción'],
            ['key' => 'brand', 'label' => 'Marca'],
            ['key' => 'category', 'label' => 'Categoría'],
            ['key' => 'stock', 'label' => 'Stock', 'class' => 'text-right'],
        ]" :rows="$products" link="/product/{id}" with-pagination>
            @scope('cell_image_url', $product)
                <x-image-proxy :url="$product->image_url" class="w-16 h-16 object-cover rounded-lg shadow-sm" />
            @endscope

            @scope('cell_description', $product)
                <div class="flex items-center gap-2">
                    <span>{{ $product->description }}</span>
                    @if($product->isSystemProduct())
                        <x-badge value="Sistema" class="badge-warning badge-xs font-bold uppercase" />
                    @endif
                </div>
            @endscope

            @scope('cell_stock', $product)
                <span @class(['font-bold', 'text-error' => $product->stock <= 0, 'text-warning' => $product->stock > 0 && $product->stock < 10])>
                    {{ $product->stock }}
                </span>
            @endscope
        </x-table>
    </x-card>
</div>
