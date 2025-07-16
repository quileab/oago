<!DOCTYPE html>
<html data-theme="light" lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, viewport-fit=cover">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<!-- Charly -->

<body class="min-h-screen font-sans antialiased bg-gray-200 text-gray-900">
  <livewire:web-navbar />
  <livewire:users.guests.registration />
  <x-web-footer />
</body>

</html>