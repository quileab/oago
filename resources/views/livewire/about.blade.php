<?php
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;

new
    #[Layout('components.layouts.clean')]
    #[Title('Sobre Nosotros - Agostini Distribuidor')]
    class extends Component {
    //
}; ?>

<div class="bg-white dark:bg-gray-950 min-h-screen">
    {{-- Hero Section --}}
    <div class="relative h-64 md:h-96 overflow-hidden flex items-center justify-center bg-gray-900">
        <img src="{{ asset('imgs/brand.webp') }}" class="absolute inset-0 w-full h-full object-cover opacity-40 dark:opacity-30 blur-xs" alt="Distribuidora Agostini">
        <div class="absolute inset-0 bg-linear-to-t from-gray-900/80 to-transparent"></div>
        <div class="relative text-center px-4">
            <x-header title="Nuestra Historia" subtitle="Desde 1974, endulzando la vida de los santafesinos" size="text-4xl md:text-6xl" class="text-white font-black drop-shadow-[0_2px_10px_rgba(0,0,0,0.5)]" />
        </div>
    </div>

    <div class="max-w-6xl mx-auto px-6 py-12 space-y-24">
        
        {{-- Intro Section con Stats --}}
        <section class="grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">
            <div class="space-y-8">
                <h2 class="text-4xl font-extrabold text-gray-900 dark:text-white tracking-tight">Quiénes Somos</h2>
                <div class="space-y-4 text-xl leading-relaxed text-gray-800 dark:text-gray-200">
                    <p>
                        ¡Hola buena gente! Mi nombre es <span class="font-bold text-red-700 dark:text-red-500">Osvaldo Agostini</span> y quiero contarles cómo nació esta gran empresa familiar. 
                    </p>
                    <p>
                        Acompañado por mi familia y un equipo de profesionales, hemos transformado un sueño de juventud en uno de los centros de distribución de golosinas más importantes de la región.
                    </p>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 mt-10">
                    <x-stat title="Años de Historia" value="50+" icon="o-calendar" class="bg-gray-50 dark:bg-gray-900 shadow-md border-l-8 border-red-700 text-gray-900 dark:text-white" />
                    <x-stat title="Superficie" value="4200m²" icon="o-building-office" class="bg-gray-50 dark:bg-gray-900 shadow-md border-l-8 border-red-700 text-gray-900 dark:text-white" />
                </div>
            </div>
            <div class="relative group">
                <div class="absolute -inset-4 bg-red-700/10 rounded-3xl rotate-3 group-hover:rotate-0 transition-transform duration-500"></div>
                <div class="relative rounded-2xl overflow-hidden shadow-2xl rotate-3 group-hover:rotate-0 transition-transform duration-500">
                    <img id="logo" loading="lazy" src="{{ asset('imgs/WebStoreBrand.webp') }}" class="w-full h-full object-cover" alt="Osvaldo Agostini">
                </div>
            </div>
        </section>

        {{-- Timeline Visual --}}
        <section class="space-y-16">
            <div class="text-center space-y-4">
                <h2 class="text-4xl font-extrabold text-gray-900 dark:text-white">Nuestra Evolución</h2>
                <div class="w-24 h-1 bg-red-700 mx-auto rounded-full"></div>
            </div>

            <div class="relative border-l-4 border-red-700 ml-4 md:ml-0 md:border-l-0 md:grid md:grid-cols-3 md:gap-12">
                {{-- Hito 1 --}}
                <div class="relative pb-16 md:pb-0 md:text-center px-8 group">
                    <div class="absolute -left-[22px] md:left-1/2 md:-translate-x-1/2 top-0 bg-red-700 text-white w-10 h-10 rounded-full flex items-center justify-center font-bold shadow-xl z-10">1</div>
                    <h3 class="font-black text-2xl mt-8 text-red-700 dark:text-red-500">1974</h3>
                    <p class="font-bold text-gray-900 dark:text-white mb-2">El Origen</p>
                    <p class="text-gray-800 dark:text-gray-300 text-lg leading-snug">Iniciamos con un garage en calle San Martin al 5300, vendiendo golosinas y juguetes con el apoyo incondicional de la familia.</p>
                </div>
                {{-- Hito 2 --}}
                <div class="relative pb-16 md:pb-0 md:text-center px-8 group">
                    <div class="absolute -left-[22px] md:left-1/2 md:-translate-x-1/2 top-0 bg-red-700 text-white w-10 h-10 rounded-full flex items-center justify-center font-bold shadow-xl z-10">2</div>
                    <h3 class="font-black text-2xl mt-8 text-red-700 dark:text-red-500">Crecimiento</h3>
                    <p class="font-bold text-gray-900 dark:text-white mb-2">La Casa Propia</p>
                    <p class="text-gray-800 dark:text-gray-300 text-lg leading-snug">Construimos nuestro local de 450m² en Pedro de Vega al 3100, haciendo realidad el sueño de consolidar nuestro propio espacio.</p>
                </div>
                {{-- Hito 3 --}}
                <div class="relative md:text-center px-8 group">
                    <div class="absolute -left-[22px] md:left-1/2 md:-translate-x-1/2 top-0 bg-red-700 text-white w-10 h-10 rounded-full flex items-center justify-center font-bold shadow-xl z-10">3</div>
                    <h3 class="font-black text-2xl mt-8 text-red-700 dark:text-red-500">Actualidad</h3>
                    <p class="font-bold text-gray-900 dark:text-white mb-2">Avenida Gorriti</p>
                    <p class="text-gray-800 dark:text-gray-300 text-lg leading-snug">Hoy operamos en dos naves de 2100m² cada una, atendiendo a miles de clientes con la misma esencia y humildad.</p>
                </div>
            </div>
        </section>

        {{-- Valores Section --}}
        <section class="bg-red-700 rounded-[40px] p-10 md:p-20 text-white shadow-2xl relative overflow-hidden">
            <div class="absolute top-0 right-0 w-64 h-64 bg-white/5 rounded-full -mr-32 -mt-32"></div>
            <div class="relative max-w-4xl mx-auto text-center space-y-12">
                <h2 class="text-4xl font-black">Nuestros Cimientos</h2>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-10 py-4">
                    <div class="flex flex-col items-center space-y-4 group">
                        <div class="p-5 bg-white/10 rounded-2xl group-hover:bg-white/20 transition-colors">
                            <x-icon name="o-shield-check" class="w-12 h-12 text-white" />
                        </div>
                        <span class="font-bold text-xl uppercase tracking-wider">Respeto</span>
                    </div>
                    <div class="flex flex-col items-center space-y-4 group">
                        <div class="p-5 bg-white/10 rounded-2xl group-hover:bg-white/20 transition-colors">
                            <x-icon name="o-briefcase" class="w-12 h-12 text-white" />
                        </div>
                        <span class="font-bold text-xl uppercase tracking-wider">Seriedad</span>
                    </div>
                    <div class="flex flex-col items-center space-y-4 group">
                        <div class="p-5 bg-white/10 rounded-2xl group-hover:bg-white/20 transition-colors">
                            <x-icon name="o-clock" class="w-12 h-12 text-white" />
                        </div>
                        <span class="font-bold text-xl uppercase tracking-wider">Cumplimiento</span>
                    </div>
                    <div class="flex flex-col items-center space-y-4 group">
                        <div class="p-5 bg-white/10 rounded-2xl group-hover:bg-white/20 transition-colors">
                            <x-icon name="o-heart" class="w-12 h-12 text-white" />
                        </div>
                        <span class="font-bold text-xl uppercase tracking-wider">Trabajo</span>
                    </div>
                </div>
                <blockquote class="text-2xl md:text-3xl font-medium italic text-red-50 border-l-4 border-white/30 pl-8 text-left">
                    "Aquí todo se hace con el corazón, logrando de esta forma, diferenciarnos de los demás."
                </blockquote>
            </div>
        </section>

        {{-- Clientes y Proveedores --}}
        <section class="grid grid-cols-1 md:grid-cols-2 gap-10">
            <div class="bg-gray-50 dark:bg-gray-900 p-10 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-800">
                <div class="flex items-center gap-4 mb-6">
                    <x-icon name="o-hand-raised" class="w-10 h-10 text-red-700" />
                    <h3 class="text-2xl font-black text-gray-900 dark:text-white uppercase">Proveedores</h3>
                </div>
                <p class="text-xl text-gray-800 dark:text-gray-300 leading-relaxed">
                    Son una parte importante en esta historia, porque supieron confiar no solo sus productos, sino que escucharon sugerencias para beneficio mutuo.
                </p>
            </div>
            <div class="bg-gray-50 dark:bg-gray-900 p-10 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-800">
                <div class="flex items-center gap-4 mb-6">
                    <x-icon name="o-user-group" class="w-10 h-10 text-red-700" />
                    <h3 class="text-2xl font-black text-gray-900 dark:text-white uppercase">Clientes</h3>
                </div>
                <p class="text-xl text-gray-800 dark:text-gray-300 leading-relaxed">
                    Nuestro capital más preciado. Gracias por permitirnos ingresar en sus negocios y en sus vidas, logrando un acompañamiento mutuo.
                </p>
            </div>
        </section>

        {{-- Mensaje Final --}}
        <section class="text-center py-20 bg-linear-to-b from-transparent to-gray-50 dark:to-gray-900/50 rounded-[40px]">
            <div class="max-w-3xl mx-auto space-y-10 px-4">
                <h3 class="text-3xl font-black text-gray-900 dark:text-white">Un sueño hecho realidad</h3>
                <p class="text-2xl text-gray-800 dark:text-gray-200 leading-relaxed italic font-serif">
                    "Siento que hemos sido bendecidos por Dios al encontrarnos donde hoy estamos, siempre con la misma esencia y la humildad que llevamos como carta de presentación."
                </p>
                <div class="space-y-2">
                    <p class="text-3xl font-black text-red-700 dark:text-red-500">Osvaldo Agostini</p>
                    <p class="text-gray-600 dark:text-gray-400 font-bold uppercase tracking-[0.3em] text-sm">Fundador</p>
                </div>
                <div class="pt-10">
                    <x-button label="Explorar Catálogo" icon="o-shopping-bag" link="/" class="btn-lg bg-red-700 border-none hover:bg-red-800 text-white font-bold px-12 rounded-2xl shadow-xl" />
                </div>
            </div>
        </section>
    </div>
</div>
