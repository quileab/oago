<?php
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;

new #[Layout('components.layouts.clean')] #[Title('Sobre Nosotros - Encendido Reconquista')] class extends Component {
    public array $benefits = [
        [
            'title' => '+50 años de trayectoria',
            'text' => 'Respaldados por décadas de experiencia y confianza de la comunidad',
            'icon' => 'o-trophy'
        ],
        [
            'title' => 'Empresa familiar',
            'text' => 'Atención personalizada con valores humanos en cada servicio',
            'icon' => 'o-users'
        ],
        [
            'title' => 'Especialistas en cada área',
            'text' => 'Tres unidades de negocio con técnicos especializados',
            'icon' => 'o-wrench-screwdriver'
        ],
        [
            'title' => 'Referentes en la zona norte',
            'text' => 'La primera opción para toda la región norte de Santa Fe',
            'icon' => 'o-map-pin'
        ],
        [
            'title' => 'Honestidad ante todo',
            'text' => 'Diagnósticos transparentes y presupuestos sin sorpresas',
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

<div class="bg-base-100 min-h-screen text-base-content overflow-hidden font-sans cyber-grid">
    <!-- Navbar Link (Volver) -->
    <div class="fixed top-0 left-0 w-full z-50 p-6 flex justify-between items-center pointer-events-none">
        <a href="/" class="btn btn-sm btn-circle btn-ghost pointer-events-auto bg-base-100/50 backdrop-blur-xl border border-primary/30 shadow-[0_0_15px_rgba(255,0,60,0.3)] hover:shadow-[0_0_25px_rgba(255,0,60,0.6)] transition-all duration-300">
            <x-icon name="o-arrow-left" class="w-5 h-5 text-primary" />
        </a>
    </div>

    <!-- Hero Section con Efectos Futuristas (como en index) -->
    <div class="relative flex h-[70vh] min-h-[500px] items-center justify-center overflow-hidden bg-transparent">
        <div class="absolute inset-0 bg-gradient-to-b from-transparent to-base-200 z-10"></div>
        
        <!-- Orbes de luz (igual que en index: primary e info) -->
        <div class="absolute top-[-20%] left-[-10%] w-[50%] h-[50%] rounded-full bg-primary/20 blur-[150px] animate-pulse pointer-events-none -z-10"></div>
        <div class="absolute bottom-[-10%] right-[-10%] w-[40%] h-[40%] rounded-full bg-info/20 blur-[120px] animate-pulse pointer-events-none -z-10" style="animation-delay: 1s;"></div>
        
        <div class="relative px-4 text-center z-20 flex flex-col items-center gsap-hero-content perspective-1000">
            <div class="relative group">
                <img id="hero-logo" src="{{ asset('imgs/Logos/ER50.png') }}" alt="Encendido Reconquista 50 Años" class="relative h-32 md:h-48 logo-glow mb-8 drop-shadow-[0_0_25px_rgba(255,0,60,0.6)] transform-gpu hover:scale-105 transition-transform duration-500" />
            </div>
            
            <h1 class="text-5xl md:text-7xl font-black uppercase tracking-tighter text-transparent bg-clip-text bg-gradient-to-br from-white via-neutral-content to-gray-500 drop-shadow-[0_0_20px_rgba(255,255,255,0.2)]">
                50 Años de <span class="text-transparent bg-clip-text bg-gradient-to-r from-primary to-info drop-shadow-[0_0_20px_rgba(255,0,60,0.5)]">Confianza</span>
            </h1>
            <p class="mt-6 text-lg md:text-2xl text-neutral-content max-w-2xl font-light tracking-wide">
                Repuestos y baterías para mantener tu vehículo en marcha hacia el futuro.
            </p>
        </div>
    </div>

    <div class="mx-auto max-w-7xl space-y-32 px-6 py-24 relative z-20">
        <!-- Section: Quiénes Somos -->
        <section class="grid grid-cols-1 items-center gap-16 lg:grid-cols-2 gsap-fade-up">
            <div class="space-y-8 glass-panel p-10 rounded-3xl border-l-4 border-primary">
                <h2 class="text-4xl font-extrabold tracking-tight bg-clip-text text-transparent bg-gradient-to-r from-white to-gray-400">Quiénes Somos</h2>
                <div class="space-y-6 text-lg leading-relaxed text-base-content/80 font-light">
                    <p>
                        Fundada en 1975 por Pablo Zancada y Ángela Weidmann, somos una empresa familiar que ha crecido junto a la comunidad de Reconquista durante más de cinco décadas. 
                    </p>
                    <p>
                        Hoy, con la segunda generación al frente, mantenemos los mismos valores que nos dieron origen: <span class="font-bold text-primary drop-shadow-[0_0_8px_rgba(255,0,60,0.5)]">lealtad, honestidad y ética comercial</span> en cada atención. Contamos con tres unidades de negocio especializadas, siendo referentes en soluciones vehiculares para toda la zona norte de la provincia de Santa Fe.
                    </p>
                </div>
            </div>

            <div class="grid gap-6 grid-cols-1 sm:grid-cols-2">
                 @foreach (array_slice($benefits, 0, 4) as $benefit)
                    <div class="glow-card glass-panel p-6 rounded-2xl flex flex-col gap-3 group">
                        <div class="w-12 h-12 rounded-full bg-primary/10 flex items-center justify-center group-hover:scale-110 group-hover:bg-primary/20 transition-all duration-300">
                            <x-icon :name="$benefit['icon']" class="w-6 h-6 text-primary" />
                        </div>
                        <span class="text-lg font-bold tracking-tight text-white">{{ $benefit['title'] }}</span>
                        <span class="text-sm text-base-content/60">{{ $benefit['text'] }}</span>
                    </div>
                 @endforeach
            </div>
        </section>

        <!-- Section: Encendido Reconquista -->
        <section class="grid grid-cols-1 items-start gap-16 lg:grid-cols-2 gsap-fade-up">
            <div class="group relative order-2 lg:order-1 perspective-1000">
                <div class="absolute -inset-4 rounded-3xl bg-gradient-to-br from-primary/30 to-transparent blur-2xl opacity-50 group-hover:opacity-80 transition-opacity duration-500 pointer-events-none -z-10"></div>
                <div class="relative glass-panel rounded-3xl p-12 flex flex-col items-center transform-gpu transition-all duration-500 hover:rotate-y-6 hover:-rotate-x-6 hover:shadow-[0_20px_50px_rgba(255,0,60,0.2)]">
                    <div class="relative mb-8">
                        <div class="absolute inset-0 bg-white/20 blur-xl rounded-full"></div>
                        <img loading="lazy" src="{{ asset('imgs/Logos/ER50.png') }}" class="relative h-32 object-contain logo-glow" alt="Encendido Reconquista">
                    </div>
                    <div class="w-full space-y-3 bg-base-300/30 p-6 rounded-2xl border border-base-content/5 backdrop-blur-sm">
                        <p class="text-sm font-medium text-base-content/80 flex items-center gap-3">
                            <span class="p-2 rounded-full bg-primary/20 text-primary"><x-icon name="o-map-pin" class="w-4 h-4" /></span> 
                            Moreno 1531, Reconquista
                        </p>
                        <p class="text-sm font-medium text-base-content/80 flex items-center gap-3">
                            <span class="p-2 rounded-full bg-primary/20 text-primary"><x-icon name="o-phone" class="w-4 h-4" /></span> 
                            3482 25-3370
                        </p>
                    </div>
                </div>
            </div>

            <div class="space-y-8 order-1 lg:order-2">
                <div>
                    <h2 class="text-4xl font-extrabold tracking-tight text-white">Encendido Reconquista</h2>
                    <h3 class="text-xl font-medium text-primary mt-3 flex items-center gap-2">
                        <span class="w-8 h-px bg-primary shadow-[0_0_10px_rgba(255,0,60,0.8)]"></span>
                        Repuestos Eléctricos, de Encendido y Climatización
                    </h3>
                </div>
                <div class="space-y-6 text-lg leading-relaxed text-base-content/70 font-light">
                    <p>
                        En Encendido Reconquista encontrarás todo lo que tu vehículo necesita para funcionar de manera óptima. Trabajamos con repuestos de calidad para autos y camiones, con asesoramiento técnico especializado en cada consulta.
                    </p>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-8">
                    @foreach($erServices as $service)
                    <div class="flex items-start gap-3 p-3 rounded-lg bg-base-200/30 hover:bg-base-200/60 border border-transparent hover:border-primary/30 transition-all duration-300">
                        <x-icon name="o-check-circle" class="w-5 h-5 text-primary shrink-0 mt-0.5 drop-shadow-[0_0_5px_rgba(255,0,60,0.5)]" />
                        <span class="text-sm font-medium text-base-content/80">{{ $service }}</span>
                    </div>
                    @endforeach
                </div>
            </div>
        </section>

        <!-- Section: CIBAT -->
        <section class="grid grid-cols-1 items-start gap-16 lg:grid-cols-2 gsap-fade-up">
            <div class="space-y-8">
                <div>
                    <h2 class="text-4xl font-extrabold tracking-tight text-white">CIBAT</h2>
                    <h3 class="text-xl font-medium text-info mt-3 flex items-center gap-2">
                        <span class="w-8 h-px bg-info shadow-[0_0_10px_rgba(6,182,212,0.8)]"></span>
                        Centro Integral de Baterías
                    </h3>
                </div>
                <div class="space-y-6 text-lg leading-relaxed text-base-content/70 font-light">
                    <p>
                        CIBAT es un centro especializado en baterías y soluciones de acceso vehicular. Ofrecemos una amplia línea de productos para vehículos livianos, pesados, motos y sistemas de energía fotovoltaica, con atención personalizada y precios competitivos.
                    </p>
                </div>
                <div class="grid grid-cols-1 gap-4 mt-8">
                    @foreach($cibatServices as $service)
                    <div class="flex items-start gap-3 p-4 rounded-xl bg-gradient-to-r from-base-200/40 to-transparent border-l-2 border-info/50 hover:border-info hover:bg-base-200/80 transition-all duration-300">
                        <x-icon name="o-bolt" class="w-5 h-5 text-info shrink-0 mt-0.5 drop-shadow-[0_0_5px_rgba(6,182,212,0.5)]" />
                        <span class="text-sm font-medium text-base-content/90">{{ $service }}</span>
                    </div>
                    @endforeach
                </div>
            </div>

            <div class="group relative perspective-1000">
                <div class="absolute -inset-4 rounded-3xl bg-gradient-to-bl from-info/30 to-transparent blur-2xl opacity-50 group-hover:opacity-80 transition-opacity duration-500 pointer-events-none -z-10"></div>
                <div class="relative glass-panel rounded-3xl p-12 flex flex-col items-center transform-gpu transition-all duration-500 hover:-rotate-y-6 hover:rotate-x-6 hover:shadow-[0_20px_50px_rgba(6,182,212,0.2)]">
                    <div class="relative mb-8 w-full flex justify-center">
                        <div class="absolute inset-0 bg-white/10 blur-2xl rounded-full"></div>
                        <img id="logo-cibat" loading="lazy" src="{{ asset('imgs/Logos/logo_cibat.png') }}" class="relative h-24 sm:h-32 w-full max-w-sm object-contain drop-shadow-[0_0_15px_rgba(255,255,255,0.4)] brightness-0 invert opacity-90" alt="CIBAT Baterías">
                    </div>
                    <div class="w-full space-y-3 bg-base-300/30 p-6 rounded-2xl border border-base-content/5 backdrop-blur-sm">
                        <p class="text-sm font-medium text-base-content/80 flex items-center gap-3">
                            <span class="p-2 rounded-full bg-info/20 text-info"><x-icon name="o-map-pin" class="w-4 h-4" /></span> 
                            Moreno 1541, Reconquista
                        </p>
                        <p class="text-sm font-medium text-base-content/80 flex items-center gap-3">
                            <span class="p-2 rounded-full bg-info/20 text-info"><x-icon name="o-phone" class="w-4 h-4" /></span> 
                            3482 23-0488
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Section: Testimonios -->
        <section class="relative overflow-hidden rounded-[2.5rem] bg-base-200/80 p-10 text-neutral-content shadow-2xl md:p-20 border border-white/5 backdrop-blur-xl gsap-fade-up">
            <!-- Grids de fondo futurista -->
            <div class="absolute right-0 top-0 -mr-32 -mt-32 h-96 w-96 rounded-full bg-primary/20 blur-[100px] pointer-events-none -z-10"></div>
            <div class="absolute left-0 bottom-0 -ml-32 -mb-32 h-96 w-96 rounded-full bg-info/20 blur-[100px] pointer-events-none -z-10"></div>
            
            <div class="relative mx-auto max-w-5xl space-y-12 text-center z-10">
                <h2 class="text-3xl md:text-5xl font-black tracking-tight text-white drop-shadow-lg">Lo que dicen de nosotros</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-10 mt-16">
                    @foreach ($testimonials as $testimonio)
                        <div class="glow-card glass-panel p-10 rounded-3xl text-left relative group">
                            <div class="absolute -top-6 -left-6 w-16 h-16 rounded-full bg-primary/20 flex items-center justify-center backdrop-blur-md border border-primary/30 group-hover:scale-110 group-hover:bg-primary/40 transition-all duration-300 shadow-[0_0_15px_rgba(255,0,60,0.3)]">
                                <x-icon name="o-chat-bubble-left-ellipsis" class="w-8 h-8 text-primary drop-shadow-[0_0_5px_rgba(255,0,60,0.8)]" />
                            </div>
                            <p class="text-lg font-medium italic text-base-content/90 mt-4 leading-relaxed">
                                {{ $testimonio['text'] }}
                            </p>
                            <div class="mt-8 pt-6 border-t border-white/10">
                                <p class="text-xs font-black uppercase tracking-widest text-primary drop-shadow-[0_0_5px_rgba(255,0,60,0.5)]">
                                    {{ $testimonio['author'] }}
                                </p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        <!-- CTA Final -->
        <section class="relative rounded-3xl overflow-hidden py-24 text-center border border-primary/20 shadow-[0_0_50px_rgba(255,0,60,0.1)] gsap-fade-up glass-panel">
            <div class="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/carbon-fibre.png')] opacity-10 mix-blend-overlay pointer-events-none -z-10"></div>
            
            <div class="relative z-10 mx-auto max-w-3xl space-y-10 px-4">
                <h3 class="text-4xl md:text-5xl font-black tracking-tight text-transparent bg-clip-text bg-gradient-to-r from-white to-gray-300">¿Tu vehículo necesita atención?</h3>
                <p class="text-xl leading-relaxed text-base-content/70 font-light">
                    Visitanos en nuestros locales de Moreno 1531 y 1541 en Reconquista, o escribinos por WhatsApp. Más de 50 años de experiencia nos respaldan para brindarte la solución que necesitás.
                </p>
                <div class="pt-8 flex flex-wrap justify-center gap-6">
                    <a href="https://wa.me/5493482415931" target="_blank" class="btn btn-primary btn-lg rounded-full font-bold shadow-[0_0_20px_rgba(255,0,60,0.4)] hover:shadow-[0_0_30px_rgba(255,0,60,0.7)] hover:scale-105 transition-all duration-300 border-none">
                        <x-icon name="o-chat-bubble-left-ellipsis" class="w-6 h-6 mr-2" /> ER: 3482-415931
                    </a>
                    <a href="https://wa.me/5493482534205" target="_blank" class="btn btn-info btn-lg rounded-full font-bold shadow-[0_0_20px_rgba(6,182,212,0.4)] hover:shadow-[0_0_30px_rgba(6,182,212,0.7)] hover:scale-105 transition-all duration-300 border-none text-info-content">
                        <x-icon name="o-chat-bubble-left-ellipsis" class="w-6 h-6 mr-2" /> CIBAT: 3482-534205
                    </a>
                </div>
            </div>
        </section>
    </div>

    <!-- GSAP Scripts -->
    <script>
        document.addEventListener('DOMContentLoaded', (event) => {
            gsap.registerPlugin(ScrollTrigger);

            // Animación 3D del Logo Hero
            gsap.from('#hero-logo', {
                rotationY: 180,
                scale: 0.5,
                opacity: 0,
                duration: 2,
                ease: "elastic.out(1, 0.5)",
                delay: 0.3
            });

            gsap.to('#hero-logo', {
                y: -15,
                rotationX: 10,
                rotationY: -10,
                duration: 3,
                yoyo: true,
                repeat: -1,
                ease: "sine.inOut",
                delay: 2.3
            });

            // Animación del Hero
            gsap.from('.gsap-hero-content > *', {
                y: 50,
                opacity: 0,
                duration: 1.2,
                stagger: 0.2,
                ease: "power4.out",
                delay: 0.2
            });

            // Animación de los elementos al hacer scroll
            const fadeUpElements = gsap.utils.toArray('.gsap-fade-up');
            fadeUpElements.forEach((element) => {
                gsap.from(element, {
                    scrollTrigger: {
                        trigger: element,
                        start: "top 85%",
                        toggleActions: "play none none reverse"
                    },
                    y: 60,
                    opacity: 0,
                    duration: 1,
                    ease: "power3.out"
                });
            });
        });
    </script>
</div>
