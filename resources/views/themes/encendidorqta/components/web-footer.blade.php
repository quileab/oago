@php
    $locales = [
        [
            'nombre' => 'Encendido Reconquista',
            'direccion' => 'Moreno 1531, Reconquista, Santa Fe',
            'telefono' => '3482-415931',
            'maps' => 'https://maps.app.goo.gl/t4Vzx1ZCed91sBJ38',
        ],
        [
            'nombre' => 'CIBAT Baterías y Llaves Codificadas (consultas y servicios)',
            'direccion' => 'Moreno 1541, Reconquista, Santa Fe',
            'telefono' => '3482-534205',
            'maps' => 'https://www.google.com/maps/search/?api=1&query=Moreno+1541,+Reconquista,+Santa+Fe',
        ],
    ];

    $nombreEmpresa = \App\Helpers\SettingsHelper::settings('company_name', 'Encendido Reconquista');
    $correo = \App\Helpers\SettingsHelper::settings('company_email', 'cibat@cibat.com.ar');
    $redes = collect(\App\Helpers\SettingsHelper::settings('social_networks', []));
    $whatsapp = $redes->firstWhere('platform', 'WhatsApp');
    $whatsappUrl = ! empty($whatsapp['url']) ? $whatsapp['url'] : 'https://wa.me/5493482415931';
    $redes = $redes->reject(fn ($red) => $red['platform'] === 'WhatsApp');
@endphp
<footer class="relative w-full text-white pt-20 pb-6 mt-32 rounded-t-[3rem] shadow-[0_-20px_50px_rgba(0,0,0,0.6)] border-t border-white/10 overflow-hidden" style="background: linear-gradient(to bottom, #374151, #030712);">
    
    <div class="relative z-10 max-w-7xl mx-auto px-6 grid grid-cols-1 md:grid-cols-3 gap-10">
        <!-- Columna 1: Marca -->
        <div class="flex flex-col items-center md:items-start text-center md:text-left">
            <div class="flex items-center gap-4 mb-4">
                <img src="{{ asset('themes/encendidorqta/images/erlogo.png') }}" alt="Encendido Reconquista" class="h-14 drop-shadow-md" style="filter: grayscale(1) brightness(0) invert(0.8);">
                <div class="w-px h-10 bg-white/20"></div>
                <img src="{{ asset('themes/encendidorqta/images/cibatlogo.png') }}" alt="CIBAT Baterías" class="h-10 drop-shadow-md" style="filter: grayscale(1) brightness(0) invert(0.8);">
            </div>
            <p class="text-sm leading-relaxed max-w-xs text-neutral-content/70">
                {{ $nombreEmpresa }}<br>
                Líderes en distribución de repuestos eléctricos y bujías para el automotor.<br>
                <strong class="font-bold text-white mt-2 block">50 años potenciando tu negocio</strong>
            </p>
        </div>

        <!-- Columna 2: Contacto -->
        <div class="flex flex-col items-center md:items-start text-center md:text-left">
            <h3 class="text-white font-black uppercase tracking-widest text-xs mb-6 flex items-center gap-2">
                <span class="w-6 h-0.5 bg-white rounded-full"></span>
                Ubicación y Contacto
            </h3>
            <ul class="space-y-5 text-sm font-medium">
                @foreach($locales as $local)
                    <li class="flex flex-col items-center md:items-start gap-1.5">
                        <span class="text-white font-black text-xs uppercase tracking-wider">{{ $local['nombre'] }}</span>
                        <a href="{{ $local['maps'] }}" target="_blank" rel="noopener noreferrer" class="flex items-start gap-2 hover:text-white hover:underline transition-colors">
                            <x-icon name="o-map-pin" class="w-4 h-4 text-white shrink-0 mt-0.5" /> {{ $local['direccion'] }}
                        </a>
                        <a href="https://wa.me/549{{ preg_replace('/[^0-9]/', '', $local['telefono']) }}" target="_blank" rel="noopener noreferrer" class="flex items-center gap-2 hover:text-white hover:underline transition-colors tabular-nums">
                            <x-icon name="o-phone" class="w-4 h-4 text-white shrink-0" /> Tel.: {{ $local['telefono'] }}
                        </a>
                    </li>
                @endforeach
                <li class="flex items-center justify-center md:justify-start gap-2 hover:text-white transition-colors">
                    <x-icon name="o-envelope" class="w-4 h-4 text-white shrink-0" />
                    <a href="mailto:{{ $correo }}" class="hover:underline">{{ $correo }}</a>
                </li>
            </ul>
        </div>

        <!-- Columna 3: Redes Sociales -->
        @if($redes->isNotEmpty())
        <div class="flex flex-col items-center md:items-start text-center md:text-left">
            <h3 class="text-white font-black uppercase tracking-widest text-xs mb-6 flex items-center gap-2">
                <span class="w-6 h-0.5 bg-white rounded-full"></span>
                Seguinos
            </h3>
            <div class="flex flex-wrap gap-4 justify-center md:justify-start">
                @foreach($redes as $red)
                    <a href="{{ $red['url'] }}"
                       title="{{ $red['platform'] }}"
                       target="_blank" rel="noopener noreferrer"
                       class="p-3 bg-neutral-focus rounded-xl hover:bg-primary hover:text-primary-content transition-all duration-300 shadow-lg group">
                        <div class="w-6 h-6 group-hover:scale-110 transition-transform flex items-center justify-center">
                            {!! strip_tags($red['icon_svg'], '<svg><path><g><circle><rect><polygon><line><polyline>') !!}
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
        @endif
    </div>

    <!-- Mapa al ancho de las columnas -->
    <div class="relative z-10 max-w-7xl mx-auto px-6 mt-10">
        <div class="w-full grayscale opacity-80 hover:grayscale-0 hover:opacity-100 transition-all duration-500">
            {!! \App\Helpers\SettingsHelper::settings('company_map_iframe', '<iframe src="https://maps.google.com/maps?q=Moreno%201531,%20Reconquista,%20Santa%20Fe&t=&z=17&ie=UTF8&iwloc=&output=embed" width="100%" height="300" style="border:0; border-radius: 0.5rem;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>') !!}
        </div>
    </div>

    <!-- WhatsApp Flotante Mejorado (Movido a la Izquierda) -->
    <div class="fixed bottom-6 left-6 z-[100] group">
        <div class="absolute inset-0 bg-green-500 rounded-full blur-md opacity-20 group-hover:opacity-40 animate-pulse transition-opacity"></div>
        <a href="{{ $whatsappUrl }}" aria-label="WhatsApp Encendido Reconquista 3482-415931"
           target="_blank" rel="noopener noreferrer" class="relative block p-4 bg-green-500 text-white rounded-full shadow-2xl hover:bg-green-600 transition-all duration-300 hover:scale-110 active:scale-95">
           <div class="w-8 h-8 flex items-center justify-center">
               <x-icon name="o-chat-bubble-left-ellipsis" class="w-8 h-8" />
           </div>
        </a>
    </div>
</footer>
