<div class="mx-5">
    <h2 class="text-3xl font-bold my-4">{{$title}}</h2>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-8 bg-gray-200">
    @foreach ($products as $product)
    @php
    // remove \n from description 
        $product->description_html = str_replace('\n', '', $product->description_html);
    @endphp
        <livewire:web-product-card :$product />
    @endforeach
    </div>
</div>
