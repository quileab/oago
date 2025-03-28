<?php

use Livewire\Volt\Component;
use Livewire\WithPagination;
use Livewire\WithoutUrlPagination;
use Livewire\Attributes\On;
use Mary\Traits\Toast;

new class extends Component {
    use WithPagination, WithoutUrlPagination, Toast;

    public $items = 12;

    public function products()
    {
        $this->info('Actualizando productos');
        $this->resetPage(); // vuelve a pagina 1
        $params['search'] = session()->get('search') ?: null;
        $params['category'] = session()->get('category') ?: null;
        $params['brand'] = session()->get('brand') ?: null;
        // add published=1 and description not like "CONS INT" to filter
        $filter['published'] = 1;

        $products = \App\Models\Product::where($filter)
            // ->where('published', 1)
            ->where('description', 'not like', 'CONS INT%')
            ->where('model', '!=', 'consumo interno')


            ->when($params['category'] ?? false, function ($query, $param) {
                return $query->where('category', $param);
            })

            ->when($params['brand'] ?? false, function ($query, $param) {
                return $query->where('brand', $param);
            })

            ->when($params['search'] ?? false, function ($query, $param) {
                return $query->where('description', 'like', '%' . $param . '%')
                    ->orWhere('model', 'like', '%' . $param . '%');
            })
            ->when($this->filter['similar'] ?? false, function ($query) {
                return $query->where('model', $this->filter['model']);
            })
            // when user is logged in
            ->when($user = auth()->user(), function ($query) use ($user) {
                return $query->leftJoin('list_prices', function ($join) use ($user) {
                    $join->on('products.id', '=', 'list_prices.product_id')
                        ->where('list_prices.list_id', $user->list_id); // Asociar precios de la lista del usuario
                })
                    // Seleccionar columnas de productos y el precio del usuario
                    ->select('products.*', 'list_prices.price as user_price');
            });
        //dump($products->toSql(), $products->getBindings());
        $products = $products->paginate($this->items);
        //$this->skipMount();
        return $products;
    }

    #[On('updateProducts')]
    public function with()
    {
        return ['products' => $this->products()];
    }
}; ?>

<div class="mx-5">
    {{-- <h2 class="text-3xl font-bold my-4">{{$title}}</h2> --}}
    <div wire:ignore.self class="grid grid-cols-1 md:grid-cols-3 gap-8">
        @forelse ($products as $product)
                <div>
                    @php
                        // remove \n from description 
                        $product->description_html = str_replace('\n', '', $product->description_html);
                    @endphp
                    <livewire:web-product-card :$product wire:key="{{ $loop->index }}" />
                </div>
        @empty
            <h1 class="text-2xl">No existen productos</h1>
        @endforelse
        <div class="block justify-center w-full">
            {{ $products->links(data: ['scrollTo' => false]) }}
        </div>
    </div>
</div>