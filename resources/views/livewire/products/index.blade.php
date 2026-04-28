<?php

use App\Models\ListName;
use App\Models\ListPrice;
use App\Models\Product;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Mary\Traits\Toast;

new class extends Component {
    use WithPagination, WithFileUploads, Toast;

    public string $search = '';

    public function products(): LengthAwarePaginator
    {
        return Product::query()
            ->where(function ($q) {
                $q->where('description', 'like', "%{$this->search}%")
                    ->orWhere('brand', 'like', "%{$this->search}%")
                    ->orWhere('id', 'like', "%{$this->search}%");
            })
            ->orderBy('id', 'desc')
            ->paginate(15);
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function with(): array
    {
        return [
            'products' => $this->products(),
        ];
    }
}; ?>

<div>
    <x-header title="Gestión de Productos" separator progress-indicator>
        <x-slot:actions>
            <x-input placeholder="Buscar descripción, marca o ID..." wire:model.live.debounce="search" icon="o-magnifying-glass" clearable />
            <x-button label="Nuevo Producto" icon="o-plus" class="btn-primary" link="/product" />
        </x-slot:actions>
    </x-header>

    <x-card>
        <x-table :headers="[
            ['key' => 'id', 'label' => 'ID', 'class' => 'w-16'],
            ['key' => 'image_url', 'label' => 'Imagen', 'class' => 'w-24'],
            ['key' => 'description', 'label' => 'Descripción'],
            ['key' => 'brand', 'label' => 'Marca'],
            ['key' => 'category', 'label' => 'Categoría'],
            ['key' => 'stock', 'label' => 'Stock', 'class' => 'text-right'],
        ]" :rows="$products" link="/product/{id}" with-pagination>
            @scope('cell_image_url', $product)
                <x-image-proxy :url="$product->image_url" class="w-16 h-16 object-cover rounded-lg shadow-sm" />
            @endscope

            @scope('cell_stock', $product)
                <span @class(['font-bold', 'text-error' => $product->stock <= 0, 'text-warning' => $product->stock > 0 && $product->stock < 10])>
                    {{ $product->stock }}
                </span>
            @endscope
        </x-table>
    </x-card>
</div>
