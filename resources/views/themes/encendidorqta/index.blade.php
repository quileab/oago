<!DOCTYPE html>
<html data-theme="dark" lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="icon" type="image/webp" href="{{ asset('imgs/fallback.webp') }}">
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
    @if(!request()->has('store'))
        {{-- // slider --}}
        @if(!session()->has('noslider'))
            <livewire:webslider />
        @endif

        {{-- Paymethods Banner --}}
        <div class="max-w-7xl mx-auto px-4 mt-2 mb-6 z-10 relative">
            <div class="bg-white rounded-lg shadow-md py-4 px-6 flex flex-wrap justify-center items-center gap-4 md:gap-8">
                <img src="{{ asset('img/medios-de-pago/visa.png') }}" alt="Visa" class="h-6 md:h-8 object-contain">
                <img src="{{ asset('img/medios-de-pago/cabal.jpg') }}" alt="Cabal" class="h-6 md:h-8 object-contain">
                <img src="{{ asset('img/medios-de-pago/mercado_pago.webp') }}" alt="Mercado Pago" class="h-6 md:h-8 object-contain">
                <img src="{{ asset('img/medios-de-pago/modo.png') }}" alt="Modo" class="h-6 md:h-8 object-contain">
                <img src="{{ asset('img/medios-de-pago/mastercard.svg') }}" alt="Mastercard" class="h-6 md:h-8 object-contain">
                <img src="{{ asset('img/medios-de-pago/naranjax.webp') }}" alt="Naranja X" class="h-6 md:h-8 object-contain">
            </div>
        </div>
    @endif

    <livewire:web-search-filter />

    @if (!request()->query('product_id') && !request()->has('store'))
        <!-- Contenedor general -->
        <div class="relative w-full mb-10">
            <!-- Slider (z-20 para que quede arriba) -->
            <div class="relative z-20">
                <livewire:web-product-slider type="featured" title="PRODUCTO DESTACADOS" />
            </div>

            <!-- Sección de la foto (fondo) con aspect ratio real de la imagen -->
            <div class="relative w-full bg-cover bg-top -mt-56 md:-mt-72" style="background-image: url('{{ asset('img/frentelocal.png') }}'); aspect-ratio: 1919/899;">
                <!-- Overlay oscuro y en escala de grises -->
                <div class="absolute inset-0 bg-black/30 backdrop-grayscale"></div>
                
                <!-- Contenido de los hexágonos posicionado al pie de la foto -->
                <div class="absolute bottom-12 md:bottom-[10%] left-0 right-0 z-10 max-w-7xl mx-auto px-6">
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-6 text-white text-left">
                        <!-- Stat 1 -->
                        <div class="flex items-center gap-3 md:gap-4">
                            <svg viewBox="0 0 24 24" fill="currentColor" class="w-12 h-12 md:w-16 md:h-16 text-white shrink-0 drop-shadow-[0_3px_6px_rgba(0,0,0,0.8)]">
                                <path fill-rule="evenodd" clip-rule="evenodd" d="M12 2l9 5v10l-9 5-9-5V7l9-5z M11 8h2v3h3v2h-3v3h-2v-3H8v-2h3V8z" />
                            </svg>
                            <div class="leading-tight">
                                <div class="text-xl md:text-3xl font-black italic tracking-tighter drop-shadow-md">50 AÑOS</div>
                                <div class="text-[10px] md:text-sm font-light uppercase tracking-widest mt-0.5 drop-shadow-md">DE HISTORIA</div>
                            </div>
                        </div>
                        <!-- Stat 2 -->
                        <div class="flex items-center gap-3 md:gap-4">
                            <svg viewBox="0 0 24 24" fill="currentColor" class="w-12 h-12 md:w-16 md:h-16 text-white shrink-0 drop-shadow-[0_3px_6px_rgba(0,0,0,0.8)]">
                                <path fill-rule="evenodd" clip-rule="evenodd" d="M12 2l9 5v10l-9 5-9-5V7l9-5z M11 8h2v3h3v2h-3v3h-2v-3H8v-2h3V8z" />
                            </svg>
                            <div class="leading-tight">
                                <div class="text-xl md:text-3xl font-black italic tracking-tighter drop-shadow-md">25.000</div>
                                <div class="text-[10px] md:text-sm font-light uppercase tracking-widest mt-0.5 drop-shadow-md">PRODUCTOS</div>
                            </div>
                        </div>
                        <!-- Stat 3 -->
                        <div class="flex items-center gap-3 md:gap-4">
                            <svg viewBox="0 0 24 24" fill="currentColor" class="w-12 h-12 md:w-16 md:h-16 text-white shrink-0 drop-shadow-[0_3px_6px_rgba(0,0,0,0.8)]">
                                <path fill-rule="evenodd" clip-rule="evenodd" d="M12 2l9 5v10l-9 5-9-5V7l9-5z M11 8h2v3h3v2h-3v3h-2v-3H8v-2h3V8z" />
                            </svg>
                            <div class="leading-tight">
                                <div class="text-xl md:text-3xl font-black italic tracking-tighter drop-shadow-md">100</div>
                                <div class="text-[10px] md:text-sm font-light uppercase tracking-widest mt-0.5 drop-shadow-md">MARCAS</div>
                            </div>
                        </div>
                        <!-- Stat 4 -->
                        <div class="flex items-center gap-3 md:gap-4">
                            <svg viewBox="0 0 24 24" fill="currentColor" class="w-12 h-12 md:w-16 md:h-16 text-white shrink-0 drop-shadow-[0_3px_6px_rgba(0,0,0,0.8)]">
                                <path d="M12 2l9 5v10l-9 5-9-5V7l9-5z" />
                            </svg>
                            <div class="leading-tight">
                                <div class="text-xl md:text-3xl font-black italic tracking-tighter drop-shadow-md">3 UNIDADES</div>
                                <div class="text-[10px] md:text-sm font-light uppercase tracking-widest mt-0.5 drop-shadow-md">DE NEGOCIO</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

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