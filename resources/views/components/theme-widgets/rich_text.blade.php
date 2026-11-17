@props([
    'props' => [],
    'preview' => false,
])

@php
    $badge = (string) ($props['badge'] ?? '');
    $title = (string) ($props['title'] ?? '');
    $maxWidth = (string) ($props['max_width'] ?? 'medium');
    $bgColor = (string) ($props['background_color'] ?? '#ffffff');
    $textColor = (string) ($props['text_color'] ?? '#1e293b');
    $accentColor = (string) ($props['accent_color'] ?? '#a6282e');

    $widthClass = match ($maxWidth) {
        'narrow' => 'max-w-3xl',
        'wide' => 'max-w-7xl',
        'full' => 'max-w-none',
        default => 'max-w-5xl',
    };
@endphp

<section class="w-full py-10 sm:py-14" style="background-color: {{ $bgColor }}; color: {{ $textColor }};">
    <div class="{{ $widthClass }} mx-auto px-4 sm:px-6 lg:px-8">
        @if ($badge !== '' || $title !== '')
            <div class="mb-6">
                @if ($badge !== '')
                    <span
                        class="inline-block px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider text-white mb-3"
                        style="background-color: {{ $accentColor }};"
                    >
                        {{ $badge }}
                    </span>
                @endif
                @if ($title !== '')
                    <h2 class="text-2xl sm:text-3xl font-black tracking-tight">
                        {{ $title }}
                    </h2>
                @endif
            </div>
        @endif

        <div class="max-w-none text-base leading-relaxed space-y-4 [&_h2]:text-2xl [&_h2]:font-bold [&_h2]:mt-6 [&_h3]:text-xl [&_h3]:font-semibold [&_h3]:mt-4 [&_ul]:list-disc [&_ul]:pl-6 [&_ol]:list-decimal [&_ol]:pl-6 [&_a]:underline [&_a]:font-medium [&_blockquote]:border-l-4 [&_blockquote]:pl-4 [&_blockquote]:italic [&_img]:rounded-xl [&_img]:max-w-full">
            @if (isset($htmlContent) && $htmlContent->isNotEmpty())
                {{ $htmlContent }}
            @else
                {!! $props['html'] ?? '' !!}
            @endif
        </div>
    </div>
</section>
