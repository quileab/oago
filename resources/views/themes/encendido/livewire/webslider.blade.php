<?php

use Illuminate\Support\Facades\Storage;
use Livewire\Volt\Component;

new class extends Component
{
    public function slides()
    {
        $disk = Storage::disk('slider_public');
        $jsonPath = 'slider.json';

        if (! $disk->exists($jsonPath)) {
            return [];
        }

        $data = json_decode($disk->get($jsonPath), true) ?? [];
        $items = $data['slides'] ?? $data;

        if (! is_array($items)) {
            return [];
        }

        return collect($items)->map(function ($item) {
            $path = is_array($item) ? $item['id'] : $item;

            return [
                'image' => asset('imgs/slider/'.$path),
                'title' => is_array($item) ? ($item['title'] ?? '') : '',
                'description' => is_array($item) ? ($item['description'] ?? '') : '',
                'url' => is_array($item) ? ($item['url'] ?? '') : '',
                'urlText' => is_array($item) ? ($item['urlText'] ?? '') : '',
            ];
        })->toArray();
    }

    public function config()
    {
        $disk = Storage::disk('slider_public');
        $jsonPath = 'slider.json';
        $defaultConfig = [
            'autoplay' => true,
            'interval' => 5000,
            'withoutArrows' => false,
            'withoutIndicators' => false,
        ];

        if (! $disk->exists($jsonPath)) {
            return $defaultConfig;
        }

        $data = json_decode($disk->get($jsonPath), true);

        return array_merge($defaultConfig, $data['config'] ?? []);
    }

    public function with(): array
    {
        return [
            'slides' => $this->slides(),
            'config' => $this->config(),
        ];
    }
}; ?>

<div class="relative w-full overflow-hidden bg-neutral text-neutral-content pt-8 pb-12 sm:pt-16 sm:pb-24 mt-0 border-b border-neutral-focus"
     x-data="{ scrolled: false }"
     @scroll.window="scrolled = (window.pageYOffset > 50)">
    
    <!-- Animated Background Elements -->
    <div class="absolute inset-0 z-0 overflow-hidden pointer-events-none">
        <div class="absolute top-[-20%] left-[-10%] w-[50%] h-[50%] rounded-full bg-primary/20 blur-[120px] animate-pulse"></div>
        <div class="absolute bottom-[-20%] right-[-10%] w-[40%] h-[40%] rounded-full bg-error/20 blur-[100px]" style="animation: pulse 4s cubic-bezier(0.4, 0, 0.6, 1) infinite 1s;"></div>
        <div class="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/carbon-fibre.png')] opacity-20 mix-blend-overlay"></div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 relative z-10 flex flex-col lg:flex-row items-center gap-12">
        
        <!-- Hero Content Area -->
        <div class="w-full lg:w-5/12 flex flex-col justify-center text-center lg:text-left space-y-6"
             x-data="{ show: false }" x-init="setTimeout(() => show = true, 100)">
            <h1 class="text-4xl sm:text-5xl lg:text-6xl font-black uppercase tracking-tighter leading-none text-transparent bg-clip-text bg-gradient-to-br from-white via-gray-200 to-gray-500 transition-all duration-1000 transform"
                :class="show ? 'translate-y-0 opacity-100' : 'translate-y-10 opacity-0'">
                MÁS DE 50 AÑOS <br/> <span class="text-primary drop-shadow-[0_0_15px_rgba(220,38,38,0.5)]">DE CONFIANZA</span>
            </h1>
            
            <p class="text-lg sm:text-xl text-neutral-content/80 font-medium transition-all duration-1000 delay-300 transform"
               :class="show ? 'translate-y-0 opacity-100' : 'translate-y-10 opacity-0'">
                Siendo el respaldo de tu vehículo en la zona norte de Santa Fe. Repuestos y baterías para mantener tu vehículo en marcha.
            </p>
            
            <div class="flex flex-wrap items-center justify-center lg:justify-start gap-4 pt-4 transition-all duration-1000 delay-500 transform"
                 :class="show ? 'translate-y-0 opacity-100' : 'translate-y-10 opacity-0'">
                <a href="#catalogo" @click.prevent="document.getElementById('catalogo').scrollIntoView({behavior: 'smooth'})" class="btn btn-primary btn-lg rounded-full shadow-[0_0_20px_rgba(220,38,38,0.4)] hover:shadow-[0_0_30px_rgba(220,38,38,0.6)] hover:scale-105 transition-all font-bold tracking-wider">
                    VER CATÁLOGO <x-icon name="o-arrow-down" class="w-5 h-5 ml-2" />
                </a>
                <a href="https://wa.me/5493482415931" target="_blank" class="btn btn-outline btn-lg rounded-full text-white hover:bg-green-500 hover:text-white hover:border-green-500 hover:scale-105 transition-all font-bold tracking-wider">
                    <x-icon name="o-chat-bubble-left-ellipsis" class="w-5 h-5 mr-2" /> CONSULTANOS
                </a>
            </div>
            
            <!-- Quick Stats -->
            <div class="grid grid-cols-3 gap-4 pt-8 border-t border-neutral-focus mt-8 transition-all duration-1000 delay-700 transform"
                 :class="show ? 'translate-y-0 opacity-100' : 'translate-y-10 opacity-0'">
                <div class="text-center lg:text-left">
                    <div class="text-3xl font-black text-white">+50</div>
                    <div class="text-xs text-primary font-bold uppercase tracking-widest mt-1">Años</div>
                </div>
                <div class="text-center lg:text-left">
                    <div class="text-3xl font-black text-white">3</div>
                    <div class="text-xs text-primary font-bold uppercase tracking-widest mt-1">Unidades</div>
                </div>
                <div class="text-center lg:text-left">
                    <div class="text-3xl font-black text-white">100%</div>
                    <div class="text-xs text-primary font-bold uppercase tracking-widest mt-1">Respaldo</div>
                </div>
            </div>
        </div>

        <!-- Slider Area -->
        <div class="w-full lg:w-7/12" x-data="{ show: false }" x-init="setTimeout(() => show = true, 400)">
            <div class="transition-all duration-1000 transform"
                 :class="show ? 'translate-x-0 opacity-100' : 'translate-x-12 opacity-0'">
                <div class="rounded-3xl overflow-hidden shadow-[0_20px_50px_rgba(0,0,0,0.5)] border border-neutral-focus relative group">
                    <!-- Overlay Decorativo en el Slider -->
                    <div class="absolute inset-0 border-2 border-white/10 rounded-3xl z-20 pointer-events-none group-hover:border-primary/50 transition-colors duration-500"></div>
                    
                    @if(count($slides) > 0)
                        <x-carousel 
                            :slides="$slides" 
                            :autoplay="$config['autoplay']" 
                            :interval="$config['interval']" 
                            :without-arrows="$config['withoutArrows']"
                            :without-indicators="$config['withoutIndicators']"
                            class="h-64 sm:h-96 lg:h-[500px] w-full object-cover" 
                        />
                    @else
                        <!-- Fallback si no hay slides -->
                        <div class="w-full h-64 sm:h-96 lg:h-[500px] bg-neutral-focus flex items-center justify-center">
                            <x-icon name="o-photo" class="w-24 h-24 text-neutral-content/20" />
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
