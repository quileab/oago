@props([
    'props' => [],
    'preview' => false,
])

@php
    $badge = (string) ($props['badge'] ?? '');
    $message = (string) ($props['message'] ?? '');
    $ctaLabel = (string) ($props['cta_label'] ?? '');
    $bgColor = (string) ($props['background_color'] ?? '#a6282e');
    $textColor = (string) ($props['text_color'] ?? '#ffffff');
    $dismissible = (bool) ($props['dismissible'] ?? true);

    $rawCta = trim((string) ($props['cta_url'] ?? ''));
    $ctaUrl = ($rawCta === '' || str_starts_with($rawCta, '/') || str_starts_with($rawCta, '#') || preg_match('#^(https?://|tel:|mailto:)#i', $rawCta))
        ? $rawCta
        : '/';
@endphp

<div
    x-data="{ visible: true }"
    x-show="visible"
    x-transition.opacity
    class="w-full text-xs sm:text-sm"
    style="background-color: {{ $bgColor }}; color: {{ $textColor }};"
>
    <div class="max-w-7xl mx-auto px-4 py-2 flex flex-wrap items-center justify-center gap-x-3 gap-y-1 relative pr-8">
        @if ($badge !== '')
            <span class="px-2 py-0.5 rounded bg-black/20 font-extrabold uppercase tracking-wider text-[11px]">
                {{ $badge }}
            </span>
        @endif

        @if ($message !== '')
            <span class="font-medium text-center">{{ $message }}</span>
        @endif

        @if ($ctaLabel !== '')
            <a
                href="{{ $preview ? 'javascript:void(0)' : $ctaUrl }}"
                class="underline underline-offset-2 font-bold hover:opacity-85 transition"
            >
                {{ $ctaLabel }}
            </a>
        @endif

        @if ($dismissible)
            <button
                type="button"
                @if (! $preview)
                    @click="visible = false"
                @endif
                aria-label="Cerrar aviso"
                class="absolute right-2 top-1/2 -translate-y-1/2 p-1 rounded hover:bg-black/15 transition opacity-80 hover:opacity-100"
            >
                ✕
            </button>
        @endif
    </div>
</div>
