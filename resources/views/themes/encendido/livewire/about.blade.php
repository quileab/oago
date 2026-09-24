<?php
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;

new #[Layout('components.layouts.clean')] #[Title('Sobre Nosotros - Encendido Reconquista')] class extends Component {
    public array $benefits = [
        [
            'title' => '+50 años',
            'text' => 'Respaldados por décadas de experiencia',
            'icon' => 'o-trophy'
        ],
        [
            'title' => 'Familiar',
            'text' => 'Atención con valores humanos',
            'icon' => 'o-users'
        ],
        [
            'title' => 'Especialistas',
            'text' => 'Técnicos altamente capacitados',
            'icon' => 'o-wrench-screwdriver'
        ],
        [
            'title' => 'Referentes',
            'text' => 'La primera opción en el norte de Santa Fe',
            'icon' => 'o-map-pin'
        ],
        [
            'title' => 'Honestidad',
            'text' => 'Presupuestos sin sorpresas',
            'icon' => 'o-hand-thumb-up'
        ],
    ];

    public array $erServices = [
        'Arranques y alternadores',
        'Instalaciones eléctricas',
        'Bobinas, distribuidores y bujías',
        'Módulos y captores electrónicos',
        'Ópticas y faros para autos y camiones',
        'Carburación completa',
        'Compresores y calefactores',
        'Accesorios: balizas, matafuegos y estética vehicular'
    ];

    public array $cibatServices = [
        'Baterías para todo tipo de vehículos, línea pesada y motos',
        'Baterías para sistemas fotovoltaicos (litio, VRLA y estacionarias)',
        'Llaves codificadas para todas las marcas y modelos',
        'Telemandos y controles remotos originales y alternativos',
        'Reparación y accesorios'
    ];

    public array $testimonials = [
        [
            'text' => '"Llevo más de 20 años comprando en Encendido Reconquista. Siempre me asesoraron bien y los repuestos son de primera calidad."',
            'author' => 'Cliente frecuente, Reconquista'
        ],
        [
            'text' => '"En CIBAT me hicieron la llave codificada de mi auto en el momento. Rápido, eficiente y a buen precio."',
            'author' => 'Cliente satisfecho, zona norte'
        ]
    ];
}; ?>

<div class="bg-gray-50 min-h-screen text-gray-800 font-sans overflow-hidden">
    <!-- Navbar Link (Volver) -->
    <div class="fixed top-0 left-0 w-full z-50 p-6 flex justify-between items-center pointer-events-none">
        <a href="/" class="btn btn-sm btn-circle bg-white text-[#e60000] border-gray-200 shadow-md hover:bg-[#e60000] hover:text-white pointer-events-auto transition-all duration-300">
            <x-icon name="o-arrow-left" class="w-5 h-5" />
        </a>
    </div>

    <!-- Hero Section -->
    <div class="relative w-full py-24 lg:py-32 flex flex-col items-center justify-center bg-white border-b border-gray-200 overflow-hidden">
        <!-- Background Hexagons Pattern -->
        <div class="absolute inset-0 opacity-5 pointer-events-none" style="background-image: url('data:image/svg+xml,%3Csvg width=\'60\' height=\'103.92304845413264\' viewBox=\'0 0 60 103.92304845413264\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cpath d=\'M30 103.92304845413264L0 86.60254037844388L0 51.96152422706632L30 34.64101615137755L60 51.96152422706632L60 86.60254037844388Z\' fill-opacity=\'0\' stroke=\'%23e60000\' stroke-width=\'1\'/%3E%3C/svg%3E');"></div>
        
        <div class="relative z-10 text-center px-4 max-w-4xl mx-auto flex flex-col items-center animate-fade-in-up">
            <div class="w-40 h-40 md:w-56 md:h-56 bg-white shadow-xl flex items-center justify-center p-6 mb-8 transform hover:scale-105 transition-transform duration-500" style="clip-path: polygon(50% 0%, 100% 25%, 100% 75%, 50% 100%, 0% 75%, 0% 25%);">
                <img id="hero-logo" src="{{ asset('imgs/Logos/ER50.png') }}" alt="Encendido Reconquista 50 Años" class="w-full h-auto object-contain" />
            </div>
            
            <h1 class="text-5xl md:text-7xl font-black uppercase tracking-tight text-gray-900 mb-6">
                50 Años de <span class="text-[#e60000]">Confianza</span>
            </h1>
            <p class="text-xl md:text-2xl text-gray-600 font-light max-w-2xl mx-auto">
                Repuestos y baterías para mantener tu vehículo en marcha hacia el futuro.
            </p>
        </div>
    </div>

    <div class="mx-auto max-w-7xl px-6 py-20 space-y-32 relative z-20">
        
        <!-- Section: Quiénes Somos -->
        <section class="grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">
            <div class="space-y-8">
                <div class="inline-flex items-center gap-3">
                    <div class="w-8 h-8 bg-[#e60000] text-white flex items-center justify-center" style="clip-path: polygon(50% 0%, 100% 25%, 100% 75%, 50% 100%, 0% 75%, 0% 25%);">
                        <x-icon name="o-information-circle" class="w-4 h-4" />
                    </div>
                    <h2 class="text-4xl font-extrabold tracking-tight text-gray-900 uppercase">Quiénes Somos</h2>
                </div>
                <div class="space-y-6 text-lg text-gray-600 leading-relaxed font-light">
                    <p>
                        Fundada en 1975 por Pablo Zancada y Ángela Weidmann, somos una empresa familiar que ha crecido junto a la comunidad de Reconquista durante más de cinco décadas. 
                    </p>
                    <p>
                        Hoy, con la segunda generación al frente, mantenemos los mismos valores que nos dieron origen: <span class="font-bold text-[#e60000]">lealtad, honestidad y ética comercial</span> en cada atención. Contamos con tres unidades de negocio especializadas, siendo referentes en soluciones vehiculares para toda la zona norte de la provincia de Santa Fe.
                    </p>
                </div>
            </div>

            <!-- Hexagon Grid for Benefits -->
            <div class="flex flex-wrap justify-center gap-6">
                 @foreach ($benefits as $index => $benefit)
                    <div class="w-36 h-40 md:w-44 md:h-48 bg-white shadow-lg flex flex-col items-center justify-center text-center p-4 hover:-translate-y-2 transition-transform duration-300 {{ $index % 2 == 0 ? 'mt-8' : '' }}" style="clip-path: polygon(50% 0%, 100% 25%, 100% 75%, 50% 100%, 0% 75%, 0% 25%); border: 2px solid #f3f4f6;">
                        <x-icon :name="$benefit['icon']" class="w-8 h-8 text-[#e60000] mb-2" />
                        <span class="text-sm md:text-base font-bold text-gray-800 leading-tight">{{ $benefit['title'] }}</span>
                    </div>
                 @endforeach
            </div>
        </section>

        <!-- Section: Servicios -->
        <section class="space-y-16">
            <div class="text-center space-y-4">
                <h2 class="text-4xl font-extrabold tracking-tight text-gray-900 uppercase">Nuestras Unidades</h2>
                <div class="w-24 h-1 bg-[#e60000] mx-auto rounded-full"></div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-12 lg:gap-20">
                <!-- ER Services -->
                <div class="bg-white rounded-3xl p-8 md:p-12 shadow-xl border-t-8 border-[#e60000] relative overflow-hidden group hover:shadow-2xl transition-shadow">
                    <div class="absolute -right-10 -top-10 w-40 h-40 bg-[#e60000]/10 rounded-full blur-2xl group-hover:bg-[#e60000]/20 transition-colors"></div>
                    <div class="flex items-center gap-6 mb-8">
                        <div class="w-20 h-20 bg-gray-100 flex items-center justify-center shadow-sm" style="clip-path: polygon(50% 0%, 100% 25%, 100% 75%, 50% 100%, 0% 75%, 0% 25%);">
                            <img src="{{ asset('imgs/Logos/ER50.png') }}" class="h-10 w-auto object-contain" alt="ER">
                        </div>
                        <h3 class="text-2xl font-black text-gray-900">Encendido<br><span class="text-[#e60000]">Reconquista</span></h3>
                    </div>
                    
                    <ul class="space-y-4">
                        @foreach ($erServices as $service)
                        <li class="flex items-start gap-3 text-gray-700">
                            <x-icon name="o-check-circle" class="w-5 h-5 text-[#e60000] shrink-0 mt-0.5" />
                            <span class="font-medium">{{ $service }}</span>
                        </li>
                        @endforeach
                    </ul>
                </div>

                <!-- CIBAT Services -->
                <div class="bg-white rounded-3xl p-8 md:p-12 shadow-xl border-t-8 border-[#002b6b] relative overflow-hidden group hover:shadow-2xl transition-shadow">
                    <div class="absolute -right-10 -top-10 w-40 h-40 bg-[#002b6b]/10 rounded-full blur-2xl group-hover:bg-[#002b6b]/20 transition-colors"></div>
                    <div class="flex items-center gap-6 mb-8">
                        <div class="w-20 h-20 bg-gray-100 flex items-center justify-center shadow-sm" style="clip-path: polygon(50% 0%, 100% 25%, 100% 75%, 50% 100%, 0% 75%, 0% 25%);">
                            <img src="{{ asset('imgs/Logos/logo_cibat.png') }}" class="h-10 w-auto object-contain" alt="CIBAT">
                        </div>
                        <h3 class="text-2xl font-black text-gray-900">CIBAT<br><span class="text-[#002b6b]">Baterías</span></h3>
                    </div>
                    
                    <ul class="space-y-4">
                        @foreach ($cibatServices as $service)
                        <li class="flex items-start gap-3 text-gray-700">
                            <x-icon name="o-check-circle" class="w-5 h-5 text-[#002b6b] shrink-0 mt-0.5" />
                            <span class="font-medium">{{ $service }}</span>
                        </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </section>

        <!-- Section: Testimonios -->
        <section class="bg-white rounded-3xl p-10 md:p-16 shadow-lg border border-gray-100">
            <div class="text-center mb-12">
                <h2 class="text-3xl md:text-4xl font-black tracking-tight text-gray-900 uppercase">Lo que dicen de nosotros</h2>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-10">
                @foreach ($testimonials as $index => $testimonio)
                    <div class="relative bg-gray-50 p-8 rounded-2xl border-l-4 {{ $index === 0 ? 'border-[#e60000]' : 'border-[#002b6b]' }}">
                        <div class="absolute -top-5 -left-5 w-12 h-12 flex items-center justify-center bg-white shadow-md text-gray-400" style="clip-path: polygon(50% 0%, 100% 25%, 100% 75%, 50% 100%, 0% 75%, 0% 25%);">
                            <x-icon name="o-chat-bubble-left-ellipsis" class="w-5 h-5 {{ $index === 0 ? 'text-[#e60000]' : 'text-[#002b6b]' }}" />
                        </div>
                        <p class="text-lg font-medium italic text-gray-700 leading-relaxed mt-2">
                            {{ $testimonio['text'] }}
                        </p>
                        <div class="mt-6 pt-4 border-t border-gray-200">
                            <p class="text-sm font-bold uppercase tracking-wide {{ $index === 0 ? 'text-[#e60000]' : 'text-[#002b6b]' }}">
                                {{ $testimonio['author'] }}
                            </p>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        <!-- CTA Final -->
        <section class="relative bg-gray-900 rounded-3xl overflow-hidden py-20 text-center shadow-2xl">
            <!-- Hexagon Overlay -->
            <div class="absolute inset-0 opacity-10" style="background-image: url('data:image/svg+xml,%3Csvg width=\'60\' height=\'103.92304845413264\' viewBox=\'0 0 60 103.92304845413264\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cpath d=\'M30 103.92304845413264L0 86.60254037844388L0 51.96152422706632L30 34.64101615137755L60 51.96152422706632L60 86.60254037844388Z\' fill-opacity=\'0\' stroke=\'%23ffffff\' stroke-width=\'1\'/%3E%3C/svg%3E');"></div>
            
            <div class="relative z-10 mx-auto max-w-3xl px-6">
                <h3 class="text-4xl md:text-5xl font-black tracking-tight text-white mb-6 uppercase">¿Tu vehículo necesita atención?</h3>
                <p class="text-xl text-gray-300 font-light mb-10">
                    Visitanos en nuestros locales de Moreno 1531 y 1541 en Reconquista, o escribinos por WhatsApp. Más de 50 años de experiencia nos respaldan.
                </p>
                <div class="flex flex-wrap justify-center gap-6">
                    <a href="https://wa.me/5493482415931" target="_blank" class="btn btn-lg bg-[#e60000] hover:bg-[#cc0000] text-white border-none rounded-full shadow-lg hover:shadow-xl hover:-translate-y-1 transition-all duration-300 font-bold px-8">
                        <x-icon name="o-chat-bubble-left-ellipsis" class="w-6 h-6 mr-2" /> ER: 3482-415931
                    </a>
                    <a href="https://wa.me/5493482534205" target="_blank" class="btn btn-lg bg-[#002b6b] hover:bg-[#001f4d] text-white border-none rounded-full shadow-lg hover:shadow-xl hover:-translate-y-1 transition-all duration-300 font-bold px-8">
                        <x-icon name="o-chat-bubble-left-ellipsis" class="w-6 h-6 mr-2" /> CIBAT: 3482-534205
                    </a>
                </div>
            </div>
        </section>
    </div>

    <!-- Simple Animations Script -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('opacity-100', 'translate-y-0');
                        entry.target.classList.remove('opacity-0', 'translate-y-10');
                    }
                });
            }, { threshold: 0.1 });

            document.querySelectorAll('section').forEach(el => {
                el.classList.add('transition-all', 'duration-1000', 'opacity-0', 'translate-y-10');
                observer.observe(el);
            });
        });
    </script>
</div>
