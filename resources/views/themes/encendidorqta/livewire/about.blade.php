<?php
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;

new #[Layout('components.layouts.clean')] #[Title('Nosotros — Encendido Reconquista / CIBAT')] class extends Component {
    public array $benefits = [
        ['title' => 'Experiencia', 'text' => 'Más de 50 años liderando el rubro automotor en la región.', 'icon' => 'o-shield-check'],
        ['title' => 'Especialización', 'text' => 'Técnicos capacitados para resolver lo que otros no pueden.', 'icon' => 'o-academic-cap'],
        ['title' => 'Transparencia', 'text' => 'Presupuestos claros y repuestos de primera calidad.', 'icon' => 'o-document-text'],
    ];

    public array $erServices = [
        'Arranques y alternadores',
        'Instalaciones eléctricas',
        'Bobinas, distribuidores y bujías',
        'Módulos y captores electrónicos',
        'Ópticas y faros',
        'Carburación y accesorios',
    ];

    public array $cibatServices = [
        'Baterías multimarca y línea pesada',
        'Baterías para energía solar (litio, VRLA)',
        'Llaves codificadas en el acto',
        'Telemandos originales y alternativos',
        'Reparación y diagnóstico',
    ];

    public array $timeline = [
        ['year' => '1975', 'title' => 'El origen', 'text' => 'Pablo Zancada y Ángela Weidmann abren el taller familiar. Lealtad, honestidad y ética comercial como cimientos.'],
        ['year' => '1990 — 2010', 'title' => 'Crecimiento', 'text' => 'Segunda generación al frente. De mostrador a depot regional: más stock, más marcas, mismo trato cercano.'],
        ['year' => 'Hoy', 'title' => 'Centro integral', 'text' => '3 unidades de negocio, 25.000 productos y 100 marcas. El mismo barrio, escala mayorista.'],
    ];
}; ?>

<div class="bg-gray-200 min-h-screen font-sans antialiased text-gray-900">

    {{-- Hero — mismo ADN que home frentelocal --}}
    <div class="relative w-full overflow-hidden bg-gray-900">
        <div class="absolute inset-0">
            <img src="{{ asset('themes/encendidorqta/frentelocal.png') }}" alt="Frente Encendido Reconquista" class="w-full h-full object-cover object-top">
            <div class="absolute inset-0 bg-black/55"></div>
            <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-transparent to-black/20"></div>
        </div>

        {{-- pill volver --}}
        <a href="/" class="absolute top-6 left-6 z-20 hidden md:inline-flex items-center gap-2 bg-white/95 backdrop-blur text-gray-900 px-4 py-2 rounded-full text-xs font-black uppercase tracking-widest shadow-lg hover:bg-white transition-colors">
            <x-icon name="o-arrow-left" class="w-4 h-4" /> Volver al catálogo
        </a>
        <a href="/" class="absolute top-4 left-4 z-20 md:hidden inline-flex items-center justify-center w-10 h-10 bg-white text-gray-900 rounded-full shadow-lg">
            <x-icon name="o-arrow-left" class="w-5 h-5" />
        </a>

        <div class="relative z-10 max-w-7xl mx-auto px-6 pt-24 pb-10 md:pt-28 md:pb-16">
            <div class="flex flex-col gap-6">
                <div class="inline-flex items-center gap-3">
                    <div class="w-8 h-8 bg-white/15 backdrop-blur rounded-lg flex items-center justify-center" style="clip-path: polygon(25% 0%, 75% 0%, 100% 50%, 75% 100%, 25% 100%, 0% 50%);">
                        <span class="w-2 h-2 bg-white rounded-full"></span>
                    </div>
                    <span class="text-white/80 text-xs font-black uppercase tracking-[0.2em]">Desde 1975 — Reconquista, Santa Fe</span>
                </div>

                <div>
                    <h1 class="text-4xl md:text-5xl lg:text-6xl font-black text-white uppercase tracking-tighter leading-none">
                        Nosotros
                    </h1>
                    <p class="mt-4 text-lg md:text-xl text-white/85 font-medium max-w-2xl leading-relaxed">
                        Un legado familiar que se hizo centro integral. Dos marcas, un mismo mostrador: <span class="text-white font-black">ER</span> electricidad y <span class="text-white font-black">CIBAT</span> baterías.
                    </p>
                </div>

                <div class="flex flex-wrap gap-3 pt-2">
                    <a href="/?store=er" class="inline-flex items-center gap-2 bg-[#a6282e] hover:bg-[#8d2228] text-white px-5 py-2.5 rounded-full text-xs font-black uppercase tracking-widest shadow-lg transition-colors">
                        Ver productos ER <x-icon name="o-arrow-right" class="w-4 h-4" />
                    </a>
                    <a href="/?store=cibat" class="inline-flex items-center gap-2 bg-[#1b365d] hover:bg-[#162d4d] text-white px-5 py-2.5 rounded-full text-xs font-black uppercase tracking-widest shadow-lg transition-colors">
                        Ver productos CIBAT <x-icon name="o-arrow-right" class="w-4 h-4" />
                    </a>
                </div>
            </div>
        </div>

        {{-- stats franja — replica home --}}
        <div class="relative z-10 border-t border-white/10 bg-black/25 backdrop-blur-sm">
            <div class="max-w-7xl mx-auto px-6 py-4 grid grid-cols-2 md:grid-cols-4 gap-4 md:gap-6">
                <div class="flex items-center gap-3 text-white">
                    <svg viewBox="0 0 24 24" fill="currentColor" class="w-10 h-10 md:w-12 md:h-12 shrink-0 drop-shadow-[0_2px_6px_rgba(0,0,0,0.6)]"><path fill-rule="evenodd" clip-rule="evenodd" d="M12 2l9 5v10l-9 5-9-5V7l9-5z M11 8h2v3h3v2h-3v3h-2v-3H8v-2h3V8z" /></svg>
                    <div class="leading-none"><div class="text-lg md:text-2xl font-black italic tracking-tighter">50 AÑOS</div><div class="text-[10px] font-bold uppercase tracking-widest opacity-80">DE HISTORIA</div></div>
                </div>
                <div class="flex items-center gap-3 text-white">
                    <svg viewBox="0 0 24 24" fill="currentColor" class="w-10 h-10 md:w-12 md:h-12 shrink-0 drop-shadow-[0_2px_6px_rgba(0,0,0,0.6)]"><path fill-rule="evenodd" clip-rule="evenodd" d="M12 2l9 5v10l-9 5-9-5V7l9-5z M11 8h2v3h3v2h-3v3h-2v-3H8v-2h3V8z" /></svg>
                    <div class="leading-none"><div class="text-lg md:text-2xl font-black italic tracking-tighter">25.000</div><div class="text-[10px] font-bold uppercase tracking-widest opacity-80">PRODUCTOS</div></div>
                </div>
                <div class="flex items-center gap-3 text-white">
                    <svg viewBox="0 0 24 24" fill="currentColor" class="w-10 h-10 md:w-12 md:h-12 shrink-0 drop-shadow-[0_2px_6px_rgba(0,0,0,0.6)]"><path fill-rule="evenodd" clip-rule="evenodd" d="M12 2l9 5v10l-9 5-9-5V7l9-5z M11 8h2v3h3v2h-3v3h-2v-3H8v-2h3V8z" /></svg>
                    <div class="leading-none"><div class="text-lg md:text-2xl font-black italic tracking-tighter">100</div><div class="text-[10px] font-bold uppercase tracking-widest opacity-80">MARCAS</div></div>
                </div>
                <div class="flex items-center gap-3 text-white">
                    <svg viewBox="0 0 24 24" fill="currentColor" class="w-10 h-10 md:w-12 md:h-12 shrink-0 drop-shadow-[0_2px_6px_rgba(0,0,0,0.6)]"><path d="M12 2l9 5v10l-9 5-9-5V7l9-5z" /></svg>
                    <div class="leading-none"><div class="text-lg md:text-2xl font-black italic tracking-tighter">3 UNIDADES</div><div class="text-[10px] font-bold uppercase tracking-widest opacity-80">DE NEGOCIO</div></div>
                </div>
            </div>
        </div>
    </div>

    {{-- Historia — técnico y ordenado --}}
    <div class="max-w-7xl mx-auto px-4 md:px-6 py-10 md:py-14">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 lg:gap-8 items-start">
            
            {{-- imagen con offset dual-ink --}}
            <div class="lg:col-span-5 relative">
                <div class="absolute -inset-3 bg-[#1b365d] rounded-2xl opacity-10 rotate-1"></div>
                <div class="absolute -inset-3 bg-[#a6282e] rounded-2xl opacity-10 -rotate-1"></div>
                <div class="relative bg-white rounded-2xl overflow-hidden shadow-xl border border-gray-100">
                    <img src="{{ asset('themes/encendidorqta/equipo_frente.jpg') }}" alt="Equipo en el frente" class="w-full h-auto object-cover">
                    <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/75 to-transparent p-4">
                        <p class="text-white text-xs font-black uppercase tracking-widest flex items-center gap-2">
                            <span class="w-6 h-0.5 bg-white rounded-full"></span> Segunda generación al frente
                        </p>
                    </div>
                </div>
                {{-- badge desde 1975 --}}
                <div class="absolute -bottom-4 -right-2 md:-right-4 bg-white rounded-2xl shadow-lg border border-gray-100 px-4 py-3 flex items-center gap-3">
                    <img src="{{ asset('themes/encendidorqta/desde1975.png') }}" alt="Desde 1975" class="h-10 w-auto object-contain">
                    <div class="leading-tight">
                        <div class="text-xs font-black uppercase tracking-widest text-gray-900">Legado familiar</div>
                        <div class="text-[11px] font-bold text-gray-500">Lealtad · Honestidad · Ética</div>
                    </div>
                </div>
            </div>

            {{-- texto --}}
            <div class="lg:col-span-7 lg:pl-6 pt-8 lg:pt-2">
                <div class="inline-flex items-center gap-2 bg-white rounded-full border border-gray-200 px-3 py-1.5 shadow-sm">
                    <span class="w-2 h-2 bg-[#a6282e] rounded-full animate-pulse"></span>
                    <span class="text-[11px] font-black uppercase tracking-widest text-gray-700">Un legado familiar desde 1975</span>
                </div>

                <h2 class="mt-5 text-3xl md:text-4xl font-black uppercase tracking-tighter text-gray-900 leading-none">
                    Fundada por <span class="text-[#a6282e]">Pablo</span> y <span class="text-[#1b365d]">Ángela</span>
                </h2>
                <p class="mt-4 text-base md:text-lg text-gray-600 leading-relaxed font-medium">
                    Lo que nació como proyecto de dedicación se convirtió en el centro integral de soluciones vehiculares más importante del norte de Santa Fe.
                    Hoy, la <span class="bg-gray-900 text-white px-1.5 py-0.5 rounded font-black">segunda generación</span> mantiene intactos los valores que nos dieron origen.
                </p>

                {{-- timeline compacto con hex --}}
                <div class="mt-8 relative">
                    <div class="absolute left-4 top-2 bottom-2 w-px bg-gray-300 hidden md:block"></div>
                    <div class="space-y-5">
                        @foreach($timeline as $step)
                            <div class="flex gap-4">
                                <div class="hidden md:flex w-8 h-8 shrink-0 items-center justify-center bg-white border border-gray-200 rounded-lg shadow-sm" style="clip-path: polygon(25% 0%, 75% 0%, 100% 50%, 75% 100%, 25% 100%, 0% 50%);">
                                    <span class="w-2 h-2 bg-gray-900 rounded-full"></span>
                                </div>
                                <div class="flex-1 bg-white rounded-2xl border border-gray-100 p-4 shadow-sm">
                                    <div class="text-[11px] font-black uppercase tracking-widest" style="color: {{ $loop->index === 0 ? '#a6282e' : ($loop->index === 1 ? '#8c8c8c' : '#1b365d') }}">{{ $step['year'] }}</div>
                                    <div class="text-sm font-black text-gray-900 mt-1">{{ $step['title'] }}</div>
                                    <div class="text-sm text-gray-600 leading-snug mt-1">{{ $step['text'] }}</div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- beneficios --}}
                <div class="mt-8 grid grid-cols-1 sm:grid-cols-3 gap-3">
                    @foreach($benefits as $b)
                        <div class="bg-white rounded-2xl border border-gray-100 p-4 shadow-sm hover:shadow-md transition-shadow">
                            <div class="w-9 h-9 rounded-xl bg-gray-900 text-white flex items-center justify-center">
                                <x-icon :name="$b['icon']" class="w-5 h-5" />
                            </div>
                            <div class="mt-3 text-sm font-black text-gray-900 leading-tight">{{ $b['title'] }}</div>
                            <div class="mt-1 text-xs text-gray-500 leading-snug">{{ $b['text'] }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- banner vans — ya existente pero integrado --}}
    <div class="max-w-7xl mx-auto px-4 md:px-6">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-3 md:p-4">
            <img src="{{ asset('themes/encendidorqta/banner_vans.png') }}" alt="Servicio a domicilio" class="w-full h-auto rounded-xl">
        </div>
    </div>

    {{-- Unidades — rounded-2xl cards como home product cards --}}
    <div class="max-w-7xl mx-auto px-4 md:px-6 py-10">
        <div class="flex items-center gap-3 mb-6">
            <div class="w-8 h-8 bg-gray-400 shrink-0 flex items-center justify-center" style="clip-path: polygon(25% 0%, 75% 0%, 100% 50%, 75% 100%, 25% 100%, 0% 50%);">
                <span class="w-2 h-2 bg-white rounded-full"></span>
            </div>
            <h2 class="text-xl md:text-2xl font-black uppercase tracking-wide text-gray-400">Nuestras unidades</h2>
            <span class="hidden md:inline text-xs font-bold text-gray-400 ml-3">Dos mostradores, un mismo estándar de servicio</span>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- ER --}}
            <div class="relative bg-white rounded-2xl shadow-md border border-gray-100 overflow-hidden hover:shadow-xl transition-all duration-300">
                <div class="absolute inset-0 opacity-[0.04] pointer-events-none" style="background-image: url('{{ asset('themes/encendidorqta/patron_hex2.png') }}'); background-size: cover; background-position: right center;"></div>
                <div class="absolute -right-10 -top-10 w-36 h-36 bg-[#a6282e]/10" style="clip-path: polygon(50% 0%, 100% 25%, 100% 75%, 50% 100%, 0% 75%, 0% 25%);"></div>
                <div class="relative p-6 md:p-8">
                    <div class="flex items-center justify-between">
                        <img src="{{ asset('themes/encendidorqta/erlogo.png') }}" alt="ER" class="h-10 w-auto object-contain grayscale opacity-60" style="filter: grayscale(1) brightness(0.35) contrast(1.05);">
                        <span class="text-[10px] font-black uppercase tracking-widest bg-[#a6282e] text-white px-3 py-1 rounded-full">Repuestos y electricidad</span>
                    </div>
                    <ul class="mt-6 space-y-3">
                        @foreach($erServices as $s)
                            <li class="flex items-start gap-3 text-sm font-medium text-gray-700">
                                <span class="mt-1 w-6 h-6 rounded-full bg-[#a6282e]/10 text-[#a6282e] flex items-center justify-center shrink-0"><x-icon name="o-check" class="w-3.5 h-3.5" /></span>
                                <span>{{ $s }}</span>
                            </li>
                        @endforeach
                    </ul>
                    <a href="/?store=er" class="mt-6 inline-flex items-center gap-2 bg-[#a6282e] hover:bg-[#8d2228] text-white px-5 py-2.5 rounded-full text-xs font-black uppercase tracking-widest shadow-md transition-colors">
                        Ver catálogo ER <x-icon name="o-arrow-right" class="w-4 h-4" />
                    </a>
                </div>
            </div>

            {{-- CIBAT --}}
            <div class="relative bg-white rounded-2xl shadow-md border border-gray-100 overflow-hidden hover:shadow-xl transition-all duration-300">
                <div class="absolute inset-0 opacity-[0.04] pointer-events-none grayscale" style="background-image: url('{{ asset('themes/encendidorqta/patron_hex2.png') }}'); background-size: cover; background-position: left center;"></div>
                <div class="absolute -right-10 -top-10 w-36 h-36 bg-[#1b365d]/10" style="clip-path: polygon(50% 0%, 100% 25%, 100% 75%, 50% 100%, 0% 75%, 0% 25%);"></div>
                <div class="relative p-6 md:p-8">
                    <div class="flex items-center justify-between">
                        <img src="{{ asset('themes/encendidorqta/cibatlogo.png') }}" alt="CIBAT" class="h-10 w-auto object-contain grayscale opacity-60" style="filter: grayscale(1) brightness(0.35) contrast(1.05);">
                        <span class="text-[10px] font-black uppercase tracking-widest bg-[#1b365d] text-white px-3 py-1 rounded-full">Baterías y cerrajería</span>
                    </div>
                    <ul class="mt-6 space-y-3">
                        @foreach($cibatServices as $s)
                            <li class="flex items-start gap-3 text-sm font-medium text-gray-700">
                                <span class="mt-1 w-6 h-6 rounded-full bg-[#1b365d]/10 text-[#1b365d] flex items-center justify-center shrink-0"><x-icon name="o-check" class="w-3.5 h-3.5" /></span>
                                <span>{{ $s }}</span>
                            </li>
                        @endforeach
                    </ul>
                    <a href="/?store=cibat" class="mt-6 inline-flex items-center gap-2 bg-[#1b365d] hover:bg-[#162d4d] text-white px-5 py-2.5 rounded-full text-xs font-black uppercase tracking-widest shadow-md transition-colors">
                        Ver catálogo CIBAT <x-icon name="o-arrow-right" class="w-4 h-4" />
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- Cierre instalaciones — oscuro con interior + piedra --}}
    <div class="relative mt-6 overflow-hidden bg-gray-900">
        <img src="{{ asset('themes/encendidorqta/local_interior.jpg') }}" alt="Interior" class="absolute inset-0 w-full h-full object-cover opacity-20">
        <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/20 to-transparent"></div>

        <div class="relative max-w-7xl mx-auto px-6 py-12 md:py-16">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-center">
                <div class="lg:col-span-7">
                    <div class="inline-flex items-center gap-2 bg-white/10 backdrop-blur rounded-full px-3 py-1.5 border border-white/10">
                        <span class="w-2 h-2 bg-white rounded-full"></span>
                        <span class="text-[11px] font-black uppercase tracking-widest text-white">Nuevo y amplio espacio</span>
                    </div>
                    <h2 class="mt-4 text-3xl md:text-4xl font-black uppercase tracking-tighter text-white leading-none">
                        Te esperamos en <br/> nuestras instalaciones
                    </h2>
                    <p class="mt-4 text-base text-white/80 leading-relaxed max-w-xl">
                        Mayor comodidad, más stock y la calidad de servicio que nos caracteriza. Estamos en Moreno 1531 y 1541, Reconquista.
                    </p>
                    <div class="mt-6 flex flex-col sm:flex-row gap-3">
                        <a href="https://maps.app.goo.gl/t4Vzx1ZCed91sBJ38" target="_blank" class="inline-flex items-center justify-center gap-2 bg-white text-gray-900 px-6 py-3 rounded-full text-xs font-black uppercase tracking-widest shadow-lg hover:bg-gray-100 transition-colors">
                            <x-icon name="o-map-pin" class="w-4 h-4" /> Cómo llegar
                        </a>
                        <a href="/" class="inline-flex items-center justify-center gap-2 bg-white/10 backdrop-blur text-white border border-white/20 px-6 py-3 rounded-full text-xs font-black uppercase tracking-widest hover:bg-white hover:text-gray-900 transition-colors">
                            <x-icon name="o-shopping-bag" class="w-4 h-4" /> Ir al catálogo
                        </a>
                    </div>
                </div>

                <div class="lg:col-span-5 grid grid-cols-2 gap-3">
                    <a href="https://wa.me/5493482415931" target="_blank" class="bg-[#a6282e] hover:bg-[#8d2228] text-white rounded-2xl p-5 shadow-xl hover:shadow-2xl hover:-translate-y-0.5 transition-all">
                        <div class="w-10 h-10 rounded-xl bg-white/15 flex items-center justify-center"><x-icon name="o-chat-bubble-oval-left-ellipsis" class="w-6 h-6" /></div>
                        <div class="mt-4 text-xs font-black uppercase tracking-widest opacity-80">WhatsApp ER</div>
                        <div class="mt-1 text-sm font-black">3482 415931</div>
                        <div class="mt-3 inline-flex items-center gap-1 text-xs font-bold uppercase tracking-wide opacity-90">Escribir <x-icon name="o-arrow-right" class="w-3.5 h-3.5" /></div>
                    </a>
                    <a href="https://wa.me/5493482534205" target="_blank" class="bg-[#1b365d] hover:bg-[#162d4d] text-white rounded-2xl p-5 shadow-xl hover:shadow-2xl hover:-translate-y-0.5 transition-all">
                        <div class="w-10 h-10 rounded-xl bg-white/15 flex items-center justify-center"><x-icon name="o-chat-bubble-oval-left-ellipsis" class="w-6 h-6" /></div>
                        <div class="mt-4 text-xs font-black uppercase tracking-widest opacity-80">WhatsApp CIBAT</div>
                        <div class="mt-1 text-sm font-black">3482 534205</div>
                        <div class="mt-3 inline-flex items-center gap-1 text-xs font-bold uppercase tracking-wide opacity-90">Escribir <x-icon name="o-arrow-right" class="w-3.5 h-3.5" /></div>
                    </a>
                    <div class="col-span-2 bg-[#1a1a1a] rounded-2xl p-4 shadow-xl border border-white/10 flex items-center gap-4">
                        <img src="{{ asset('themes/encendidorqta/erlogo.png') }}" class="h-8 w-auto object-contain grayscale brightness-0 invert opacity-80" alt="ER" style="filter: grayscale(1) brightness(0) invert(1) opacity(0.8);">
                        <div class="w-px h-8 bg-white/15"></div>
                        <img src="{{ asset('themes/encendidorqta/cibatlogo.png') }}" class="h-7 w-auto object-contain grayscale brightness-0 invert opacity-80" alt="CIBAT" style="filter: grayscale(1) brightness(0) invert(1) opacity(0.8);">
                        <div class="ml-auto text-right leading-tight">
                            <div class="text-xs font-black uppercase tracking-widest text-white">50 años</div>
                            <div class="text-[11px] font-bold text-white/60">potenciando tu negocio</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
