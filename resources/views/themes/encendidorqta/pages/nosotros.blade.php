<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;

new #[Layout('components.layouts.clean')] #[Title('Nosotros - Encendido RQta')] class extends Component {
}; ?>

<div class="bg-white min-h-screen dark:bg-gray-950">
    <div class="relative flex h-64 items-center justify-center overflow-hidden bg-gray-900 md:h-80">
        <div class="absolute inset-0 bg-black/60"></div>
        <div class="relative px-4 text-center">
            <x-header title="Quiénes Somos" subtitle="Conocé nuestra historia" size="text-4xl md:text-5xl" class="text-white font-black" />
        </div>
    </div>

    <div class="mx-auto max-w-4xl px-6 py-16 space-y-10">
        <section class="space-y-6">
            <h2 class="text-3xl font-extrabold text-gray-900 dark:text-white">Nuestra empresa</h2>
            <p class="text-lg leading-relaxed text-gray-700 dark:text-gray-300">
                Somos una distribuidora con años de trayectoria en la región, comprometidos con la calidad y el servicio a nuestros clientes.
            </p>
        </section>
    </div>
</div>
