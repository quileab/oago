<?php
use App\Models\Product;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Illuminate\Pagination\LengthAwarePaginator;
use Mary\Traits\Toast;

new class extends Component {
    use Toast;
    use WithPagination;

    public string $search = '';

    public bool $drawer = false;

    public array $sortBy = ['column' => 'id', 'direction' => 'asc'];

    public $priceLists = [];
    public $priceList;

    public function mount()
    {
        $this->hydrate();
    }

    public function hydrate()
    {
        // get unique price lists from list_id
        $this->priceLists = Cache::remember('priceLists', 60 * 60, function () {
            return \App\Models\ListPrice::select('list_id')->distinct()->get();
        }) ?? \App\Models\ListPrice::select('list_id')->distinct()->get();
        // if not set, set first price list
        $this->priceList = $this->priceList ?? $this->priceLists->first()->list_id;
    }

    // Clear filters
    // public function clear(): void
    // {
    //     $this->reset();
    //     $this->resetPage(); 
    //     $this->success('Filters cleared.', position: 'toast-bottom');
    // }

    // Delete action
    public function delete($id): void
    {
        Product::destroy($id);
        $this->success('Product deleted.', position: 'toast-bottom');
        //$this->warning("Will delete #$id", 'It is fake.', position: 'toast-bottom');
    }

    // Table headers
    public function headers(): array
    {
        return [
            ['key' => 'id', 'label' => '#', 'class' => 'w-1'],
            ['key' => 'brand', 'label' => 'Marca'],
            ['key' => 'model', 'label' => 'Modelo', 'sortable' => false],
            ['key' => 'description', 'label' => 'Descripción'],
            ['key' => 'price', 'label' => 'Precio', 'class' => 'text-right'],
            ['key' => 'offer_price', 'label' => 'Precio Oferta', 'class' => 'text-right'],
            ['key' => 'list_price', 'label' => 'Precio Lista', 'class' => 'text-right'],
        ];
    }

    public function products(): LengthAwarePaginator //Collection
    {
        $this->drawer = false;
        $list_price = $this->priceList;

        return Product::join('list_prices as listprice', 'listprice.product_id', '=', 'products.id')
            ->select('products.*', 'listprice.price as list_price')
            ->where('listprice.list_id', $list_price)
            ->when($this->search, fn($q) => $q->where(DB::raw('concat(brand," ",ifnull(model,"")," ",description)'), 'like', "%$this->search%"))
            ->orderBy(...array_values($this->sortBy))
            ->paginate(20);
    }

    public function with(): array
    {
        return [
            'products' => $this->products(),
            'headers' => $this->headers()
        ];
    }

    // Reset pagination when any component property changes
    public function updated($property): void
    {
        if (!is_array($property) && $property != "") {
            // $this->resetPage();
            $this->goToPage(1);
            $this->success('Page reset.', position: 'toast-bottom');
        }
    }
}; ?>

<div>
    <!-- HEADER -->
    <x-header title="Productos » Lista {{ $priceList }}" separator progress-indicator>
        <x-slot:middle class="!justify-end">
            <x-input placeholder="Search..." wire:model.live.debounce="search" clearable icon="o-magnifying-glass" />
        </x-slot:middle>
        <x-slot:actions>
            <x-button label="Filtros" @click="$wire.drawer = true" responsive icon="o-funnel" />
        </x-slot:actions>
    </x-header>

    <!-- TABLE  -->
    <x-table :headers="$headers" :rows="$products" :sort-by="$sortBy" with-pagination :key="'products-'.$priceList" />

    <!-- FILTER DRAWER -->
    <x-drawer wire:model="drawer" title="Filtros" right separator with-close-button class="lg:w-1/3">
        Listas de Precios
        <x-select wire:model="priceList" :options="$priceLists" option-label="list_id" option-value="list_id" />

        <x-slot:actions>
            <x-button label="Aplicar" icon="o-check" class="btn-primary" wire:click="products" spinner />
        </x-slot:actions>
    </x-drawer>
</div>