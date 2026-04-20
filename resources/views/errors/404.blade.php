<!DOCTYPE html>
<html lang="es" data-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Página no encontrada - {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="antialiased overflow-hidden">
    {{-- CAPAS DE FONDO --}}
    <div class="fixed inset-0 bg-slate-950 -z-30"></div>

    <div class="fixed inset-0 -z-20 opacity-60">
        <div class="w-full h-full bg-cover bg-center blur-3xl" 
             style="background-image: url('{{ asset('imgs/rsrc/404_bg.png') }}');">
        </div>
    </div>

    <div class="fixed inset-0 -z-10 flex items-center justify-center pointer-events-none">
        <div class="h-full w-full max-w-4xl bg-contain bg-no-repeat bg-center opacity-80"
             style="background-image: url('{{ asset('imgs/rsrc/404_bg.png') }}');">
        </div>
    </div>

    {{-- INTERFAZ --}}
    <main class="min-h-screen flex flex-col justify-between p-2 md:p-4 relative z-10">
        
        {{-- PARTE SUPERIOR: Mensaje --}}
        <div class="flex justify-center w-full">
            <div class="max-w-xl w-full bg-base-100/60 backdrop-blur-xl p-4 md:p-6 rounded-3xl shadow-2xl border border-white/10 text-center">
                <div class="relative inline-block mb-1">
                    <h1 class="text-6xl font-black text-primary/40 select-none">404</h1>
                </div>
                <h2 class="text-2xl font-black uppercase tracking-tighter mb-1 text-white drop-shadow-md">¡Ups! Perdido</h2>
                <p class="text-white/90 font-bold drop-shadow-sm text-sm">La página que buscas no existe o ha sido movida a otra ubicación.</p>
            </div>
        </div>

        {{-- PARTE INFERIOR: Acciones --}}
        <div class="flex justify-center w-full mb-4">
            <div class="max-w-xs w-full drop-shadow-2xl">
                <x-button label="VOLVER AL INICIO" icon="o-home" class="btn-primary w-full font-bold shadow-2xl" link="/" />
            </div>
        </div>

    </main>
</body>
</html>