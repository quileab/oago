<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<!-- Charly -->

<body class="min-h-screen font-sans antialiased bg-gray-200 text-gray-900">
    <x-web-navbar />
    <livewire:web-search-filter />
    <div class="my-4">
        @if(session()->has('search')||session()->has('category'))
        <x-collapse>
            <x-slot:heading>
                Contenido del Carrito
            </x-slot:heading>
            <x-slot:content>
                @livewire('cart') 
            </x-slot:content>
        </x-collapse>
        <livewire:web-product title="Productos Encontrados" :items=30 :filter="['published' => true]" />
        @else
        <livewire:web-product title="Productos Destacados" :items=3 :filter="['featured' => true, 'published' => true]" />
        <livewire:web-product title="Productos Publicados" :items=9 :filter="['published' => true]" />
        @endif
    </div>
    <x-web-footer />
    {{--  TOAST area --}}
    <x-toast />
</body>

</html>