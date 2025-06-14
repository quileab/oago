<?php

// buscador principal de productos

use Livewire\Volt\Component;
use Livewire\WithPagination;
use Livewire\WithoutUrlPagination;
use Livewire\Attributes\On;
use Mary\Traits\Toast;

new class extends Component {
    use WithPagination, WithoutUrlPagination, Toast;

    public $items = 12;
    public $featured = false;

    public function products()
    {
        // check if everything is empty then show last updated products
        if (empty(session()->get('search')) && empty(session()->get('category')) && empty(session()->get('brand')) && empty(session()->get('similar')) && empty(session()->get('tag'))) {
            $this->info('Mostrando productos actualizados recientemente', timeout: 1000);
            // return ordered byfeatured and last updated
            $filter = ['published' => 1];
            return \App\Models\Product::where('visibility', '!=', 'hidden')
                ->where($filter)
                ->where('model', '!=', 'consumo interno')
                ->orderBy('featured', 'desc')
                ->orderBy('updated_at', 'desc')
                ->paginate($this->items);
        }

        $this->info('Actualizando productos', timeout: 1000);
        $params['search'] = session()->get('search') ?: null;
        // add to search params tags session tag
        if (session()->has('tag')) {
            $params['search'] .= ' ' . session()->get('tag');
        }

        $search_multiple = explode(' ', $params['search']);
        //remove empty values
        $search_multiple = array_filter($search_multiple);

        $params['category'] = session()->get('category') ?: null;
        $params['brand'] = session()->get('brand') ?: null;
        $params['similar'] = session()->get('similar') ?: null;
        // add published=1 and description not like "CONS INT" to filter
        $filter['published'] = 1;

        if (strlen(session('search') . session('category') . session('brand') . session('similar') . session('tag')) == 0) {
            $filter['featured'] = 1;
            $this->featured = true;
        } else {
            unset($filter['featured']);
            $this->featured = false;
        }

        $products = \App\Models\Product::where('visibility', '!=', 'hidden')
            ->when(session()->has('tag'), function ($query) {
                return $query->where('tags', 'like', '%' . session()->get('tag') . '%');
            })
            ->where($filter)
            //->where('description', 'not like', 'CONS INT%')
            ->where('model', '!=', 'consumo interno')

            ->when($params['category'] ?? false, function ($query, $param) {
                return $query->where('category', $param);
            })

            ->when($params['brand'] ?? false, function ($query, $param) {
                return $query->where('brand', $param);
            })
            // advanced parts search
            ->when($params['search'] ?? false, function ($query) use ($search_multiple) {
                // use search multiple as words array just where ...
                return $query->where(function ($query) use ($search_multiple) {
                    foreach ($search_multiple as $word) {
                        $query->where(
                            DB::raw('concat(description, " ", model, " ", brand," ",product_type," ",category," ",ifnull(tags,""))'),
                            'like',
                            '%' . $word . '%'
                        );
                    }
                });
            })
            // basic search until here NEXT if for logged user
            ->when($params['similar'] ?? false, function ($query, $param) {
                return $query->where('model', $param);
            })            // when user is logged in
            ->when($user = auth()->user(), function ($query) use ($user) {
                return $query->leftJoin('list_prices', function ($join) use ($user) {
                    $join->on('products.id', '=', 'list_prices.product_id')
                        ->where('list_prices.list_id', $user->list_id); // Asociar precios de la lista del usuario
                })
                    // Seleccionar columnas de productos y el precio del usuario
                    ->select('products.*', 'list_prices.price as user_price');
            })->orderBy('description', 'asc');
        // dump($products->toSql(), $products->getBindings(), $products->get()->take(5)->toArray());
        $products = $products->paginate($this->items);
        // test set qtty to minimum qtty
        $products->map(function ($product) {
            $product->qtty = $product->qtty_package;
            return $product;
        });


        //$this->skipMount();
        return $products;
    }

    #[On('updateProducts')]
    public function with($resetPage = false)
    {
        if ($resetPage) {
            $this->resetPage();
            // do not update until the page is reset
            $resetPage = false;
        }
        return ['products' => $this->products()];
    }
}; ?>

<div class="mx-5 z-10 bg-gray-200">
    @if($featured)
        <h2 class="text-3xl font-bold my-4">Productos Destacados</h2>
    @else
        <h2 class="text-3xl font-bold my-4">Productos</h2>
    @endif
    <div wire:ignore.self class="grid grid-cols-1 md:grid-cols-3 gap-8">
        @forelse ($products as $product)
            <div>
                @php
                    // remove \n from description 
                    $product->description_html = str_replace('\n', '', $product->description_html);
                @endphp
                {{-- <livewire:web-product-card :$product wire:key="{{ $product->id }}" /> --}}
                <livewire:web-product-card :$product :key="'prod-{{ $product->id }}'.Str::random(16)" />
            </div>
        @empty
            <h1 class="text-2xl">No existen productos</h1>
        @endforelse
    </div>
    <div class="block justify-center w-full mt-2">
        {{ $products->links(data: ['scrollTo' => true]) }}
    </div>
</div>