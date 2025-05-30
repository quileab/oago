<!DOCTYPE html>
<html data-theme="dark" lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script type="text/javascript" src="{{ asset('js/vanilla-slider.min.js') }}"></script>
</head>
<!-- Charly -->
<!-- force page refresh -->
<script>
    if (window.history.replaceState) {
        window.history.replaceState(null, null, window.location.href);
    }
</script>

<body class="min-h-screen font-sans antialiased bg-gray-200 text-gray-900">
    <x-web-navbar />

    <livewire:cart />
    {{-- // slider --}}
    <livewire:webslider />
    <div class="sticky top-0 z-10">
        <livewire:web-search-filter />
    </div>
    <div class="my-4">
        {{-- @if(!(session()->has('search') || !session()->has('category') || !session()->has('brand')))
        <livewire:web-product title="Productos Destacados" :items=3
            :filter="['featured' => true, 'published' => true]" />
        @endif --}}
        {{-- srch{{ session()->has('search') }}cat{{ session()->has('category') }}br{{ session()->has('brand') }} --}}
        <livewire:webproductsmain :filter="['published' => true]" />
    </div>
    <x-web-footer />
    {{-- <p class="bg-gray-200 text-gray-900 h-5">{{ Auth::user() ? Auth::user()->name : 'Nada' }}</p>
    <p class="bg-gray-200 text-gray-900 h-5">
        {{ Auth::guard('guest_user') ? Auth::guard('guest_user')->user()->name : 'Nada' }}</p> --}}
    <x-toast />
</body>
<script>
    var containerId = 'slider';

    var options = {
        transitionTime: 500,
        transitionZoom: 'in',
        bullets: true,
        arrows: true,
        arrowsHide: true,
        auto: true,
        autoTime: 4000,
    }
    // Correct: Using native JavaScript DOMContentLoaded for better performance
    document.addEventListener('DOMContentLoaded', function () {
        var slider = createSlider(containerId, options);
        document.getElementById(containerId).style.height = 'auto';
    });

</script>

</html>