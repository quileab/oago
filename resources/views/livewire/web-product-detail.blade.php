<?php

use Livewire\Volt\Component;
use \App\Models\Product;

new class extends Component {
    public $product;
    public $qtty = 1;
    public $related_products = [];

    public function mount(Product $prod_id)
    {
        // use ProductSearchService to get product
        $this->product = app(\App\Services\ProductSearchService::class)
            ->searchProducts(
                ['id' => $prod_id->id],
                1
            );

        // use ProductSearchService to get related products
        $this->related_products = app(\App\Services\ProductSearchService::class)
            ->searchRelatedProducts(
                $this->product,
                6
            );

        //dd($this->product, $this->related_products);

        //dd($this->related_products->get());
        // Product::where([
        //     'published' => true,
        //     'product_type' => $prod_id->product_type
        // ])
        //     ->where('visibility', '!=', 'hidden')
        //     ->where('id', '!=', $prod_id->id)
        //     // get 6 random products
        //     ->inRandomOrder()->limit(6)
        //     ->get();
    }
}; ?>

<div>
    <div class="grid grid-cols-1 md:grid-cols-2">
        <x-image-proxy url="{{ $product->image_url }}" class="w-full h-auto" />

        {{-- // if product is featured show description above image --}}
        <div class="p-2 bg-white html-desc">
            <div class="w-full">
                @if($product->featured)
                    <h2 class="text-xs text-center text-white bg-red-700">
                        PRODUCTO DESTACADO ⭐
                    </h2>
                @endif
                {{-- split tags by | --}}
                @foreach (array_filter(explode('|', $product->tags)) as $tag)
                    <x-badge value="{{ $tag }}" class="badge-warning" />
                @endforeach

            </div>
            <h2 class="text-2xl">{{ $product->brand }}</h2>
            <p>{{ $product->description }}</p>
            {!! $product->description_html !!}

            @if(Auth::guest())
                <div class="p-2 bg-slate-100 text-center text-sm">
                    Regístrese para ver precios o realizar compras
                </div>
            @else
                <div class="p-2 bg-white grid grid-cols-2">
                    <div class="flex flex-col justify-center">
                        <h3 @class([
                            "text-2xl text-center font-bold text-green-700",
                            "text-xl line-through" =>
                                $product->offer_price > 0
                        ])>$ {{ number_format($product->user_price, 2, ',', '.') }}
                        </h3>
                        @if($product->qtty_unit > 1)
                            <p class="text-xs text-center font-bold text-green-800">$
                                {{ number_format($product->user_price / $product->qtty_unit, 2, ',', '.') }} p/un.
                            </p>
                        @endif
                        @if($product->offer_price > 0)
                            <h3 class="text-2xl text-center font-bold text-green-700">$
                                {{number_format($product->offer_price, 2, ',', '.')}}
                            </h3>
                        @endif
                    </div>
                    <div>
                        <div class="text-xs text-right">Cod. {{ $product->id }}<br>
                            @if($product->stock < 10)
                                <x-icon name="s-battery-0" label="Stock Bajo" class="text-red-600 text-md h-4" />
                            @elseif($product->stock < 100)
                                <x-icon name="s-battery-50" label="Stock Medio" class="text-yellow-600 text-md h-4" />
                            @else
                                <x-icon name="s-battery-100" label="En stock" class="text-green-600 text-md h-4" />
                            @endif
                            <br>
                            <x-icon name="o-cube" label="{{ $product->qtty_package }} " class="text-gray-600 text-md h-4" />
                        </div>

                    </div>
                </div>
                <div class="p-2 bg-slate-200 grid grid-cols-1 gap-2">
                    @if($product->qtty_package > 1)
                        <p class="text-xs"><small>Algunos productos se venden por bulto y no por unidades.
                            </small>
                        </p>
                    @endif
                    <div class="flex gap-0">
                        <button class="btn bg-red-600 border-2 hover:bg-red-500 hover:text-white grow-1"
                            onclick="decreaseQuantity({{ $product->id }}, 1)">
                            -1</button>
                        @if($product->qtty_package > 1)
                            <button class="btn bg-red-600 border-2 hover:bg-red-500 hover:text-white grow-1"
                                onclick="decreaseQuantity('{{$product->id}}', {{ $product->qtty_package }})">
                                -{{ $product->qtty_package }}</button>
                        @endif
                        <input id="qtty-{{ $product->id }}" wire:key="{{ $product->id }}" type="number" wire:model="qtty"
                            min="1" step="1"
                            class="bg-slate-100 text-black border rounded-md border-gray-900 text-center w-16">
                        <button class="btn bg-red-600 border-2 hover:bg-red-500 hover:text-white grow-1"
                            onclick="document.getElementById('qtty-{{ $product->id }}').value = parseInt(document.getElementById('qtty-{{ $product->id }}').value)+1">+1</button>
                        @if($product->qtty_package > 1)
                            <button class="btn bg-red-600 border-2 hover:bg-red-500 hover:text-white grow-1"
                                onclick="increaseQuantity('{{$product->id}}', {{ $product->qtty_package }})">
                                +{{ $product->qtty_package }}</button>
                        @endif
                    </div>


                    <div class="grid grid-cols-2 gap-2">
                        <button class="btn btn-outline text-red-600 border-2 hover:bg-red-600 hover:text-white"
                            onclick="Livewire.dispatch('addToCart', {'product': {{ $product }}, 'quantity':
                                                                                                                                                                                                                                                                                                                document.getElementById('qtty-{{ $product->id }}').value})">
                            <x-icon name="o-shopping-cart" label="AGREGAR" />
                        </button>
                    </div>
                </div>
                <!-- if cart has products and product is in cart show cart icon -->
                @if(!empty($cart) && isset($cart[$product->id]))
                    <x-icon name="o-shopping-cart" label="Producto en el carrito" class="text-success" />
                @endif


            @endif
        </div>
    </div>
    <h1 class="text-2xl font-bold m-4 px-2">Relacionados</h1>
    <div class="p-4 bg-slate-200 grid grid-cols-3 gap-4">
        @foreach ($related_products as $product)
            <livewire:web-product-card :$product />
        @endforeach
    </div>


</div>