{{-- @oago-builder-compiled --}}
@php
    $__oagoPageTitle = 'Nosotros';
@endphp
<x-layouts.clean :title="$__oagoPageTitle">
<div class="w-full">

{{-- Bloque 1: rich_text (blk_df7c8ae1) --}}
@php
    $__oagoWidget0 = [
        'badge' => 'Información Institucional',
        'title' => 'Conocé más sobre ' . (string) (\App\Helpers\SettingsHelper::settings('company_name', config('app.name'))),
        'html' => '',
        'max_width' => 'medium',
        'background_color' => '#ffffff',
        'text_color' => '#1e293b',
        'accent_color' => '#a6282e',
    ];
@endphp
<x-theme-widgets.rich_text :props="$__oagoWidget0" :preview="false">
    <x-slot:htmlContent>
<p>Escribí aquí el contenido de tu sección utilizando formato enriquecido e incorporando datos vivos como <strong>{{ \App\Helpers\SettingsHelper::settings('company_name', config('app.name')) }}</strong> o <strong>{{ \App\Helpers\SettingsHelper::settings('company_phone', '') }}</strong>.</p>
    </x-slot:htmlContent>
</x-theme-widgets.rich_text>

{{-- Bloque 2: single_image (blk_bc7e6602) --}}
@php
    $__oagoWidget1 = [
        'image' => (string) (asset('themes/' . config('app.theme', 'default') . '/images/' . 'isoER50.png')),
        'alt' => 'Imagen destacada de ' . (string) (\App\Helpers\SettingsHelper::settings('company_name', config('app.name'))),
        'title' => '',
        'caption' => (string) (\App\Helpers\SettingsHelper::settings('company_name', config('app.name'))),
        'link_url' => '',
        'max_width' => 'md',
        'align' => 'center',
        'aspect_ratio' => '1-1',
        'rounded' => true,
        'shadow' => true,
        'background_color' => '#ffffff',
    ];
@endphp
<x-theme-widgets.single_image :props="$__oagoWidget1" :preview="false" />
</div>
</x-layouts.clean>
