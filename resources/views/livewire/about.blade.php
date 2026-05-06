<?php
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;

new #[Layout('components.layouts.clean')] #[Title('Sobre Nosotros - Agostini Distribuidor')] class extends Component {
    public array $stats = [
        ['title' => 'Años de Historia', 'value' => '50+', 'icon' => 'o-calendar'],
        ['title' => 'Superficie', 'value' => '4200m²', 'icon' => 'o-building-office'],
    ];

    public array $timeline = [
        ['number' => 1, 'label' => '1974', 'title' => 'El Origen', 'text' => 'Iniciamos con un garage en calle San Martin al 5300, vendiendo golosinas y juguetes con el apoyo incondicional de la familia.'],
        ['number' => 2, 'label' => 'Crecimiento', 'title' => 'La Casa Propia', 'text' => 'Construimos nuestro local de 450m² en Pedro de Vega al 3100, haciendo realidad el sueño de consolidar nuestro propio espacio.'],
        ['number' => 3, 'label' => 'Actualidad', 'title' => 'Avenida Gorriti', 'text' => 'Hoy operamos en dos naves de 2100m² cada una, atendiendo a miles de clientes con la misma esencia y humildad.'],
    ];

    public array $values = ['Respeto', 'Seriedad', 'Cumplimiento', 'Trabajo'];

    public array $valueIcons = [
        'Respeto' => 'o-shield-check',
        'Seriedad' => 'o-briefcase',
        'Cumplimiento' => 'o-clock',
        'Trabajo' => 'o-heart',
    ];

    public array $pillars = [
        [
            'icon' => 'o-hand-raised',
            'title' => 'Proveedores',
            'text' => 'Son una parte importante en esta historia, porque supieron confiar no solo sus productos, sino que escucharon sugerencias para beneficio mutuo.',
        ],
        [
            'icon' => 'o-user-group',
            'title' => 'Clientes',
            'text' => 'Nuestro capital más preciado. Gracias por permitirnos ingresar en sus negocios y en sus vidas, logrando un acompañamiento mutuo.',
        ],
    ];
}; ?>

<div class="bg-white min-h-screen dark:bg-gray-950">
    <div class="relative flex h-64 items-center justify-center overflow-hidden bg-gray-900 md:h-96">
        <img src="{{ asset('imgs/brand.webp') }}" class="absolute inset-0 h-full w-full object-cover opacity-40 blur-xs dark:opacity-30" alt="Distribuidora Agostini">
        <div class="absolute inset-0 bg-linear-to-t from-gray-900/80 to-transparent"></div>
        <div class="relative px-4 text-center">
            <x-header title="Nuestra Historia" subtitle="Desde 1974, endulzando la vida de los santafesinos" size="text-4xl md:text-6xl" class="text-white font-black drop-shadow-[0_2px_10px_rgba(0,0,0,0.5)]" />
        </div>
    </div>

    <div class="mx-auto max-w-6xl space-y-24 px-6 py-12">
        <section class="grid grid-cols-1 items-center gap-16 lg:grid-cols-2">
            <div class="space-y-8">
                <h2 class="text-4xl font-extrabold tracking-tight text-gray-900 dark:text-white">Quiénes Somos</h2>
                <div class="space-y-4 text-xl leading-relaxed text-gray-800 dark:text-gray-200">
                    <p>
                        ¡Hola buena gente! Mi nombre es <span class="font-bold text-red-700 dark:text-red-500">Osvaldo Agostini</span> y quiero contarles cómo nació esta gran empresa familiar.
                    </p>
                    <p>
                        Acompañado por mi familia y un equipo de profesionales, hemos transformado un sueño de juventud en uno de los centros de distribución de golosinas más importantes de la región.
                    </p>
                </div>
                <div class="mt-10 grid grid-cols-1 gap-6 sm:grid-cols-2">
                    @foreach ($stats as $stat)
                        <x-stat :title="$stat['title']" :value="$stat['value']" :icon="$stat['icon']" class="border-l-8 border-red-700 bg-gray-50 text-gray-900 shadow-md dark:bg-gray-900 dark:text-white" />
                    @endforeach
                </div>
            </div>

            <div class="group relative">
                <div class="absolute -inset-4 rounded-3xl bg-red-700/10 rotate-3 transition-transform duration-500 group-hover:rotate-0"></div>
                <div class="relative overflow-hidden rounded-2xl shadow-2xl rotate-3 transition-transform duration-500 group-hover:rotate-0">
                    <img id="logo" loading="lazy" src="{{ asset('imgs/WebStoreBrand.webp') }}" class="h-full w-full object-cover" alt="Osvaldo Agostini">
                </div>
            </div>
        </section>

        <section class="space-y-16">
            <div class="space-y-4 text-center">
                <h2 class="text-4xl font-extrabold text-gray-900 dark:text-white">Nuestra Evolución</h2>
                <div class="mx-auto h-1 w-24 rounded-full bg-red-700"></div>
            </div>

            <div class="relative ml-4 border-l-4 border-red-700 md:ml-0 md:grid md:grid-cols-3 md:gap-12 md:border-l-0">
                @foreach ($timeline as $step)
                    <div class="group relative px-8 {{ $step['number'] < count($timeline) ? 'pb-16 md:pb-0' : '' }} md:text-center">
                        <div class="absolute -left-[22px] top-0 z-10 flex h-10 w-10 items-center justify-center rounded-full bg-red-700 font-bold text-white shadow-xl md:left-1/2 md:-translate-x-1/2">{{ $step['number'] }}</div>
                        <h3 class="mt-8 text-2xl font-black text-red-700 dark:text-red-500">{{ $step['label'] }}</h3>
                        <p class="mb-2 font-bold text-gray-900 dark:text-white">{{ $step['title'] }}</p>
                        <p class="text-lg leading-snug text-gray-800 dark:text-gray-300">{{ $step['text'] }}</p>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="relative overflow-hidden rounded-[40px] bg-red-700 p-10 text-white shadow-2xl md:p-20">
            <div class="absolute right-0 top-0 -mr-32 -mt-32 h-64 w-64 rounded-full bg-white/5"></div>
            <div class="relative mx-auto max-w-4xl space-y-12 text-center">
                <h2 class="text-4xl font-black">Nuestros Cimientos</h2>
                <div class="grid grid-cols-2 gap-10 py-4 md:grid-cols-4">
                    @foreach ($values as $value)
                        <div class="group flex flex-col items-center space-y-4">
                            <div class="rounded-2xl bg-white/10 p-5 transition-colors group-hover:bg-white/20">
                                <x-icon :name="$valueIcons[$value]" class="h-12 w-12 text-white" />
                            </div>
                            <span class="text-xl font-bold uppercase tracking-wider">{{ $value }}</span>
                        </div>
                    @endforeach
                </div>
                <blockquote class="border-l-4 border-white/30 pl-8 text-left text-2xl font-medium italic text-red-50 md:text-3xl">
                    "Aquí todo se hace con el corazón, logrando de esta forma, diferenciarnos de los demás."
                </blockquote>
            </div>
        </section>

        <section class="grid grid-cols-1 gap-10 md:grid-cols-2">
            @foreach ($pillars as $pillar)
                <div class="rounded-3xl border border-gray-100 bg-gray-50 p-10 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <div class="mb-6 flex items-center gap-4">
                        <x-icon :name="$pillar['icon']" class="h-10 w-10 text-red-700" />
                        <h3 class="text-2xl font-black uppercase text-gray-900 dark:text-white">{{ $pillar['title'] }}</h3>
                    </div>
                    <p class="text-xl leading-relaxed text-gray-800 dark:text-gray-300">
                        {{ $pillar['text'] }}
                    </p>
                </div>
            @endforeach
        </section>

        <section class="rounded-[40px] bg-linear-to-b from-transparent to-gray-50 py-20 text-center dark:to-gray-900/50">
            <div class="mx-auto max-w-3xl space-y-10 px-4">
                <h3 class="text-3xl font-black text-gray-900 dark:text-white">Un sueño hecho realidad</h3>
                <p class="text-2xl leading-relaxed font-serif italic text-gray-800 dark:text-gray-200">
                    "Siento que hemos sido bendecidos por Dios al encontrarnos donde hoy estamos, siempre con la misma esencia y la humildad que llevamos como carta de presentación."
                </p>
                <div class="space-y-2">
                    <p class="text-3xl font-black text-red-700 dark:text-red-500">Osvaldo Agostini</p>
                    <p class="text-sm font-bold uppercase tracking-[0.3em] text-gray-600 dark:text-gray-400">Fundador</p>
                </div>
                <div class="pt-10">
                    <x-button label="Explorar Catálogo" icon="o-shopping-bag" link="/" class="btn-lg rounded-2xl border-none bg-red-700 px-12 font-bold text-white shadow-xl hover:bg-red-800" />
                </div>
            </div>
        </section>
    </div>
</div>
