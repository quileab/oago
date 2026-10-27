<!DOCTYPE html>
<html data-theme="light" lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('themes.' . config('app.theme', 'default') . '.partials.favicon')
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
                <img src="{{ asset('themes/encendidorqta/images/medios-de-pago/visa.png') }}" alt="Visa" class="h-6 md:h-8 object-contain">
                <img src="{{ asset('themes/encendidorqta/images/medios-de-pago/cabal.jpg') }}" alt="Cabal" class="h-6 md:h-8 object-contain">
                <img src="{{ asset('themes/encendidorqta/images/medios-de-pago/mercado_pago.webp') }}" alt="Mercado Pago" class="h-6 md:h-8 object-contain">
                <img src="{{ asset('themes/encendidorqta/images/medios-de-pago/modo.png') }}" alt="Modo" class="h-6 md:h-8 object-contain">
                <img src="{{ asset('themes/encendidorqta/images/medios-de-pago/mastercard.svg') }}" alt="Mastercard" class="h-6 md:h-8 object-contain">
                <img src="{{ asset('themes/encendidorqta/images/medios-de-pago/naranjax.webp') }}" alt="Naranja X" class="h-6 md:h-8 object-contain">
            </div>
        </div>
    @endif

    <livewire:web-search-filter />

    @if (!request()->query('product_id') && !request()->has('store'))
        @php
            $activeFilters = [
                'search' => (string) request()->query('search', ''),
                'category' => (string) request()->query('category', ''),
                'brand' => (string) request()->query('brand', ''),
                'tag' => (string) request()->query('tag', ''),
                'similar' => (string) request()->query('similar', ''),
            ];
            $hasActiveFilters = collect($activeFilters)->contains(fn ($value) => $value !== '');
        @endphp
        <!-- Contenedor general: se oculta mientras hay búsqueda/filtros para que los resultados queden arriba -->
        <div class="relative w-full mb-10"
            x-data="{
                filters: {{ Js::from($activeFilters) }},
                get filtered() { return Object.values(this.filters).some(Boolean); },
                onUpdateProducts(detail) {
                    const incoming = detail?.filters ?? detail ?? {};
                    const wasFiltered = this.filtered;
                    Object.keys(this.filters).forEach((key) => {
                        if (key in incoming) this.filters[key] = incoming[key] || '';
                    });
                    if (this.filtered) {
                        window.erScrollToResultsOnRender();
                    } else if (wasFiltered) {
                        this.$nextTick(() => window.scrollTo({ top: 0, behavior: 'smooth' }));
                    }
                }
            }"
            x-show="!filtered"
            x-on:update-products.window.camel="onUpdateProducts($event.detail)"
            @if($hasActiveFilters) style="display: none;" @endif>
            <!-- Slider (z-20 para que quede arriba) -->
            <div class="relative z-20">
                <livewire:web-product-slider type="featured" title="PRODUCTO DESTACADOS" />
            </div>

            <!-- Sección de la foto (fondo) con aspect ratio real de la imagen -->
            <div class="relative w-full bg-cover bg-top -mt-56 md:-mt-72" style="background-image: url('{{ asset('themes/encendidorqta/images/frentelocal.png') }}'); aspect-ratio: 1919/899;">
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

    <div class="my-4" id="resultados" style="scroll-margin-top: 10rem;">
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
    (function () {
        window.flyToCart = function (imgId) {
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
                zIndex: '9999',
                transition: 'all 0.8s cubic-bezier(0.42, 0, 0.58, 1)',
                pointerEvents: 'none',
                opacity: '0.8'
            });

            document.body.appendChild(clone);

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

            clone.addEventListener('transitionend', () => {
                clone.remove();
            });
        };

        /**
         * El proxy de imágenes responde 200 con su logo genérico (290x290, 3608 bytes) cuando no
         * logra bajar la imagen original, así que el onerror nunca dispara. Se detecta por tamaño.
         */
        window.erIsProxyFallback = async function (img) {
            const src = img?.currentSrc || '';
            if (!src.includes('/proxy-image')) return false;
            if (img.naturalWidth !== 290 || img.naturalHeight !== 290) return false;

            try {
                const blob = await (await fetch(src)).blob();
                return blob.size === 3608;
            } catch (error) {
                return false;
            }
        };

        // Esta función se vuelve a ejecutar en cada wire:navigate: los listeners se registran una sola vez.
        if (window.__erIndexBound) return;
        window.__erIndexBound = true;

        /**
         * Lleva la vista a los resultados una vez que el listado terminó de actualizarse,
         * así el scroll no se calcula sobre un layout que todavía está cambiando.
         */
        let scrollPending = false;
        window.erScrollToResultsOnRender = function () {
            scrollPending = true;
            setTimeout(() => { scrollPending = false; }, 4000);
        };

        const bindResultsScroll = () => {
            Livewire.hook('commit', ({ component, succeed }) => {
                succeed(() => {
                    if (!scrollPending || component.name !== 'webproductsmain') return;
                    scrollPending = false;
                    requestAnimationFrame(() => {
                        document.getElementById('resultados')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    });
                });
            });
        };

        if (window.Livewire) {
            bindResultsScroll();
        } else {
            document.addEventListener('livewire:init', bindResultsScroll, { once: true });
        }

        const animationName = 'cart-wiggle-animation';

        window.addEventListener('cart-updated', () => {
            const cartIconEffect = document.getElementById('cart-highlight');
            if (!cartIconEffect) return;

            cartIconEffect.classList.remove(animationName);
            void cartIconEffect.offsetWidth;
            cartIconEffect.classList.add(animationName);
            cartIconEffect.addEventListener('animationend', () => {
                cartIconEffect.classList.remove(animationName);
            }, { once: true });
        });

        document.addEventListener('load', async (event) => {
            const img = event.target;
            if (!(img instanceof HTMLImageElement) || !img.dataset.fallback) return;

            if (await window.erIsProxyFallback(img)) {
                img.classList.remove('mix-blend-multiply');
                img.classList.add(...(img.dataset.fallbackClass || '').split(' ').filter(Boolean));
                img.src = img.dataset.fallback;
            }
        }, true);
    })();
</script>

</html>