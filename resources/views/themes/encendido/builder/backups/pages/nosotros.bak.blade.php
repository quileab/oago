<?php
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;

new #[Layout('components.layouts.clean')] #[Title('Nosotros')] class extends Component {
}; ?>

<div class="bg-white min-h-screen dark:bg-gray-950 px-4 py-8 text-gray-900 dark:text-gray-100">
    <div class="mx-auto max-w-4xl space-y-6">
        <h1 class="text-3xl font-bold">Nosotros</h1>
        <p>Contenido de la página va aquí...</p>
    </div>
</div>