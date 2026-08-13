<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, viewport-fit=cover">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="icon" type="image/png" href="{{ asset('imgs/Logos/isoER50.png') }}">
</head>

<body class="grid m-0 p-0 min-h-screen font-sans antialiased bg-base-200/50 dark:bg-base-200">
  <div class="scroll-progress" id="scrollProgress"></div>
  <livewire:web-navbar />
  <x-main>
    <x-slot:content>
      @if(isset($slot))
      {{ $slot }}
    @endif
    </x-slot:content>
    {{-- TOAST area --}}
    <x-toast />

    {{-- Scroll Progress Bar --}}
    <script>
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
    </script>

    {{-- GSAP and Magnetic Buttons --}}
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const magneticElements = document.querySelectorAll('.btn:not(.btn-ghost), .magnetic');
            
            magneticElements.forEach(elem => {
                elem.addEventListener('mousemove', e => {
                    const rect = elem.getBoundingClientRect();
                    const x = e.clientX - rect.left - rect.width / 2;
                    const y = e.clientY - rect.top - rect.height / 2;
                    
                    gsap.to(elem, {
                        x: x * 0.3,
                        y: y * 0.3,
                        duration: 0.3,
                        ease: "power2.out"
                    });
                });
                
                elem.addEventListener('mouseleave', () => {
                    gsap.to(elem, {
                        x: 0,
                        y: 0,
                        duration: 0.5,
                        ease: "elastic.out(1, 0.3)"
                    });
                });
            });
        });
    </script>
  </x-main>
  <x-web-footer />
</body>

</html>