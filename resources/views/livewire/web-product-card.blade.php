<div class="card bg-white shadow-md shadow-slate-400 overflow-hidden">
    <div class="grid grid-cols-2">
        <!-- /public/storage/qb works in production -->
        <div>
            {{-- if product is featured show description above image --}}
            @if($product->featured)
                <h2 class="text-xs text-center text-white bg-red-700">
                    PRODUCTO DESTACADO ⭐
                </h2>
            @endif
        <img class="h-32 w-auto mx-auto aspect-square m-2"
            src="{{ env('qb_public_assets_path', '/public/storage/qb') }}/proxyImg.php?url={{ $product->image_url }}"
            alt="{{ $product->category }}" />

        </div>
        {{-- // if product is featured show description above image --}}
        <div class="p-2 bg-white">
            <h2 class="text-2xl">{{ $product->brand }}</h2>
            <p>{{ $product->description }}</p>
        </div>
    </div>
    @if(Auth::guest())
        <div class="p-2 bg-slate-100 text-center text-sm">
            Regístrese para ver precios o realizar compras
        </div>

    @else
        <div class="p-2 bg-white grid grid-cols-2">
            <div>
                <h3 @class([
                    "text-2xl text-center font-bold text-green-700",
                    "text-xl line-through" =>
                        $product->offer_price > 0
                ])>$ {{ number_format($product->user_price, 2, ',', '.') }}
                </h3>
                @if($product->qtty_package > 1)
                <p class="text-xs text-center font-bold text-green-800">$
                    {{ number_format($product->user_price / $product->qtty_package, 2, ',', '.') }} p/un.</p>
                @endif
                        @if($product->offer_price > 0)
                            <h3 class="text-2xl text-center font-bold text-green-700">$
                                {{number_format($product->offer_price, 2, ',', '.')}}
                            </h3>
                        @endif
            </div>
            <div>
                <p>{!! $product->description_html !!}</p>
                <p class="text-xs text-right">Cod. {{ $product->id }}</p>
                <!-- Stock less than 10 show icon in red, 11 to 100 in yellow, more than 100 in green -->
                <p>
                    <x-icon name="o-cube" label="x {{ $product->qtty_package }} " class="text-gray-600 h-6" />
                    @if($product->stock < 10)
                        <x-icon name="s-battery-0" label="Stock Bajo" class="text-red-600 h-6" />
                    @elseif($product->stock < 100)
                        <x-icon name="s-battery-50" label="Stock Medio" class="text-yellow-600 h-6" />
                    @else
                        <x-icon name="s-battery-100" label="En stock" class="text-green-600 h-6" />
                    @endif
                </p>

            </div>
        </div>
        <div class="p-2 bg-slate-200 grid grid-cols-3 gap-2">

            <input id="qtty-{{ $product->id }}" wire:key="qtty-$product->id" type="number" min="1"
                class="bg-slate-100 text-black border rounded-md border-gray-900 text-center" wire:model="qtty" / gap-2>

            <x-button label="Comprar" icon="o-shopping-cart" class="btn-outline text-orange-600 btn-sm border-2 hover:bg-orange-600 hover:text-white"
                wire:click="buy({{$product}},false)" responsive />

            <x-button label="Similares" icon="o-magnifying-glass-circle" class="btn-outline text-orange-600 btn-sm border-2 hover:bg-orange-600 hover:text-white"
                wire:click="searchSimilar({{$product}})" responsive />

            {{-- <x-button label="Comprar Pack x {{ $product->qtty_package}}" icon="o-shopping-cart"
                class="btn-outline text-orange-600 btn-sm" wire:click="buy({{$product}},true)" /> --}}

        </div>
        <!-- if cart has products and product is in cart show cart icon -->
        @if(!empty($cart) && isset($cart[$product->id]))
            <x-icon name="o-shopping-cart" label="Producto en el carrito" class="text-success" />
        @endif

    @endif
</div>