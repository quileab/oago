@props([
    'props' => [],
    'preview' => false,
])

@php
    $badge = (string) ($props['badge'] ?? '');
    $title = (string) ($props['title'] ?? '');
    $subtitle = (string) ($props['subtitle'] ?? '');
    $address = (string) ($props['address'] ?? '');
    $phone = (string) ($props['phone'] ?? '');
    $email = (string) ($props['email'] ?? '');
    $accentColor = (string) ($props['accent_color'] ?? '#a6282e');
    $secondaryColor = (string) ($props['secondary_color'] ?? '#1b365d');
    $bgColor = (string) ($props['background_color'] ?? '#ffffff');
    $hours = is_array($props['hours'] ?? null) ? $props['hours'] : [];

    $rawEmbed = trim((string) ($props['map_embed_url'] ?? ''));
    $mapEmbedUrl = str_starts_with($rawEmbed, 'https://') ? $rawEmbed : '';

    $rawLink = trim((string) ($props['map_link_url'] ?? ''));
    $mapLinkUrl = preg_match('#^https?://#i', $rawLink) ? $rawLink : '';
@endphp

<section class="w-full py-12 sm:py-16" style="background-color: {{ $bgColor }};">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 items-stretch">
            <div class="rounded-2xl bg-slate-50 border border-slate-200/80 p-6 sm:p-8 flex flex-col justify-between">
                <div>
                    @if ($badge !== '')
                        <span
                            class="inline-block px-3.5 py-1 rounded-full text-xs font-bold uppercase tracking-wider text-white mb-4"
                            style="background-color: {{ $accentColor }};"
                        >
                            {{ $badge }}
                        </span>
                    @endif

                    @if ($title !== '')
                        <h2 class="text-2xl sm:text-3xl font-black text-slate-900">{{ $title }}</h2>
                    @endif
                    @if ($subtitle !== '')
                        <p class="mt-2.5 text-sm sm:text-base text-slate-600">{{ $subtitle }}</p>
                    @endif

                    <dl class="mt-6 space-y-3 text-sm text-slate-700">
                        @if ($address !== '')
                            <div>
                                <dt class="text-xs font-bold uppercase tracking-wider text-slate-400">Dirección</dt>
                                <dd class="font-medium mt-0.5">{{ $address }}</dd>
                            </div>
                        @endif
                        @if ($phone !== '')
                            <div>
                                <dt class="text-xs font-bold uppercase tracking-wider text-slate-400">Teléfono</dt>
                                <dd class="font-medium mt-0.5">{{ $phone }}</dd>
                            </div>
                        @endif
                        @if ($email !== '')
                            <div>
                                <dt class="text-xs font-bold uppercase tracking-wider text-slate-400">Correo electrónico</dt>
                                <dd class="font-medium mt-0.5 break-all">{{ $email }}</dd>
                            </div>
                        @endif
                    </dl>

                    @if (count($hours) > 0)
                        <div class="mt-6 pt-5 border-t border-slate-200">
                            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-3">Horarios de Atención</h3>
                            <ul class="space-y-1.5 text-sm">
                                @foreach ($hours as $slot)
                                    @php
                                        $day = (string) ($slot['day'] ?? '');
                                        $schedule = (string) ($slot['schedule'] ?? '');
                                    @endphp
                                    @if ($day !== '' || $schedule !== '')
                                        <li class="flex justify-between gap-4 text-slate-700">
                                            <span class="font-semibold">{{ $day }}</span>
                                            <span>{{ $schedule }}</span>
                                        </li>
                                    @endif
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>

                @if ($mapLinkUrl !== '')
                    <div class="mt-6">
                        <a
                            href="{{ $preview ? 'javascript:void(0)' : $mapLinkUrl }}"
                            target="{{ $preview ? '_self' : '_blank' }}"
                            rel="noopener noreferrer"
                            class="inline-flex items-center px-5 py-2.5 rounded-xl text-xs font-bold text-white shadow-sm transition"
                            style="background-color: {{ $secondaryColor }};"
                        >
                            Abrir ubicación en Google Maps
                        </a>
                    </div>
                @endif
            </div>

            <div class="rounded-2xl overflow-hidden border border-slate-200 bg-slate-100 min-h-[300px] flex items-center justify-center">
                @if ($mapEmbedUrl !== '')
                    <iframe
                        src="{{ $mapEmbedUrl }}"
                        class="w-full h-full min-h-[320px] border-0"
                        loading="lazy"
                        referrerpolicy="no-referrer-when-downgrade"
                        title="{{ $title }}"
                    ></iframe>
                @else
                    <div class="p-8 text-center text-slate-500 text-sm max-w-sm">
                        <p class="font-semibold text-slate-700">Mapa de ubicación</p>
                        <p class="mt-1 text-xs">Pegá una URL embed (https://www.google.com/maps/embed?pb=...) en el inspector para mostrar el mapa interactivo.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</section>
