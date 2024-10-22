<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<!-- Charly -->

<body class="flex flex-col min-h-screen font-sans antialiased bg-gray-200 text-gray-900">
  <x-web-navbar />

  <div class="border-2 my-auto py-4 border-green-600 rounded bg-green-300 text-gray-950 text-2xl text-center">
    Orden enviada correctamente.
  </div>

  <x-web-footer />
</body>

</html>






