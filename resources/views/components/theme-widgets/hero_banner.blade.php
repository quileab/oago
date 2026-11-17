@props([
    'props' => [],
    'preview' => false,
])

@php
    $badge = (string) ($props['badge'] ?? '');
    $title = (string) ($props['title'] ?? '');
    $subtitle = (string) ($props['subtitle'] ?? '');
    $bgImage = (string) ($props['background_image'] ?? '');
    $bgColor = (string) ($props['background_color'] ?? '#1b365d');
    $accentColor = (string) ($props['accent_color'] ?? '#a6282e');
    $textColor = (string) ($props['text_color'] ?? '#ffffff');
    $overlayOpacity = max(0, min(95, (int) ($props['overlay_opacity'] ?? 75)));
    $align = (string) ($props['align'] ?? 'left');
    $buttons = is_array($props['buttons'] ?? null) ? $props['buttons'] : [];
    $stats = is_array($props['stats'] ?? null) ? $props['stats'] : [];
    $isCentered = $align === 'center';
    $safeHref = static function (string $url): string {
        $trimmed = trim($url);
        if ($trimmed === '' || str_starts_with($trimmed, '/') || str_starts_with($trimmed, '#') || preg_match('#^(https?://|tel:|mailto:)#i', $trimmed)) {
            return $trimmed !== '' ? $trimmed : '#';
        }

        return '#';
    };
@endphp

<section class="relative w-full overflow-hidden" style="background-color: {{ $bgColor }}; color: {{ $textColor }};">
    @if ($bgImage !== '')
        <img
            src="{{ $bgImage }}"
            alt="{{ $title }}"
            class="absolute inset-0 w-full h-full object-cover"
            loading="lazy"
        />
        <div
            class="absolute inset-0"
            style="background-color: {{ $bgColor }}; opacity: {{ number_format($overlayOpacity / 100, 2, '.', '') }};"
        ></div>
    @else
        <div
            class="absolute inset-0 opacity-20 pointer-events-none"
            style="background: radial-gradient(circle at top right, {{ $accentColor }}, transparent 65%);"
        ></div>
    @endif

    <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-14 sm:py-20">
        <div class="{{ $isCentered ? 'max-w-3xl mx-auto text-center' : 'max-w-3xl text-left' }}">
            @if ($badge !== '')
                <div
                    class="inline-flex items-center gap-2 px-3.5 py-1 rounded-full text-xs font-semibold uppercase tracking-wider mb-5 shadow-sm"
                    style="background-color: {{ $accentColor }}; color: #ffffff;"
                >
                    <span>{{ $badge }}</span>
                </div>
            @endif

            @if ($title !== '')
                <h1 class="text-3xl sm:text-4xl lg:text-5xl font-black tracking-tight leading-tight">
                    {{ $title }}
                </h1>
            @endif

            @if ($subtitle !== '')
                <p class="mt-4 text-base sm:text-lg leading-relaxed opacity-90 {{ $isCentered ? 'mx-auto max-w-2xl' : 'max-w-2xl' }}">
                    {{ $subtitle }}
                </p>
            @endif

            @if (count($buttons) > 0)
                <div class="mt-8 flex flex-wrap gap-3 {{ $isCentered ? 'justify-center' : 'justify-start' }}">
                    @foreach ($buttons as $button)
                        @php
                            $btnLabel = (string) ($button['label'] ?? '');
                            $btnUrl = $safeHref((string) ($button['url'] ?? '#'));
                            $btnStyle = (string) ($button['style'] ?? 'primary');
                        @endphp
                        @if ($btnLabel !== '')
                            @if ($btnStyle === 'outline')
                                <a
                                    href="{{ $preview ? 'javascript:void(0)' : $btnUrl }}"
                                    class="inline-flex items-center justify-center px-6 py-3 rounded-xl font-semibold text-sm border border-current/40 bg-white/10 hover:bg-white/20 backdrop-blur-xs transition"
                                >
                                    {{ $btnLabel }}
                                </a>
                            @else
                                <a
                                    href="{{ $preview ? 'javascript:void(0)' : $btnUrl }}"
                                    class="inline-flex items-center justify-center px-6 py-3 rounded-xl font-bold text-sm text-white shadow-lg hover:brightness-110 transition"
                                    style="background-color: {{ $accentColor }};"
                                >
                                    {{ $btnLabel }}
                                </a>
                            @endif
                        @endif
                    @endforeach
                </div>
            @endif
        </div>

        @if (count($stats) > 0)
            <div class="mt-10 pt-8 border-t border-white/15 grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
                @foreach ($stats as $stat)
                    @php
                        $statVal = (string) ($stat['value'] ?? '');
                        $statLabel = (string) ($stat['label'] ?? '');
                    @endphp
                    @if ($statVal !== '' || $statLabel !== '')
                        <div class="rounded-xl bg-white/10 backdrop-blur-xs px-4 py-3 border border-white/10">
                            <div class="text-2xl sm:text-3xl font-black tracking-tight">{{ $statVal }}</div>
                            @if ($statLabel !== '')
                                <div class="text-xs sm:text-sm opacity-80 mt-0.5">{{ $statLabel }}</div>
                            @endif
                        </div>
                    @endif
                @endforeach
            </div>
        @endif
    </div>
</section>
