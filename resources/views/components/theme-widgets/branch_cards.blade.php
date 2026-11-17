@props([
    'props' => [],
    'preview' => false,
])

@php
    $badge = (string) ($props['badge'] ?? '');
    $title = (string) ($props['title'] ?? '');
    $subtitle = (string) ($props['subtitle'] ?? '');
    $columns = (string) ($props['columns'] ?? '2');
    $accentColor = (string) ($props['accent_color'] ?? '#a6282e');
    $secondaryColor = (string) ($props['secondary_color'] ?? '#1b365d');
    $bgColor = (string) ($props['background_color'] ?? '#f8fafc');
    $branches = is_array($props['branches'] ?? null) ? $props['branches'] : [];

    $gridColsClass = match ($columns) {
        '1' => 'grid-cols-1 max-w-3xl mx-auto',
        '3' => 'grid-cols-1 md:grid-cols-2 lg:grid-cols-3',
        default => 'grid-cols-1 lg:grid-cols-2',
    };

    $safeMapUrl = static function (string $url): string {
        $trimmed = trim($url);
        if ($trimmed !== '' && preg_match('#^https?://#i', $trimmed)) {
            return $trimmed;
        }

        return '';
    };
@endphp

<section class="w-full py-12 sm:py-16" style="background-color: {{ $bgColor }};">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        @if ($badge !== '' || $title !== '' || $subtitle !== '')
            <div class="text-center max-w-2xl mx-auto mb-10">
                @if ($badge !== '')
                    <span
                        class="inline-block px-3.5 py-1 rounded-full text-xs font-bold uppercase tracking-wider text-white mb-3"
                        style="background-color: {{ $accentColor }};"
                    >
                        {{ $badge }}
                    </span>
                @endif
                @if ($title !== '')
                    <h2 class="text-2xl sm:text-3xl font-black tracking-tight text-slate-900">{{ $title }}</h2>
                @endif
                @if ($subtitle !== '')
                    <p class="mt-2.5 text-sm sm:text-base text-slate-600">{{ $subtitle }}</p>
                @endif
            </div>
        @endif

        <div class="grid {{ $gridColsClass }} gap-6">
            @foreach ($branches as $branch)
                @php
                    $name = (string) ($branch['name'] ?? '');
                    $branchBadge = (string) ($branch['badge'] ?? '');
                    $address = (string) ($branch['address'] ?? '');
                    $city = (string) ($branch['city'] ?? '');
                    $phone = (string) ($branch['phone'] ?? '');
                    $whatsapp = preg_replace('/\D+/', '', (string) ($branch['whatsapp'] ?? '')) ?? '';
                    $email = (string) ($branch['email'] ?? '');
                    $hoursText = (string) ($branch['hours_text'] ?? '');
                    $image = (string) ($branch['image'] ?? '');
                    $mapUrl = $safeMapUrl((string) ($branch['map_url'] ?? ''));
                @endphp
                <article class="rounded-2xl overflow-hidden bg-white border border-slate-200/80 shadow-md flex flex-col">
                    @if ($image !== '')
                        <div class="relative h-52 w-full bg-slate-100 overflow-hidden">
                            <img src="{{ $image }}" alt="{{ $name }}" class="w-full h-full object-cover" loading="lazy" />
                            @if ($branchBadge !== '')
                                <span
                                    class="absolute top-3 left-3 px-3 py-1 rounded-full text-xs font-bold text-white shadow-sm"
                                    style="background-color: {{ $accentColor }};"
                                >
                                    {{ $branchBadge }}
                                </span>
                            @endif
                        </div>
                    @endif

                    <div class="p-6 flex-1 flex flex-col justify-between gap-4">
                        <div>
                            <div class="flex items-start justify-between gap-3">
                                <h3 class="text-xl font-black text-slate-900">{{ $name }}</h3>
                                @if ($image === '' && $branchBadge !== '')
                                    <span
                                        class="px-2.5 py-0.5 rounded-full text-xs font-bold text-white shrink-0"
                                        style="background-color: {{ $secondaryColor }};"
                                    >
                                        {{ $branchBadge }}
                                    </span>
                                @endif
                            </div>

                            @if ($city !== '')
                                <p class="text-xs font-semibold uppercase tracking-wider mt-1" style="color: {{ $accentColor }};">
                                    {{ $city }}
                                </p>
                            @endif

                            <dl class="mt-4 space-y-2 text-sm text-slate-600">
                                @if ($address !== '')
                                    <div class="flex items-start gap-2">
                                        <span class="font-semibold text-slate-800 shrink-0">Dirección:</span>
                                        <span>{{ $address }}</span>
                                    </div>
                                @endif
                                @if ($hoursText !== '')
                                    <div class="flex items-start gap-2">
                                        <span class="font-semibold text-slate-800 shrink-0">Horarios:</span>
                                        <span>{{ $hoursText }}</span>
                                    </div>
                                @endif
                                @if ($phone !== '')
                                    <div class="flex items-start gap-2">
                                        <span class="font-semibold text-slate-800 shrink-0">Teléfono:</span>
                                        <span>{{ $phone }}</span>
                                    </div>
                                @endif
                                @if ($email !== '')
                                    <div class="flex items-start gap-2">
                                        <span class="font-semibold text-slate-800 shrink-0">Email:</span>
                                        <span class="break-all">{{ $email }}</span>
                                    </div>
                                @endif
                            </dl>
                        </div>

                        @if ($whatsapp !== '' || $mapUrl !== '')
                            <div class="pt-3 border-t border-slate-100 flex flex-wrap gap-2.5">
                                @if ($whatsapp !== '')
                                    <a
                                        href="{{ $preview ? 'javascript:void(0)' : 'https://wa.me/' . $whatsapp }}"
                                        target="{{ $preview ? '_self' : '_blank' }}"
                                        rel="noopener noreferrer"
                                        class="inline-flex items-center px-4 py-2 rounded-xl text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 transition"
                                    >
                                        WhatsApp
                                    </a>
                                @endif
                                @if ($mapUrl !== '')
                                    <a
                                        href="{{ $preview ? 'javascript:void(0)' : $mapUrl }}"
                                        target="{{ $preview ? '_self' : '_blank' }}"
                                        rel="noopener noreferrer"
                                        class="inline-flex items-center px-4 py-2 rounded-xl text-xs font-bold text-white transition"
                                        style="background-color: {{ $secondaryColor }};"
                                    >
                                        Cómo llegar
                                    </a>
                                @endif
                            </div>
                        @endif
                    </div>
                </article>
            @endforeach
        </div>
    </div>
</section>
