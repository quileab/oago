<div class="card bg-white shadow-xl overflow-hidden" wire:key="product-{{ $product->id }}">
    <div class="grid grid-cols-2">
        <img class="h-32 w-auto mx-auto aspect-square" src="{{ $product->image_url }}" alt="{{ $product->category }}" />
        <div class="p-2 bg-slate-100">
            <h2 class="text-2xl">{{ $product->brand }}</h2>
            <p>{{ $product->description }}</p>
        </div>
    </div>
    @if(Auth::guest())
    <div class="p-2 bg-slate-100 text-center text-sm">
        Regístrese para ver precios o realizar compras
    </div>

    @else
    <div class="p-2 bg-slate-100 grid grid-cols-2">
        <div>
            <h3 @class([ "text-2xl text-center font-bold text-green-700" , "text-xl line-through"=>
                $product->offer_price>0
                ])>$ {{ number_format($product->user_price, 2, ',', '.') }}
            </h3>
            @if($product->offer_price>0)
            <h3 class="text-2xl text-center font-bold text-green-700">$ {{number_format($product->offer_price, 2, ',','.')}}</h3>
            @endif
        </div>
        <div>
            <p>{!! $product->description_html !!}</p>
            <p class="text-xs text-right">Cod. {{ $product->id }}</p>
        </div>
    </div>
    <div class="p-2 bg-slate-100 grid grid-cols-2">
        <p>
            <x-button label="Comprar" icon="o-shopping-cart" class="btn-outline text-orange-600 btn-sm"
                wire:click="buy({{$product}},false)" />
        </p>
        <p>
            <x-button label="Comprar Pack x {{ $product->qtty_package}}" icon="o-shopping-cart" class="btn-outline text-orange-600 btn-sm"
                wire:click="buy({{$product}},true)" />
        </p>
    </div>
    @endif
</div>