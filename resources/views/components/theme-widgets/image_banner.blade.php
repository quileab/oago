@props([
    'props' => [],
    'preview' => false,
])

@php
    $title = (string) ($props['title'] ?? '');
    $caption = (string) ($props['caption'] ?? '');
    $image = (string) ($props['image'] ?? '');
    $linkLabel = (string) ($props['link_label'] ?? '');
    $height = (string) ($props['height'] ?? 'md');
    $rounded = (bool) ($props['rounded'] ?? true);
    $overlayColor = (string) ($props['overlay_color'] ?? '#0f172a');

    $rawLink = trim((string) ($props['link_url'] ?? ''));
    $linkUrl = ($rawLink === '' || str_starts_with($rawLink, '/') || str_starts_with($rawLink, '#') || preg_match('#^https?://#i', $rawLink))
        ? $rawLink
        : '';

    $heightClass = match ($height) {
        'sm' => 'min-h-[190px]',
        'lg' => 'min-h-[380px]',
        default => 'min-h-[280px]',
    };
@endphp

<section class="w-full {{ $rounded ? 'max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6' : '' }}">
    <div
        class="relative overflow-hidden {{ $heightClass }} flex items-end {{ $rounded ? 'rounded-2xl shadow-lg' : '' }}"
        style="background-color: {{ $overlayColor }};"
    >
        @if ($image !== '')
            <img src="{{ $image }}" alt="{{ $title }}" class="absolute inset-0 w-full h-full object-cover" loading="lazy" />
        @endif

        <div
            class="absolute inset-0 opacity-70"
            style="background: linear-gradient(to top, {{ $overlayColor }}, transparent 85%);"
        ></div>

        <div class="relative z-10 w-full p-6 sm:p-10 flex flex-col sm:flex-row sm:items-end justify-between gap-4 text-white">
            <div class="max-w-2xl">
                @if ($title !== '')
                    <h2 class="text-2xl sm:text-3xl font-black tracking-tight drop-shadow-xs">{{ $title }}</h2>
                @endif
                @if ($caption !== '')
                    <p class="mt-2 text-sm sm:text-base text-white/90">{{ $caption }}</p>
                @endif
            </div>

            @if ($linkLabel !== '' && $linkUrl !== '')
                <a
                    href="{{ $preview ? 'javascript:void(0)' : $linkUrl }}"
                    class="inline-flex items-center px-5 py-2.5 rounded-xl bg-white text-slate-900 font-bold text-sm shadow-md hover:bg-slate-100 transition shrink-0"
                >
                    {{ $linkLabel }}
                </a>
            @endif
        </div>
    </div>
</section>
