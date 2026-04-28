<footer class="w-full bg-slate-900 text-slate-300 pt-10 pb-6 mt-12 border-t-4 border-orange-500">
    <div class="max-w-7xl mx-auto px-6 grid grid-cols-1 md:grid-cols-3 gap-10">
        <!-- Columna 1: Marca -->
        <div class="flex flex-col items-center md:items-start text-center md:text-left">
            <img src="{{ asset('imgs/brand-logo.webp') }}" alt="Brand Logo" class="h-14 mb-4 opacity-90 brightness-0 invert">
            <p class="text-sm leading-relaxed max-w-xs text-slate-400">
                Líderes en distribución de productos de calidad. Comprometidos con el crecimiento de nuestros clientes en toda la región.
            </p>
        </div>

        <!-- Columna 2: Contacto -->
        <div class="flex flex-col items-center md:items-start text-center md:text-left">
            <h3 class="text-white font-black uppercase tracking-widest text-xs mb-6 flex items-center gap-2">
                <span class="w-6 h-0.5 bg-orange-500 rounded-full"></span>
                Ubicación y Contacto
            </h3>
            <ul class="space-y-4 text-sm font-medium">
                <li class="flex items-start justify-center md:justify-start gap-3 hover:text-white transition-colors">
                    <x-icon name="o-map-pin" class="w-5 h-5 text-orange-500 shrink-0" />
                    <span>
                        {{ App\Helpers\SettingsHelper::settings('company_address', 'Av. José Gorriti, S3000, Santa Fe') }}
                    </span>
                </li>
                <li class="flex items-center justify-center md:justify-start gap-3 hover:text-white transition-colors">
                    <x-icon name="o-phone" class="w-5 h-5 text-orange-500 shrink-0" />
                    <span>{{ App\Helpers\SettingsHelper::settings('company_phone', '+54 9 342 463-8925') }}</span>
                </li>
                <li class="flex items-center justify-center md:justify-start gap-3 hover:text-white transition-colors">
                    <x-icon name="o-envelope" class="w-5 h-5 text-orange-500 shrink-0" />
                    <span>{{ App\Helpers\SettingsHelper::settings('company_email', 'contacto@oagostini.com.ar') }}</span>
                </li>
            </ul>
            <div class="mt-6 w-full grayscale opacity-70 hover:grayscale-0 hover:opacity-100 transition-all duration-500 rounded-xl overflow-hidden">
                {!! App\Helpers\SettingsHelper::settings('company_map_iframe', '') !!}
            </div>
        </div>

        <!-- Columna 3: Redes Sociales -->
        <div class="flex flex-col items-center md:items-start text-center md:text-left">
            <h3 class="text-white font-black uppercase tracking-widest text-xs mb-6 flex items-center gap-2">
                <span class="w-6 h-0.5 bg-orange-500 rounded-full"></span>
                Seguinos
            </h3>
            <div class="flex flex-wrap gap-4 justify-center md:justify-start">
                @foreach(App\Helpers\SettingsHelper::settings('social_networks', []) as $network)
                    <a href="{{ $network['url'] }}" 
                       title="{{ $network['platform'] }}"
                       target="_blank" 
                       class="p-3 bg-slate-800 rounded-xl hover:bg-orange-500 hover:text-white transition-all duration-300 shadow-lg group">
                        <div class="w-6 h-6 group-hover:scale-110 transition-transform flex items-center justify-center">
                            {!! $network['icon_svg'] !!}
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    </div>

    <!-- WhatsApp Flotante Mejorado (Movido a la Izquierda) -->
    @php
        $whatsapp = collect(App\Helpers\SettingsHelper::settings('social_networks', []))->firstWhere('platform', 'WhatsApp');
    @endphp
    @if($whatsapp)
        <div class="fixed bottom-6 left-6 z-[100] group">
            <div class="absolute inset-0 bg-green-500 rounded-full blur-md opacity-20 group-hover:opacity-40 animate-pulse transition-opacity"></div>
            <a href="{{ $whatsapp['url'] }}" 
               target="_blank" class="relative block p-4 bg-green-500 text-white rounded-full shadow-2xl hover:bg-green-600 transition-all duration-300 hover:scale-110 active:scale-95">
               <div class="w-8 h-8 flex items-center justify-center">
                   {!! $whatsapp['icon_svg'] !!}
               </div>
            </a>
        </div>
    @endif
</footer>
