<div wire:ignore class="card bg-white shadow-md shadow-slate-400 overflow-hidden" skip="true">
    <a href="./?product_id={{ $product->id }}">
        <div class="grid grid-cols-2">
            <!-- /public/storage/qb works in production -->
            <div>
                {{-- if product is featured show description above image --}}
                @if($product->featured)
                    <h2 class="text-xs text-center text-white bg-red-700 rounded-br-lg">
                        PRODUCTO DESTACADO ⭐
                    </h2>
                @endif

                <x-image-proxy url="{{ $product->image_url }}"
                    class="h-32 w-auto mx-auto object-cover {{ $product->stock == 0 ? 'opacity-50' : '' }}" />

            </div>
            {{-- // if product is featured show description above image --}}
            <div class="p-2 bg-white html-desc">
                {{-- <h2 class="text-2xl">{{ $product->brand }}</h2> --}}
                <div class="w-full">
                    {{-- split tags by | --}}
                    @foreach (array_filter(explode('|', $product->tags)) as $tag)
                        <x-badge value="{{ $tag }}" class="badge-warning" />
                    @endforeach

                </div>
                <p class="text-lg font-bold">{{ $product->description }}
                    <span class="text-sm font-normal text-gray-800">({{ $product->brand }})</span>
                </p>
                {!! $product->description_html !!}
            </div>
        </div>
    </a>
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
                        <x-icon name="s-battery-0" label="Stock Bajo" class="text-red-600 text-sm h-4" />
                    @elseif($product->stock < 100)
                        <x-icon name="s-battery-50" label="Stock Medio" class="text-yellow-600 text-sm h-4" />
                    @else
                        <x-icon name="s-battery-100" label="En stock" class="text-green-600 text-sm h-4" />
                    @endif
                    <br>
                    <x-icon name="o-cube" label="{{ $product->qtty_package }} " class="text-gray-600 text-sm h-4" />
                </div>

            </div>
        </div>
        <div class="p-2 bg-slate-200 grid grid-cols-1 gap-2">
            @if($product->qtty_package > 1)
                <p class="text-xs"><small>Algunos productos se venden por bulto y no por unidades.
                    </small>
                </p>
            @endif
            @if($product->stock >= 0 && !in_array(Auth::user()->role->value, ['none', 'guest']))
                <div class="flex gap-0">
                    <button class="btn bg-red-600 border-2 hover:bg-red-500 hover:text-white grow-1"
                        onclick="decreaseQuantity({{ $product->id }}, 1)">
                        -1</button>
                    @if($product->qtty_package > 1)
                        <button class="btn bg-red-600 border-2 hover:bg-red-500 hover:text-white grow-1"
                            onclick="decreaseQuantity('{{$product->id}}', {{ $product->qtty_package }})">
                            -{{ $product->qtty_package }}</button>
                    @endif
                    <input id="qtty-{{ $product->id }}" wire:key="{{ $product->id }}" type="number" wire:model="qtty" min="1"
                        step="1" class="bg-slate-100 text-black border rounded-md border-gray-900 text-center w-16">
                    <button class="btn bg-red-600 border-2 hover:bg-red-500 hover:text-white grow-1"
                        onclick="document.getElementById('qtty-{{ $product->id }}').value = parseInt(document.getElementById('qtty-{{ $product->id }}').value)+1">+1</button>
                    @if($product->qtty_package > 1)
                        <button class="btn bg-red-600 border-2 hover:bg-red-500 hover:text-white grow-1"
                            onclick="increaseQuantity('{{$product->id}}', {{ $product->qtty_package }})">
                            +{{ $product->qtty_package }}</button>
                    @endif
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <x-button label="Similares" icon="o-magnifying-glass-circle"
                        class="btn-outline text-orange-600 border-2 hover:bg-orange-600 hover:text-white"
                        wire:click="searchSimilar({{$product}})" responsive />
                    <button class="btn btn-outline text-red-600 border-2 hover:bg-red-600 hover:text-white"
                        onclick="Livewire.dispatch('addToCart', {'product': {{ $product }}, 'quantity':
                                                                                                                                                                                                                                                document.getElementById('qtty-{{ $product->id }}').value})">
                        <x-icon name="o-shopping-cart" label="AGREGAR" />
                    </button>
                </div>
            @endif
        </div>
        <!-- if cart has products and product is in cart show cart icon -->
        @if(!empty($cart) && isset($cart[$product->id]))
            <x-icon name="o-shopping-cart" label="Producto en el carrito" class="text-success" />
        @endif

    @endif
</div>