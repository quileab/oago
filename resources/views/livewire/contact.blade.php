<?php
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;
use Mary\Traits\Toast;

new
    #[Layout('components.layouts.clean')]
    #[Title('Contacto - Agostini Distribuidor')]
    class extends Component {
    use Toast;

    public string $name = '';
    public string $email = '';
    public string $phone = '';
    public string $message = '';

    public function send()
    {
        $this->validate([
            'name' => 'required',
            'email' => 'required|email',
            'message' => 'required',
        ]);

        // Simulación de envío
        $this->success('Mensaje enviado correctamente. Nos pondremos en contacto a la brevedad.', position: 'toast-bottom toast-end');
        
        $this->reset(['name', 'email', 'phone', 'message']);
    }
}; ?>

<div class="bg-white dark:bg-gray-950 min-h-screen">
    {{-- Hero Section --}}
    <div class="relative h-48 md:h-72 overflow-hidden flex items-center justify-center bg-gray-900">
        <img src="{{ asset('imgs/brand.webp') }}" class="absolute inset-0 w-full h-full object-cover opacity-40 blur-xs" alt="Contacto Agostini">
        <div class="absolute inset-0 bg-linear-to-t from-gray-900/80 to-transparent"></div>
        <div class="relative text-center px-4">
            <x-header title="Contacto" subtitle="Estamos para asesorarte y acompañar el crecimiento de tu negocio" size="text-4xl md:text-5xl" class="text-white font-black drop-shadow-lg" />
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-6 py-16 space-y-16">
        
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-12">
            
            {{-- Columna de Información --}}
            <div class="lg:col-span-1 space-y-8">
                <h2 class="text-3xl font-extrabold text-gray-900 dark:text-white tracking-tight italic">Atención Personalizada</h2>
                <p class="text-lg text-gray-700 dark:text-gray-300">
                    Visitanos en nuestra casa central o comunicate por cualquiera de nuestros canales oficiales.
                </p>

                <div class="space-y-6">
                    {{-- Dirección --}}
                    <div class="flex items-start gap-4 p-4 rounded-2xl bg-gray-50 dark:bg-gray-900 border border-gray-100 dark:border-gray-800 shadow-sm">
                        <div class="p-3 bg-red-700 rounded-xl">
                            <x-icon name="o-map-pin" class="w-6 h-6 text-white" />
                        </div>
                        <div>
                            <p class="font-bold text-gray-900 dark:text-white">Nuestra Dirección</p>
                            <p class="text-gray-600 dark:text-gray-400">Av. José Gorriti 3014<br>S3000 - Santa Fe, Argentina</p>
                        </div>
                    </div>

                    {{-- Teléfono --}}
                    <div class="flex items-start gap-4 p-4 rounded-2xl bg-gray-50 dark:bg-gray-900 border border-gray-100 dark:border-gray-800 shadow-sm">
                        <div class="p-3 bg-red-700 rounded-xl">
                            <x-icon name="o-phone" class="w-6 h-6 text-white" />
                        </div>
                        <div>
                            <p class="font-bold text-gray-900 dark:text-white">Teléfono Principal</p>
                            <a href="tel:03424608300" class="text-gray-600 dark:text-gray-400 hover:text-red-700 transition-colors">0342 460-8300</a>
                        </div>
                    </div>

                    {{-- Redes Sociales --}}
                    <div class="p-6 rounded-2xl bg-red-700 text-white shadow-xl space-y-4">
                        <p class="font-bold text-xl uppercase tracking-wider">Seguinos en redes</p>
                        <div class="flex flex-col gap-3">
                            <a href="https://www.instagram.com/o.a.distribuciones" target="_blank" class="flex items-center gap-3 hover:translate-x-2 transition-transform">
                                <x-icon name="o-camera" class="w-6 h-6" />
                                <span>@o.a.distribuciones</span>
                            </a>
                            <a href="https://www.facebook.com/OAgostinidistribuciones" target="_blank" class="flex items-center gap-3 hover:translate-x-2 transition-transform">
                                <x-icon name="o-users" class="w-6 h-6" />
                                <span>/OAgostinidistribuciones</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Columna del Formulario --}}
            <div class="lg:col-span-2 bg-white dark:bg-gray-900 p-8 md:p-12 rounded-[40px] shadow-2xl border border-gray-100 dark:border-gray-800">
                <h3 class="text-3xl font-black text-gray-900 dark:text-white mb-8">Envianos un mensaje</h3>
                
                <form wire:submit="send" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <x-input label="Nombre completo" wire:model="name" icon="o-user" placeholder="Ej: Juan Pérez" class="bg-gray-50 dark:bg-gray-800 border-none text-gray-900 dark:text-white" />
                        <x-input label="Correo electrónico" wire:model="email" icon="o-envelope" placeholder="ejemplo@correo.com" class="bg-gray-50 dark:bg-gray-800 border-none text-gray-900 dark:text-white" />
                    </div>
                    
                    <x-input label="Teléfono (opcional)" wire:model="phone" icon="o-phone" placeholder="342 XXXXXXX" class="bg-gray-50 dark:bg-gray-900 border-none text-gray-900 dark:text-white" />
                    
                    <x-textarea label="Consulta o Mensaje" wire:model="message" icon="o-chat-bubble-bottom-center-text" placeholder="¿En qué podemos ayudarte?" rows="5" class="bg-gray-50 dark:bg-gray-800 border-none text-gray-900 dark:text-white" />
                    
                    <div class="pt-4 flex justify-end">
                        <x-button label="Enviar Consulta" type="submit" icon="o-paper-airplane" class="btn-lg bg-red-700 border-none hover:bg-red-800 text-white font-bold px-12 rounded-2xl shadow-xl" spinner="send" />
                    </div>
                </form>
            </div>
        </div>

        {{-- Mapa Section --}}
        <div class="space-y-8">
            <div class="flex items-center gap-4">
                <div class="w-12 h-1 bg-red-700 rounded-full"></div>
                <h3 class="text-2xl font-black text-gray-900 dark:text-white uppercase tracking-widest">Ubicación Estratégica</h3>
            </div>
            <div class="rounded-[40px] overflow-hidden shadow-2xl border-4 border-white dark:border-gray-900">
                <iframe
                    src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d2712.6935386062837!2d-60.69576889999999!3d-31.5897094!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x95b5073f9885249f%3A0xb530e103325fae06!2sAgostini%20Distribuidor%20Mayorista%20-%20OA%20DISTRIBUCIONES!5e1!3m2!1ses-419!2sar!4v1753448760696!5m2!1ses-419!2sar"
                    width="100%" height="450" style="border:0;" allowfullscreen="" loading="lazy"
                    referrerpolicy="no-referrer-when-downgrade"></iframe>
            </div>
        </div>
    </div>

    {{-- Footer de Contacto --}}
    <div class="bg-gray-900 text-white py-12 px-6">
        <div class="max-w-7xl mx-auto text-center space-y-4">
            <p class="text-red-500 font-bold uppercase tracking-[0.4em] text-sm italic">Horarios de Atención</p>
            <p class="text-2xl md:text-3xl font-light">Lunes a Viernes de 08:00 a 17:00 hs | Sábados de 08:00 a 12:00 hs</p>
        </div>
    </div>
</div>
