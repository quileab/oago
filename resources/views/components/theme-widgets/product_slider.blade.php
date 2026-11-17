@props([
    'props' => [],
    'preview' => false,
])

@php
    $title = (string) ($props['title'] ?? 'Productos Destacados');
    $sliderType = in_array(($props['slider_type'] ?? 'featured'), ['featured', 'new'], true)
        ? (string) $props['slider_type']
        : 'featured';
    $bgColor = (string) ($props['background_color'] ?? '#ffffff');
    $accentColor = (string) ($props['accent_color'] ?? '#1b365d');
@endphp

<section class="w-full py-6" style="background-color: {{ $bgColor }};">
    <div class="max-w-7xl mx-auto">
        @if ($preview)
            <div class="px-4 sm:px-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-xl font-black text-slate-900 flex items-center gap-3">
                        <span class="w-2 h-7 rounded-full" style="background-color: {{ $accentColor }};"></span>
                        <span>{{ $title !== '' ? $title : 'Carrusel de Productos' }}</span>
                    </h2>
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-700">
                        {{ $sliderType === 'new' ? 'Colección: Últimos ingresos' : 'Colección: Destacados' }}
                    </span>
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                    @for ($i = 1; $i <= 4; $i++)
                        <div class="rounded-xl border border-slate-200 bg-white p-3 shadow-xs">
                            <div class="aspect-square rounded-lg bg-slate-100 mb-3 flex items-center justify-center text-xs font-medium text-slate-400">
                                Producto vivo #{{ $i }}
                            </div>
                            <div class="h-3 w-3/4 rounded bg-slate-200 mb-2"></div>
                            <div class="h-3 w-1/2 rounded bg-slate-100 mb-3"></div>
                            <div class="h-7 w-full rounded-lg opacity-85" style="background-color: {{ $accentColor }};"></div>
                        </div>
                    @endfor
                </div>
            </div>
        @else
            <livewire:web-product-slider :type="$sliderType" :title="$title" />
        @endif
    </div>
</section>
