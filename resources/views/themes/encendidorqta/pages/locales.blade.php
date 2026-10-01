@php
    $whatsappIcon = 'M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.888-.788-1.487-1.761-1.663-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z';

    $horarios = [
        ['label' => 'Lunes a viernes', 'value' => '8:00 a 12:30 y 15:30 a 19:30'],
        ['label' => 'Sábados', 'value' => '9:00 a 12:30'],
    ];

    $erServicios = [
        'Arranques y alternadores',
        'Instalaciones eléctricas',
        'Bobinas, distribuidores y bujías',
        'Módulos y captores electrónicos',
        'Ópticas y faros',
        'Carburación y accesorios',
    ];

    $cibatServicios = [
        'Baterías multimarca y línea pesada',
        'Baterías para energía solar (litio, VRLA)',
        'Llaves codificadas en el acto',
        'Telemandos originales y alternativos',
        'Reparación y diagnóstico',
    ];

    $sucursales = [
        [
            'nombre' => 'Encendido Reconquista',
            'badge' => 'Casa Central',
            'color' => '#a6282e',
            'colorHover' => '#8d2228',
            'logo' => 'erlogo.png',
            'logoAlt' => 'Encendido Reconquista',
            'panel' => 'foto',
            'foto' => 'frenteencendido.jpg',
            'descripcion' => 'Nuestra casa matriz. Un inmenso salón de ventas y depósito con el stock más amplio en electricidad y repuestos para el automotor, transporte y maquinaria agrícola.',
            'direccion' => 'Moreno 1541, Reconquista (Santa Fe)',
            'telefono' => '3482 415931',
            'whatsapp' => 'https://wa.me/5493482415931',
            'maps' => 'https://maps.app.goo.gl/t4Vzx1ZCed91sBJ38',
            'catalogo' => '/?store=er',
            'catalogoLabel' => 'catálogo ER',
            'servicios' => $erServicios,
            'patternPosition' => 'right center',
        ],
        [
            'nombre' => 'CIBAT Baterías',
            'badge' => 'Centro Especializado',
            'color' => '#1b365d',
            'colorHover' => '#162d4d',
            'logo' => 'cibatlogo.png',
            'logoAlt' => 'CIBAT Baterías',
            'panel' => 'marca',
            'panelBajada' => 'Centro Integral de Baterías',
            'descripcion' => 'El único Centro Integral de Baterías de la región. Instalación en el acto, diagnóstico por escáner y el respaldo directo de Moura, Willard y Moura Moto.',
            'direccion' => 'Reconquista (Santa Fe)',
            'telefono' => '3482 53-4205',
            'whatsapp' => 'https://wa.me/5493482534205',
            'maps' => 'https://maps.app.goo.gl/y2X8u5Y2Z5X8y2Y5A',
            'catalogo' => '/?store=cibat',
            'catalogoLabel' => 'catálogo CIBAT',
            'servicios' => $cibatServicios,
            'patternPosition' => 'left center',
        ],
    ];
@endphp

<x-layouts.clean>

<div class="bg-gray-200 min-h-screen font-sans antialiased text-gray-900">

    {{-- Hero — foto real del local, mismo tratamiento que home y nosotros --}}
    <div class="relative w-full overflow-hidden bg-gray-900">
        <div class="absolute inset-0">
            <img src="{{ asset('themes/encendidorqta/images/frentelocal.png') }}"
                alt="Fachada de Encendido Reconquista y CIBAT en Reconquista"
                class="w-full h-full object-cover object-top">
            <div class="absolute inset-0 bg-black/55"></div>
            <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-transparent to-black/20"></div>
        </div>

        <a href="/"
            class="absolute top-6 left-6 z-20 hidden md:inline-flex items-center gap-2 bg-white/95 backdrop-blur text-gray-900 px-4 py-2 rounded-full text-xs font-black uppercase tracking-widest shadow-lg hover:bg-gray-50 transition-colors">
            <x-icon name="o-arrow-left" class="w-4 h-4" /> Volver al catálogo
        </a>
        <a href="/"
            class="absolute top-4 left-4 z-20 md:hidden inline-flex items-center justify-center w-10 h-10 bg-white text-gray-900 rounded-full shadow-lg"
            aria-label="Volver al catálogo">
            <x-icon name="o-arrow-left" class="w-5 h-5" />
        </a>

        <div class="relative z-10 max-w-7xl mx-auto px-6 pt-24 pb-10 md:pt-28 md:pb-16">
            <div class="flex flex-col gap-6">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 bg-white/15 backdrop-blur rounded-lg flex items-center justify-center shrink-0"
                        style="clip-path: polygon(25% 0%, 75% 0%, 100% 50%, 75% 100%, 25% 100%, 0% 50%);">
                        <span class="w-2 h-2 bg-white rounded-full"></span>
                    </div>
                    <span class="text-white/85 text-xs font-black uppercase tracking-[0.2em]">Reconquista, Santa Fe — dos centros, un mismo barrio</span>
                </div>

                <div>
                    <h1 class="text-4xl md:text-5xl lg:text-6xl font-black text-white uppercase tracking-tighter leading-none drop-shadow-lg">
                        Nuestros locales
                    </h1>
                    <p class="mt-4 text-lg md:text-xl text-white font-medium max-w-2xl leading-relaxed drop-shadow-md">
                        Dos centros, un mismo estándar de servicio. Stock de sobra, mostradores que conocen el rubro y
                        <span class="text-white font-black drop-shadow-lg">guardia 24hs</span> para cuando una urgencia no puede esperar.
                    </p>
                </div>

                <div class="flex flex-wrap gap-3 pt-2">
                    <a href="{{ $sucursales[0]['whatsapp'] }}" target="_blank" rel="noopener noreferrer"
                        class="inline-flex items-center gap-2 bg-[#a6282e] hover:bg-[#8d2228] text-white px-5 py-2.5 rounded-full text-xs font-black uppercase tracking-widest shadow-lg transition-colors">
                        <svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="{{ $whatsappIcon }}" /></svg>
                        ER 3482 415931
                    </a>
                    <a href="{{ $sucursales[1]['whatsapp'] }}" target="_blank" rel="noopener noreferrer"
                        class="inline-flex items-center gap-2 bg-[#1b365d] hover:bg-[#162d4d] text-white px-5 py-2.5 rounded-full text-xs font-black uppercase tracking-widest shadow-lg transition-colors">
                        <svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="{{ $whatsappIcon }}" /></svg>
                        CIBAT 3482 53-4205
                    </a>
                </div>
            </div>
        </div>

        {{-- stats franja — replica home --}}
        <div class="relative z-10 border-t border-white/10 bg-black/25 backdrop-blur-sm">
            <div class="max-w-7xl mx-auto px-6 py-4 grid grid-cols-2 md:grid-cols-4 gap-4 md:gap-6">
                @foreach([
                    ['2', 'SUCURSALES'],
                    ['25.000', 'PRODUCTOS'],
                    ['100', 'MARCAS'],
                    ['24HS', 'DE GUARDIA'],
                ] as $stat)
                    <div class="flex items-center gap-3 text-white">
                        <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"
                            class="w-10 h-10 md:w-12 md:h-12 shrink-0 drop-shadow-[0_2px_6px_rgba(0,0,0,0.6)]">
                            <path d="M12 2l9 5v10l-9 5-9-5V7l9-5z" />
                        </svg>
                        <div class="leading-none">
                            <div class="text-lg md:text-2xl font-black italic tracking-tighter">{{ $stat[0] }}</div>
                            <div class="text-[10px] font-bold uppercase tracking-widest opacity-80">{{ $stat[1] }}</div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Sucursales --}}
    <div class="relative">
        <div class="absolute top-0 left-1/2 -translate-x-1/2 w-3/4 h-64 bg-gradient-to-b from-white/60 to-transparent blur-3xl pointer-events-none"></div>

        <div class="relative z-10 max-w-7xl mx-auto px-4 md:px-6 py-12 md:py-16">

            <div class="flex items-center gap-3 mb-8" data-reveal>
                <div class="w-8 h-8 bg-gray-400 shrink-0 flex items-center justify-center"
                    style="clip-path: polygon(25% 0%, 75% 0%, 100% 50%, 75% 100%, 25% 100%, 0% 50%);">
                    <span class="w-2 h-2 bg-white rounded-full"></span>
                </div>
                <h2 class="text-xl md:text-2xl font-black uppercase tracking-wide text-gray-500">Dónde encontrarnos</h2>
                <span class="hidden lg:inline text-xs font-bold text-gray-400 ml-3">Recepción, depósito y venta directa en ambas
                    sucursales</span>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 lg:gap-8 items-stretch">
                @foreach($sucursales as $sucursal)
                    <div data-reveal data-reveal-delay="{{ $loop->index * 90 }}" class="h-full">
                        <article class="relative h-full bg-white rounded-2xl border border-gray-100 shadow-md hover:shadow-2xl hover:-translate-y-1 transition-all duration-300 flex flex-col overflow-hidden"
                            style="--brand: {{ $sucursal['color'] }}; --brand-hover: {{ $sucursal['colorHover'] }}; --brand-soft: {{ $sucursal['color'] }}1f; --brand-alt: {{ $sucursales[1 - $loop->index]['color'] }};">

                            <div class="absolute inset-0 opacity-[0.04] pointer-events-none"
                                style="background-image: url('{{ asset('themes/encendidorqta/images/patron_hex2.png') }}'); background-size: cover; background-position: {{ $sucursal['patternPosition'] }};">
                            </div>
                            <div class="absolute -right-10 -top-10 w-36 h-36 bg-[var(--brand-soft)] pointer-events-none"
                                style="clip-path: polygon(50% 0%, 100% 25%, 100% 75%, 50% 100%, 0% 75%, 0% 25%);">
                            </div>

                            <div class="relative flex-1 p-6 md:p-8 flex flex-col">

                                <div class="flex items-center justify-between gap-4 mb-6">
                                    <img src="{{ asset('themes/encendidorqta/images/' . $sucursal['logo']) }}" alt="{{ $sucursal['logoAlt'] }}"
                                        class="h-10 w-auto object-contain"
                                        style="filter: grayscale(1) brightness(0.35) contrast(1.05);">
                                    <span class="text-[10px] font-black uppercase tracking-widest text-white px-3 py-1 rounded-full shrink-0 bg-[var(--brand)]">{{ $sucursal['badge'] }}</span>
                                </div>

                                @if($sucursal['panel'] === 'foto')
                                    <div class="relative mb-6">
                                        <div class="absolute -inset-2 rounded-2xl opacity-10 rotate-1 bg-[var(--brand)]"></div>
                                        <div class="absolute -inset-2 rounded-2xl opacity-10 -rotate-1 bg-[var(--brand-alt)]"></div>
                                        <div class="relative rounded-xl overflow-hidden shadow-md aspect-video bg-gray-200 group">
                                            <img src="{{ asset('themes/encendidorqta/images/' . $sucursal['foto']) }}"
                                                alt="{{ $sucursal['nombre'] }}"
                                                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700">
                                            <div class="absolute inset-0 bg-gradient-to-t from-black/75 via-black/10 to-transparent"></div>
                                            <p class="absolute bottom-0 left-0 right-0 p-3 md:p-4 text-white text-[11px] font-black uppercase tracking-wider leading-snug flex items-start gap-2">
                                                <span class="mt-1.5 w-5 h-0.5 bg-white rounded-full shrink-0"></span> {{ $sucursal['direccion'] }}
                                            </p>
                                        </div>
                                    </div>
                                @else
                                    <div class="relative mb-6">
                                        <div class="absolute -inset-2 rounded-2xl opacity-10 rotate-1 bg-[var(--brand)]"></div>
                                        <div class="absolute -inset-2 rounded-2xl opacity-10 -rotate-1 bg-[var(--brand-alt)]"></div>
                                        <div class="relative rounded-xl overflow-hidden shadow-md aspect-video bg-gray-900 flex flex-col items-center justify-center gap-3 px-6">
                                            <div class="absolute inset-0 opacity-[0.05] pointer-events-none mix-blend-screen"
                                                style="background-image: url('{{ asset('themes/encendidorqta/images/patron_hex3.png') }}'); background-size: cover; background-position: center;">
                                            </div>
                                            <img src="{{ asset('themes/encendidorqta/images/' . $sucursal['logo']) }}" alt="{{ $sucursal['logoAlt'] }}"
                                                class="relative h-12 md:h-16 w-auto object-contain"
                                                style="filter: grayscale(1) brightness(0) invert(1) opacity(0.92);">
                                            <div class="relative w-10 h-0.5 rounded-full bg-[var(--brand)]"></div>
                                            <p class="relative text-white text-[11px] font-black uppercase tracking-[0.2em] text-center drop-shadow-[0_2px_6px_rgba(0,0,0,0.6)]">
                                                {{ $sucursal['panelBajada'] }}
                                            </p>
                                            <p class="absolute bottom-0 left-0 right-0 bg-black/50 px-3 py-2 text-white/90 text-[11px] font-bold uppercase tracking-wider text-center leading-snug">
                                                {{ $sucursal['direccion'] }}
                                            </p>
                                        </div>
                                    </div>
                                @endif

                                <h3 class="text-2xl font-black text-gray-900 uppercase tracking-tight drop-shadow-sm">{{ $sucursal['nombre'] }}</h3>
                                <div class="w-12 h-1 rounded-full mt-2 mb-4 bg-[var(--brand)]"></div>

                                <p class="text-gray-600 text-sm md:text-base leading-relaxed font-medium mb-6">
                                    {{ $sucursal['descripcion'] }}
                                </p>

                                <div class="rounded-xl bg-gray-50 border border-gray-100 p-4 mb-6">
                                    <div class="flex items-start gap-3">
                                        <div class="w-9 h-9 rounded-lg bg-white flex items-center justify-center shrink-0 shadow-sm text-gray-700">
                                            <x-icon name="o-clock" class="w-5 h-5" />
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <div class="text-[10px] font-black uppercase tracking-widest text-gray-500 mb-2">Horarios de
                                                atención</div>
                                            <dl class="space-y-1">
                                                @foreach($horarios as $horario)
                                                    <div class="flex flex-wrap items-baseline gap-x-2 text-sm">
                                                        <dt class="font-bold text-gray-500">{{ $horario['label'] }}</dt>
                                                        <dd class="font-black text-gray-900 tabular-nums">{{ $horario['value'] }}</dd>
                                                    </div>
                                                @endforeach
                                            </dl>
                                            <div class="mt-2.5 inline-flex items-center gap-1.5 text-[11px] font-black uppercase tracking-wider text-green-800 bg-green-50 border border-green-200 rounded-full px-2.5 py-1">
                                                <span class="w-1.5 h-1.5 bg-green-600 rounded-full animate-pulse"></span>
                                                Guardia 24hs por emergencias
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <ul class="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-2 mb-7">
                                    @foreach($sucursal['servicios'] as $servicio)
                                        <li class="flex items-start gap-2.5 text-[13px] font-medium text-gray-700">
                                            <span class="mt-0.5 w-5 h-5 rounded-full shrink-0 flex items-center justify-center bg-[var(--brand-soft)] text-[var(--brand)]">
                                                <x-icon name="o-check" class="w-3 h-3" />
                                            </span>
                                            <span>{{ $servicio }}</span>
                                        </li>
                                    @endforeach
                                </ul>

                                <div class="mt-auto pt-6 border-t border-gray-100">
                                    <div class="flex items-center gap-3 mb-4">
                                        <a href="tel:{{ preg_replace('/[^0-9]/', '', $sucursal['telefono']) }}"
                                            class="inline-flex items-center gap-2 text-sm font-black text-gray-900 tabular-nums hover:text-[var(--brand)] transition-colors">
                                            <x-icon name="o-phone" class="w-4 h-4 text-gray-400" /> {{ $sucursal['telefono'] }}
                                        </a>
                                        <span class="text-gray-300">·</span>
                                        <a href="{{ $sucursal['catalogo'] }}"
                                            class="inline-flex items-center gap-1 text-xs font-black uppercase tracking-widest text-gray-500 hover:text-[var(--brand)] transition-colors">
                                            Ver {{ $sucursal['catalogoLabel'] }}
                                            <x-icon name="o-arrow-right" class="w-3.5 h-3.5" />
                                        </a>
                                    </div>
                                    <div class="grid grid-cols-2 gap-3">
                                        <a href="{{ $sucursal['whatsapp'] }}" target="_blank" rel="noopener noreferrer"
                                            class="inline-flex items-center justify-center gap-2 text-white px-4 py-3 rounded-full text-xs font-black uppercase tracking-widest shadow-md bg-[var(--brand)] hover:bg-[var(--brand-hover)] transition-colors">
                                            <svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="{{ $whatsappIcon }}" /></svg>
                                            Mensaje
                                        </a>
                                        <a href="{{ $sucursal['maps'] }}" target="_blank" rel="noopener noreferrer"
                                            class="inline-flex items-center justify-center gap-2 bg-white text-gray-900 border border-gray-200 px-4 py-3 rounded-full text-xs font-black uppercase tracking-widest shadow-sm hover:bg-gray-50 hover:border-gray-300 transition-colors">
                                            <x-icon name="o-map" class="w-4 h-4 shrink-0" /> Cómo llegar
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </article>
                    </div>
                @endforeach
            </div>

            <div class="mt-6 lg:mt-8 bg-white rounded-2xl shadow-sm border border-gray-100 p-3 md:p-4" data-reveal>
                <img src="{{ asset('themes/encendidorqta/images/banner_vans.png') }}"
                    alt="Servicio a domicilio con furgones ER y CIBAT, respuesta 24hs"
                    class="w-full h-auto rounded-xl" loading="lazy">
            </div>
        </div>
    </div>

    {{-- Cierre guardia --}}
    <div class="relative overflow-hidden bg-gray-900">
        <div class="absolute inset-0 opacity-[0.05] pointer-events-none mix-blend-screen"
            style="background-image: url('{{ asset('themes/encendidorqta/images/patron_hex3.png') }}'); background-size: cover; background-position: center;">
        </div>
        <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/30 to-transparent"></div>

        <div class="relative max-w-7xl mx-auto px-6 py-12 md:py-16">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-center">
                <div class="lg:col-span-7" data-reveal>
                    <h2 class="text-3xl md:text-4xl font-black uppercase tracking-tighter text-white leading-none drop-shadow-lg">
                        Una urgencia no<br class="hidden sm:block" /> espera al lunes
                    </h2>
                    <p class="mt-4 text-base text-white/80 leading-relaxed max-w-xl">
                        La guardia 24hs atiende emergencias de arranque y batería las 24 horas del día, todos los días del año.
                        Escribinos y te respondemos al instante.
                    </p>
                    <div class="mt-6 flex flex-col sm:flex-row gap-3">
                        <a href="{{ $sucursales[0]['whatsapp'] }}" target="_blank" rel="noopener noreferrer"
                            class="inline-flex items-center justify-center gap-2 bg-white text-gray-900 px-6 py-3 rounded-full text-xs font-black uppercase tracking-widest shadow-lg hover:bg-gray-100 transition-colors">
                            <svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="{{ $whatsappIcon }}" /></svg>
                            ER 3482 415931
                        </a>
                        <a href="{{ $sucursales[1]['whatsapp'] }}" target="_blank" rel="noopener noreferrer"
                            class="inline-flex items-center justify-center gap-2 bg-[#1b365d] hover:bg-[#162d4d] text-white border border-white/20 px-6 py-3 rounded-full text-xs font-black uppercase tracking-widest shadow-lg transition-colors">
                            <svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="{{ $whatsappIcon }}" /></svg>
                            CIBAT 3482 53-4205
                        </a>
                    </div>
                </div>

                <div class="lg:col-span-5" data-reveal data-reveal-delay="90">
                    <div class="bg-white/[0.06] backdrop-blur rounded-2xl border border-white/10 p-6">
                        <div class="flex items-center gap-3 mb-5">
                            <div class="w-10 h-10 rounded-xl bg-white/15 flex items-center justify-center shrink-0"
                                style="clip-path: polygon(50% 0%, 100% 25%, 100% 75%, 50% 100%, 0% 75%, 0% 25%);">
                                <x-icon name="o-clock" class="w-5 h-5 text-white" />
                            </div>
                            <div class="leading-tight">
                                <div class="text-xl font-black italic tracking-tighter text-white">24HS</div>
                                <div class="text-[10px] font-bold uppercase tracking-widest text-white/70">Todos los días del año</div>
                            </div>
                        </div>
                        <dl class="space-y-3 text-sm">
                            @foreach($horarios as $horario)
                                <div class="flex flex-wrap items-baseline justify-between gap-x-3 gap-y-0.5 border-b border-white/10 pb-2.5">
                                    <dt class="font-bold text-white/70">{{ $horario['label'] }}</dt>
                                    <dd class="font-black text-white tabular-nums">{{ $horario['value'] }}</dd>
                                </div>
                            @endforeach
                        </dl>
                        <p class="mt-4 text-[11px] font-black uppercase tracking-wider text-green-300">
                            Fuera de horario: guardia de emergencias activa
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        if (!('IntersectionObserver' in window)) return;
        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

        const targets = document.querySelectorAll('[data-reveal]');
        if (!targets.length) return;

        targets.forEach((el) => {
            el.classList.add(
                'opacity-0',
                'translate-y-6',
                'transition-[opacity,transform]',
                'duration-700',
                'ease-[cubic-bezier(0.16,1,0.3,1)]'
            );
            const delay = parseInt(el.dataset.revealDelay || '0', 10);
            if (delay) el.style.transitionDelay = `${delay}ms`;
        });

        const observer = new IntersectionObserver(
            (entries) => {
                entries.forEach((entry) => {
                    if (!entry.isIntersecting) return;
                    entry.target.classList.remove('opacity-0', 'translate-y-6');
                    observer.unobserve(entry.target);
                });
            },
            { threshold: 0.12, rootMargin: '0px 0px -8% 0px' }
        );

        targets.forEach((el) => observer.observe(el));
    });
</script>

</x-layouts.clean>