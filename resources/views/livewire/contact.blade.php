<?php
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;

new
    #[Layout('components.layouts.clean')]
    #[Title('Contactanos')]
    class extends Component {
    //
}; ?>

<div>
    <div class="text-xl indent-6 leading-8">
        <h1 class="text-4xl font-bold italic">Contactos</h1>
        <div class="grid grid-cols-1 md:grid-cols-2">
            <div class="flex flex-col align-middle">
                <p class="mt-4 text-lg">
                    <x-icon name="o-map-pin" label="Dirección" class="w-8 h-8 text-xl mb-4 text-orange-600" />
                    Av. José Gorriti 3014 | S3000 - Santa Fe
                </p>
                <p class="mt-4 text-lg">
                    <x-icon name="o-phone" label="Telefono" class="w-8 h-8 text-xl mb-4 text-orange-600" />
                    0342 460-8300
                </p>
                <p class="mt-4 text-lg mb-4">
                    <x-icon name="o-user-group" label="SOCIAL" class="w-8 h-8 text-xl mb-4 text-orange-600" />
                    <a href="https://www.instagram.com/o.a.distribuciones" target="_blank" class="flex gap-2">
                        <svg class="w-8 h-8" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24"
                            height="24" fill="none" viewBox="0 0 24 24">
                            <path fill="currentColor" fill-rule="evenodd"
                                d="M3 8a5 5 0 0 1 5-5h8a5 5 0 0 1 5 5v8a5 5 0 0 1-5 5H8a5 5 0 0 1-5-5V8Zm5-3a3 3 0 0 0-3 3v8a3 3 0 0 0 3 3h8a3 3 0 0 0 3-3V8a3 3 0 0 0-3-3H8Zm7.597 2.214a1 1 0 0 1 1-1h.01a1 1 0 1 1 0 2h-.01a1 1 0 0 1-1-1ZM12 9a3 3 0 1 0 0 6 3 3 0 0 0 0-6Zm-5 3a5 5 0 1 1 10 0 5 5 0 0 1-10 0Z"
                                clip-rule="evenodd" />
                        </svg>
                        instagram.com/o.a.distribuciones
                    </a>
                    <a href="https://www.facebook.com/OAgostinidistribuciones" target="_blank" class="flex gap-2">
                        <svg class="w-8 h-8" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24"
                            height="24" fill="currentColor" viewBox="0 0 24 24">
                            <path fill-rule="evenodd"
                                d="M13.135 6H15V3h-1.865a4.147 4.147 0 0 0-4.142 4.142V9H7v3h2v9.938h3V12h2.021l.592-3H12V6.591A.6.6 0 0 1 12.592 6h.543Z"
                                clip-rule="evenodd" />
                        </svg>
                        facebook.com/OAgostinidistribuciones
                    </a>
                </p>


            </div>
            <div id="map">
                <iframe
                    src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d2712.6935386062837!2d-60.69576889999999!3d-31.5897094!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x95b5073f9885249f%3A0xb530e103325fae06!2sAgostini%20Distribuidor%20Mayorista%20-%20OA%20DISTRIBUCIONES!5e1!3m2!1ses-419!2sar!4v1753448760696!5m2!1ses-419!2sar"
                    width="100%" height="600" style="border:0;" allowfullscreen="" loading="lazy"
                    referrerpolicy="no-referrer-when-downgrade"></iframe>
            </div>
        </div>
    </div>
</div>