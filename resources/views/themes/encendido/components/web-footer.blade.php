<footer class="relative w-full bg-gradient-to-b from-base-100 via-neutral to-neutral text-neutral-content pt-20 pb-6 mt-32 rounded-t-[3rem] shadow-[0_-20px_50px_rgba(0,0,0,0.6)] border-t border-white/10 overflow-hidden">
    <!-- Background Pattern -->
    <div class="absolute inset-0 z-0 opacity-40 mix-blend-lighten" style="background-image: url('{{ asset('images/footer-bg.jpg') }}'); background-repeat: repeat; background-size: 300px;"></div>
    
    <div class="relative z-10 max-w-7xl mx-auto px-6 grid grid-cols-1 md:grid-cols-3 gap-10">
        <!-- Columna 1: Marca -->
        <div class="flex flex-col items-center md:items-start text-center md:text-left">
            <div class="flex items-center gap-4 mb-4">
                <img src="{{ asset('imgs/Logos/ER50.png') }}" alt="Encendido Reconquista" class="h-14 brightness-0 invert opacity-90">
                <div class="w-px h-10 bg-white/20"></div>
                <img src="{{ asset('imgs/Logos/logo_cibat.png') }}" alt="CIBAT Baterías" class="h-10 brightness-0 invert opacity-90">
            </div>
            <p class="text-sm leading-relaxed max-w-xs text-neutral-content/70">
                Encendido Reconquista y CIBAT: Líderes en distribución de repuestos eléctricos y baterías para el automotor. 50 años potenciando tu negocio.
            </p>
        </div>

        <!-- Columna 2: Contacto -->
        <div class="flex flex-col items-center md:items-start text-center md:text-left">
            <h3 class="text-white font-black uppercase tracking-widest text-xs mb-6 flex items-center gap-2">
                <span class="w-6 h-0.5 bg-primary rounded-full"></span>
                Ubicación y Contacto
            </h3>
            <ul class="space-y-4 text-sm font-medium">
                <li class="flex items-start justify-center md:justify-start gap-3 hover:text-white transition-colors">
                    <x-icon name="o-map-pin" class="w-5 h-5 text-primary shrink-0" />
                    <span>
                        Moreno 1531 y 1541, Reconquista, Santa Fe
                    </span>
                </li>
                <li class="flex items-center justify-center md:justify-start gap-3 hover:text-white transition-colors">
                    <x-icon name="o-phone" class="w-5 h-5 text-primary shrink-0" />
                    <span>3482-415931 (ER) / 3482-534205 (CIBAT)</span>
                </li>
                <li class="flex items-center justify-center md:justify-start gap-3 hover:text-white transition-colors">
                    <x-icon name="o-envelope" class="w-5 h-5 text-primary shrink-0" />
                    <span>contacto@encendidorqta.com.ar</span>
                </li>
            </ul>
        </div>

        <!-- Columna 3: Redes Sociales -->
        <div class="flex flex-col items-center md:items-start text-center md:text-left">
            <h3 class="text-white font-black uppercase tracking-widest text-xs mb-6 flex items-center gap-2">
                <span class="w-6 h-0.5 bg-primary rounded-full"></span>
                Seguinos
            </h3>
            <div class="flex flex-wrap gap-4 justify-center md:justify-start">
                @foreach(App\Helpers\SettingsHelper::settings('social_networks', []) as $network)
                    <a href="{{ $network['url'] }}" 
                       title="{{ $network['platform'] }}"
                       target="_blank" 
                       class="p-3 bg-neutral-focus rounded-xl hover:bg-primary hover:text-primary-content transition-all duration-300 shadow-lg group">
                        <div class="w-6 h-6 group-hover:scale-110 transition-transform flex items-center justify-center">
                            {!! strip_tags($network['icon_svg'], '<svg><path><g><circle><rect><polygon><line><polyline>') !!}
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Mapa al ancho de las columnas -->
    <div class="relative z-10 max-w-7xl mx-auto px-6 mt-10">
        <div class="w-full grayscale opacity-80 hover:grayscale-0 hover:opacity-100 transition-all duration-500">
            {!! strip_tags(App\Helpers\SettingsHelper::settings('company_map_iframe', ''), '<iframe>') !!}
        </div>
    </div>

    <!-- WhatsApp Flotante Mejorado (Movido a la Izquierda) -->
    @php
        $whatsappUrl = 'https://wa.me/5493482415931';
    @endphp
    <div class="fixed bottom-6 left-6 z-[100] group">
        <div class="absolute inset-0 bg-green-500 rounded-full blur-md opacity-20 group-hover:opacity-40 animate-pulse transition-opacity"></div>
        <a href="{{ $whatsappUrl }}" 
           target="_blank" class="relative block p-4 bg-green-500 text-white rounded-full shadow-2xl hover:bg-green-600 transition-all duration-300 hover:scale-110 active:scale-95">
           <div class="w-8 h-8 flex items-center justify-center">
               <x-icon name="o-chat-bubble-left-ellipsis" class="w-8 h-8" />
           </div>
        </a>
    </div>
</footer>
