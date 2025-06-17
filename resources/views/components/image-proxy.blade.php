<div>
    @php
        $proxyUrl = route('proxy.image', ['url' => $url]);
    @endphp

    <img src="{{ $proxyUrl }}" loading="lazy" {{ $attributes }}>
</div>