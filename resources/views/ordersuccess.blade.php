<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex flex-col min-h-screen font-sans antialiased bg-gray-200 text-gray-900">
  <livewire:web-navbar />

  <div class="border-2 my-auto py-4 border-green-800 rounded bg-green-500 text-gray-950 text-2xl text-center">
    @if(isset($orderStatus))
      @switch($orderStatus)
        @case('pending')
        <h1 class="text-2xl font-bold">¡Gracias por tu compra!</h1>
        <p>Su pedido se ha realizado correctamente.</p>
        <p>El número de su pedido es: <strong>#{{ $orderId ?? 'N/A' }}</strong></p>
        @break
        @case('on-hold')
        <h1 class="text-2xl font-bold">Su pedido se encuentra EN ESPERA</h1>
        El número de su pedido es: <strong>#{{ $orderId ?? 'N/A' }}</strong>
        <p class="mt-2">⚠️ El precio puede sufrir actualizaciones al retomar el pedido.</p>
        <p>ℹ️ Pasado el lapso de espera, el pedido se cancelará.</p>
        @break
      
        @default
          <h1 class="text-2xl font-bold">⚠️ VERIFIQUE EL ESTADO DE SU PEDIDO!!</h1>
          <p>El número de su pedido es: <strong>#{{ $orderId ?? 'N/A' }}</strong></p>
      @endswitch
      <x-button label="VOLVER" icon="o-home" class="mt-4 btn-lg" link="/" />
    @endif
  </div>
  <x-web-footer />
</body>
</html>