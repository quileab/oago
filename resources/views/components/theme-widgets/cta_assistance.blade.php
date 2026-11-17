@props([
    'props' => [],
    'preview' => false,
])

@php
    $badge = (string) ($props['badge'] ?? '');
    $title = (string) ($props['title'] ?? '');
    $subtitle = (string) ($props['subtitle'] ?? '');
    $bgColor = (string) ($props['background_color'] ?? '#1b365d');
    $accentColor = (string) ($props['accent_color'] ?? '#a6282e');
    $textColor = (string) ($props['text_color'] ?? '#ffffff');
    $buttons = is_array($props['buttons'] ?? null) ? $props['buttons'] : [];
    $services = is_array($props['services'] ?? null) ? $props['services'] : [];

    $safeHref = static function (string $url): string {
        $trimmed = trim($url);
        if ($trimmed === '' || str_starts_with($trimmed, '/') || str_starts_with($trimmed, '#') || preg_match('#^(https?://|tel:|mailto:)#i', $trimmed)) {
            return $trimmed !== '' ? $trimmed : '#';
        }

        return '#';
    };
@endphp

<section class="w-full py-10 px-4 sm:px-6 lg:px-8">
    <div
        class="max-w-7xl mx-auto rounded-3xl overflow-hidden shadow-xl p-8 sm:p-12 relative"
        style="background-color: {{ $bgColor }}; color: {{ $textColor }};"
    >
        <div
            class="absolute -right-24 -top-24 w-72 h-72 rounded-full opacity-20 blur-2xl pointer-events-none"
            style="background-color: {{ $accentColor }};"
        ></div>

        <div class="relative z-10 flex flex-col lg:flex-row lg:items-center justify-between gap-8">
            <div class="max-w-2xl">
                @if ($badge !== '')
                    <span
                        class="inline-block px-3.5 py-1 rounded-full text-xs font-bold uppercase tracking-wider text-white mb-4"
                        style="background-color: {{ $accentColor }};"
                    >
                        {{ $badge }}
                    </span>
                @endif

                @if ($title !== '')
                    <h2 class="text-2xl sm:text-3xl font-black tracking-tight leading-snug">{{ $title }}</h2>
                @endif

                @if ($subtitle !== '')
                    <p class="mt-3 text-sm sm:text-base opacity-90 leading-relaxed">{{ $subtitle }}</p>
                @endif

                @if (count($services) > 0)
                    <div class="mt-5 flex flex-wrap gap-2">
                        @foreach ($services as $service)
                            @php $srvLabel = (string) ($service['label'] ?? ''); @endphp
                            @if ($srvLabel !== '')
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-white/10 border border-white/15">
                                    ✓ {{ $srvLabel }}
                                </span>
                            @endif
                        @endforeach
                    </div>
                @endif
            </div>

            @if (count($buttons) > 0)
                <div class="flex flex-wrap lg:flex-col gap-3 shrink-0">
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
                                    class="inline-flex items-center justify-center px-6 py-3 rounded-xl font-semibold text-sm border border-current/40 bg-white/10 hover:bg-white/20 transition"
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
    </div>
</section>
