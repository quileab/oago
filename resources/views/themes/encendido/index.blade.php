<!DOCTYPE html>
<html data-theme="dark" lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ App\Helpers\SettingsHelper::settings('company_name', 'Encendido Reconquista') }} · Repuestos Eléctricos y Baterías para el Automotor</title>
    <meta name="description" content="Encendido Reconquista y CIBAT: distribución de repuestos eléctricos, inyección electrónica y baterías para el automotor. Moreno 1531 y 1541, Reconquista, Santa Fe. Más de 50 años de experiencia.">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/ScrollTrigger.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
    <link rel="icon" type="image/png" href="{{ asset('imgs/Logos/isoER50.png') }}">
</head>

<script>
    if (window.history.replaceState) {
        window.history.replaceState(null, null, window.location.href);
    }
</script>

<body class="min-h-screen font-sans antialiased bg-base-200 text-base-content selection:bg-primary selection:text-primary-content cyber-grid relative overflow-x-hidden">
    <div class="scroll-progress" id="scrollProgress"></div>

    <livewire:web-navbar />

    <livewire:cart />
    {{-- slider --}}
    @if(!session()->has('noslider'))
        <livewire:webslider />
    @endif
    <div id="catalogo" class="relative z-30">
        <livewire:web-search-filter />
    </div>
    <div class="my-6 relative z-10 max-w-7xl mx-auto px-4">
        @php
            $prod_id = request()->query('product_id');
        @endphp
        @if ($prod_id)
            <livewire:web-product-detail :prod_id="$prod_id" />
        @else
            <!-- Sliders de Productos -->
            <div class="mb-12 space-y-12">
                <livewire:web-product-slider type="featured" title="PRODUCTOS DESTACADOS" />
                <livewire:web-product-slider type="new" title="NUEVOS INGRESOS" />
            </div>
            
            <livewire:webproductsmain :filter="['published' => true]" />
        @endif
    </div>
    <x-web-footer />
    <x-toast />
</body>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        gsap.registerPlugin(ScrollTrigger);

        // GSAP Init Stagger for Product Cards has been moved to Alpine x-init in the product card component
        
        // Init Swiper
        const swipers = document.querySelectorAll('.product-swiper');
        swipers.forEach(element => {
            new Swiper(element, {
                slidesPerView: 1,
                spaceBetween: 20,
                loop: true,
                autoplay: {
                    delay: 3500,
                    disableOnInteraction: false,
                    pauseOnMouseEnter: true,
                },
                breakpoints: {
                    640: { slidesPerView: 2 },
                    768: { slidesPerView: 3 },
                    1024: { slidesPerView: 4 },
                },
            });
        });
    });

    // Global Scroll Progress Bar
    (() => {
        const bar = document.getElementById('scrollProgress');
        if (!bar) return;
        const update = () => {
            const h = document.documentElement;
            const max = h.scrollHeight - h.clientHeight;
            bar.style.width = (max > 0 ? (h.scrollTop / max) * 100 : 0) + '%';
        };
        update();
        window.addEventListener('scroll', update, { passive: true });
        window.addEventListener('resize', update);
    })();

    // High Performance GSAP Fly To Cart Effect
    function flyToCart(imgId) {
        const source = document.getElementById(imgId);
        const cart = document.getElementById('cart-highlight');
        
        if (!source || !cart) return;

        const originalImg = source.querySelector('img');
        if (!originalImg) return;

        const clone = originalImg.cloneNode(true);
        const rect = originalImg.getBoundingClientRect();
        const cartRect = cart.getBoundingClientRect();

        Object.assign(clone.style, {
            position: 'fixed',
            top: `${rect.top}px`,
            left: `${rect.left}px`,
            width: `${rect.width}px`,
            height: `${rect.height}px`,
            zIndex: '99999',
            pointerEvents: 'none',
            borderRadius: '12px',
            boxShadow: '0 0 25px rgba(239, 68, 68, 0.8)'
        });

        document.body.appendChild(clone);

        gsap.to(clone, {
            top: cartRect.top + 10,
            left: cartRect.left + 10,
            width: 28,
            height: 28,
            opacity: 0,
            rotation: 720,
            scale: 0.2,
            duration: 0.85,
            ease: "power3.inOut",
            onComplete: () => {
                clone.remove();
            }
        });
    }

    const animationName = "cart-wiggle-animation";
    const cartIconEffect = document.getElementById("cart-highlight");
    if (cartIconEffect) {
        window.addEventListener("cart-updated", () => {
            gsap.fromTo(cartIconEffect, 
                { scale: 0.8, rotation: -15 }, 
                { scale: 1.25, rotation: 15, duration: 0.2, yoyo: true, repeat: 3, ease: "sine.inOut", onComplete: () => {
                    gsap.to(cartIconEffect, { scale: 1, rotation: 0, duration: 0.2 });
                }}
            );
        });
    }
</script>

</html>