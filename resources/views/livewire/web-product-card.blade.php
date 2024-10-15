<div>
    <div class="card bg-white shadow-xl overflow-hidden">
        <div class="grid grid-cols-2">
            <img class="h-32 w-auto mx-auto aspect-square" src="{{ $product->image_url }}" alt="{{ $product->category }}" />
            <div class="p-2 bg-slate-100">
            <h2 class="text-2xl">{{ $product->brand }}</h2>
            <p>{{ $product->description }}</p>
            </div>
        </div>
        <div class="p-2 bg-slate-100 grid grid-cols-2">
            <div>
                <h3 @class([
                    "text-2xl text-center font-bold text-green-700",
                    "text-xl line-through" => $product->offer_price>0
                ])>$ {{ number_format($product->price, 2, ',', '.') }}</h3>
                @if($product->offer_price>0)
                    <h3 class="text-2xl text-center font-bold text-green-700">$ {{number_format($product->offer_price, 2, ',', '.')}}</h3>
                @endif
            </div>
            <div><p>{!! $product->description_html !!}</p></div>
        </div>
        <div class="p-2 bg-slate-100">
            
           <x-icon name="o-shopping-cart" class="w-10 h-10 bg-orange-500 text-white p-2 rounded-full" /> Comprar x Unidad
           <x-icon name="o-cube" class="w-10 h-10 bg-orange-500 text-white p-2 rounded-full" /> Comprar! x Pack ({{ $product->qtty_package}})

        </div>
    </div>
</div>
