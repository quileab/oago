<x-layouts.clean>

<div class="bg-gray-200 min-h-screen font-sans antialiased text-gray-900">

    {{-- Hero --}}
    <div class="relative w-full overflow-hidden bg-gray-900 py-20 md:py-28">
        <div class="absolute inset-0 bg-black/60"></div>
        <div class="relative z-10 max-w-7xl mx-auto px-6 text-center">
            <h1 class="text-4xl md:text-5xl lg:text-6xl font-black text-white uppercase tracking-tighter leading-none">
                Nuestros Locales
            </h1>
            <p class="mt-4 text-lg md:text-xl text-white/80 font-medium max-w-2xl mx-auto leading-relaxed">
                Encontranos en Moreno 1531 y 1541, Reconquista, Santa Fe.
            </p>
        </div>
    </div>

    {{-- Mapa y Contacto --}}
    <div class="max-w-7xl mx-auto px-4 md:px-6 py-12">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">

            {{-- Datos de contacto --}}
            <div class="bg-white rounded-2xl shadow-md border border-gray-100 p-8 space-y-6">
                <h2 class="text-2xl font-black uppercase tracking-wide text-gray-900">Dónde estamos</h2>

                <div class="space-y-4">
                    <div class="flex items-start gap-4">
                        <div class="w-10 h-10 rounded-xl bg-[#a6282e]/10 text-[#a6282e] flex items-center justify-center shrink-0">
                            <x-icon name="o-map-pin" class="w-5 h-5" />
                        </div>
                        <div>
                            <div class="text-xs font-black uppercase tracking-widest text-gray-500 mb-1">Dirección</div>
                            <p class="font-bold text-gray-900">Moreno 1531 y 1541</p>
                            <p class="text-sm text-gray-600">Reconquista, Santa Fe</p>
                        </div>
                    </div>

                    <div class="flex items-start gap-4">
                        <div class="w-10 h-10 rounded-xl bg-[#a6282e]/10 text-[#a6282e] flex items-center justify-center shrink-0">
                            <x-icon name="o-phone" class="w-5 h-5" />
                        </div>
                        <div>
                            <div class="text-xs font-black uppercase tracking-widest text-gray-500 mb-1">WhatsApp ER</div>
                            <a href="https://wa.me/5493482415931" target="_blank" class="font-bold text-[#a6282e] hover:underline">3482 415931</a>
                        </div>
                    </div>

                    <div class="flex items-start gap-4">
                        <div class="w-10 h-10 rounded-xl bg-[#1b365d]/10 text-[#1b365d] flex items-center justify-center shrink-0">
                            <x-icon name="o-phone" class="w-5 h-5" />
                        </div>
                        <div>
                            <div class="text-xs font-black uppercase tracking-widest text-gray-500 mb-1">WhatsApp CIBAT</div>
                            <a href="https://wa.me/5493482534205" target="_blank" class="font-bold text-[#1b365d] hover:underline">3482 534205</a>
                        </div>
                    </div>
                </div>

                <div class="pt-4 flex flex-col sm:flex-row gap-3">
                    <a href="https://maps.app.goo.gl/t4Vzx1ZCed91sBJ38" target="_blank"
                        class="inline-flex items-center justify-center gap-2 bg-gray-900 text-white px-5 py-3 rounded-full text-xs font-black uppercase tracking-widest shadow-md hover:bg-gray-700 transition-colors">
                        <x-icon name="o-map-pin" class="w-4 h-4" /> Cómo llegar
                    </a>
                    <a href="/"
                        class="inline-flex items-center justify-center gap-2 bg-white border border-gray-200 text-gray-700 px-5 py-3 rounded-full text-xs font-black uppercase tracking-widest hover:bg-gray-50 transition-colors">
                        <x-icon name="o-shopping-bag" class="w-4 h-4" /> Ver catálogo
                    </a>
                </div>
            </div>

            {{-- Mapa --}}
            <div class="bg-white rounded-2xl shadow-md border border-gray-100 overflow-hidden">
                {!! \App\Helpers\SettingsHelper::settings('company_map_iframe', '<iframe src="https://maps.google.com/maps?q=Moreno+1531+Reconquista+Santa+Fe&output=embed" width="100%" height="400" style="border:0;" allowfullscreen="" loading="lazy"></iframe>') !!}
            </div>

        </div>
    </div>

</div>

</x-layouts.clean>
