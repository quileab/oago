<div class="card bg-white shadow-xl overflow-hidden" wire:key="product-{{ $product->id }}">
    <div class="grid grid-cols-2">
        <!-- /public/storage/qb works in production -->
        <img class="h-32 w-auto mx-auto aspect-square" 
            src="{{ env('qb_public_assets_path','/public/storage/qb') }}/proxyImg.php?url={{ $product->image_url }}" alt="{{ $product->category }}" />
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
    <div class="p-2 bg-slate-200 grid grid-cols-3">
        
        <input type="number" class="bg-slate-100 text-black border rounded-md border-gray-900 text-center" wire:model="qtty" />

        <x-button label="Comprar" icon="o-shopping-cart" class="btn-outline text-orange-600 btn-sm"
            wire:click="buy({{$product}},false)" responsive />

        <x-button label="Similares" icon="o-magnifying-glass-circle" class="btn-outline text-blue-600 btn-sm"
            wire:click="searchSimilar({{$product}})" responsive />    
    
        {{-- <x-button label="Comprar Pack x {{ $product->qtty_package}}" icon="o-shopping-cart" class="btn-outline text-orange-600 btn-sm"
            wire:click="buy({{$product}},true)" /> --}}
    
    </div>
        <!-- if cart has products and product is in cart show cart icon -->
@if(!empty($cart) && isset($cart[$product->id]))
    <x-icon name="o-shopping-cart" label="Producto en el carrito" class="text-success" />    
@endif

    @endif
</div>