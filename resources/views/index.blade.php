<!DOCTYPE html>
<html data-theme="dark" lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
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
    function flyToCart(imgId) {
        const source = document.getElementById(imgId);
        const cart = document.getElementById('cart-highlight');
        
        if (!source || !cart) return;

        // Clonar la imagen dentro del contenedor
        const originalImg = source.querySelector('img');
        if (!originalImg) return;

        const clone = originalImg.cloneNode(true);
        const rect = originalImg.getBoundingClientRect();
        const cartRect = cart.getBoundingClientRect();

        // Estilos iniciales del clon
        Object.assign(clone.style, {
            position: 'fixed',
            top: `${rect.top}px`,
            left: `${rect.left}px`,
            width: `${rect.width}px`,
            height: `${rect.height}px`,
            zIndex: '9999',
            transition: 'all 0.8s cubic-bezier(0.42, 0, 0.58, 1)',
            pointerEvents: 'none',
            opacity: '0.8'
        });

        document.body.appendChild(clone);

        // Disparar animación en el siguiente frame
        requestAnimationFrame(() => {
            Object.assign(clone.style, {
                top: `${cartRect.top + 10}px`,
                left: `${cartRect.left + 10}px`,
                width: '20px',
                height: '20px',
                opacity: '0.2',
                transform: 'rotate(360deg)'
            });
        });

        // Limpiar
        clone.addEventListener('transitionend', () => {
            clone.remove();
        });
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