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
    <livewire:web-navbar />

    <livewire:cart />
    {{-- // slider --}}
    @if(!session()->has('noslider'))
        <livewire:webslider />
    @endif
    <livewire:web-search-filter />
    <div class="my-4">
        @php
            $prod_id = request()->query('product_id');
        @endphp
        @if ($prod_id)
            <livewire:web-product-detail :prod_id="$prod_id" />
        @else
            <livewire:webproductsmain :filter="['published' => true]" />
        @endif
    </div>
    <x-web-footer />
    <x-toast />
</body>
<script>
    function increaseQuantity(id, qtty) {
        var inputElement = document.getElementById('qtty-' + id);
        var currentValue = parseInt(inputElement.value);
        inputElement.value = currentValue + qtty;
    }
    function decreaseQuantity(id, qtty) {
        var inputElement = document.getElementById('qtty-' + id);
        var currentValue = parseInt(inputElement.value);
        // ensure currentValue is a number and greater than qtty
        if (isNaN(currentValue) || currentValue - qtty < 1) {
            inputElement.value = 1; // Reset to 1 if current value is less than 1
            return;
        }
        inputElement.value = currentValue - qtty;
    }
    // const animationName = 'animar-rebote';
    const animationName = "cart-wiggle-animation";
    const cartIconEffect = document.getElementById("cart-highlight");
    if (cartIconEffect) {
        window.addEventListener("cart-updated", () => {
            cartIconEffect.classList.remove(animationName);
            void cartIconEffect.offsetWidth; // Forzar reflow
            cartIconEffect.classList.add(animationName);
        });
        cartIconEffect.addEventListener("animationend", () => {
            cartIconEffect.classList.remove(animationName);
        });
    }
</script>

</html>