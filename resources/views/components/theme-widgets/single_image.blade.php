@props([
    'props' => [],
    'preview' => false,
])

@php
    $image = (string) ($props['image'] ?? '');
    $alt = (string) ($props['alt'] ?? '');
    $title = (string) ($props['title'] ?? '');
    $caption = (string) ($props['caption'] ?? '');
    $maxWidth = (string) ($props['max_width'] ?? 'lg');
    $align = (string) ($props['align'] ?? 'center');
    $aspectRatio = (string) ($props['aspect_ratio'] ?? 'auto');
    $rounded = (bool) ($props['rounded'] ?? true);
    $shadow = (bool) ($props['shadow'] ?? true);
    $bgColor = (string) ($props['background_color'] ?? '#ffffff');

    $isFullBleed = $maxWidth === 'full';

    $widthClass = match ($maxWidth) {
        'sm' => 'max-w-md',
        'md' => 'max-w-2xl',
        'lg' => 'max-w-4xl',
        'xl' => 'max-w-7xl',
        default => 'w-full',
    };

    $alignClass = match ($align) {
        'left' => 'mr-auto',
        'right' => 'ml-auto',
        default => 'mx-auto',
    };

    $aspectClass = match ($aspectRatio) {
        '16-9' => 'aspect-video',
        '4-3' => 'aspect-[4/3]',
        '1-1' => 'aspect-square',
        '21-9' => 'aspect-[21/9]',
        default => '',
    };

    $safeHref = static function (string $url): string {
        $trimmed = trim($url);
        if ($trimmed === '') {
            return '';
        }
        if (str_starts_with($trimmed, '/') || str_starts_with($trimmed, '#') || preg_match('#^(https?://|tel:|mailto:)#i', $trimmed)) {
            return $trimmed;
        }

        return '';
    };

    $linkUrl = $safeHref((string) ($props['link_url'] ?? ''));
    $frameClasses = trim(($rounded ? 'rounded-2xl ' : '') . ($shadow ? 'shadow-lg ' : ''));
@endphp

<section class="w-full {{ $isFullBleed ? 'py-4' : 'py-8 sm:py-12' }}" style="background-color: {{ $bgColor }};">
    <div class="{{ $isFullBleed ? 'w-full' : 'max-w-7xl mx-auto px-4 sm:px-6 lg:px-8' }}">
        <figure class="w-full {{ $widthClass }} {{ $isFullBleed ? '' : $alignClass }}">
            @if ($title !== '')
                <h3 class="text-lg sm:text-xl font-bold text-slate-900 mb-3 {{ $align === 'center' ? 'text-center' : ($align === 'right' ? 'text-right' : 'text-left') }} {{ $isFullBleed ? 'px-4 sm:px-6 lg:px-8' : '' }}">
                    {{ $title }}
                </h3>
            @endif

            @if ($image !== '')
                @php
                    $imgContainerClass = trim("overflow-hidden bg-slate-100 {$frameClasses} {$aspectClass}");
                    $imgClass = ($aspectClass !== '' ? 'w-full h-full object-cover' : 'w-full h-auto object-cover')
                        .($linkUrl !== '' ? ' transition duration-300 group-hover:scale-[1.02]' : '');
                    $mediaTag = $linkUrl !== '' ? 'a' : 'div';
                @endphp
                <{{ $mediaTag }}
                    @if ($linkUrl !== '')
                        href="{{ $preview ? 'javascript:void(0)' : $linkUrl }}"
                    @endif
                    class="{{ trim(($linkUrl !== '' ? 'block group ' : '').$imgContainerClass) }}"
                >
                    <img
                        src="{{ $image }}"
                        alt="{{ $alt !== '' ? $alt : $title }}"
                        class="{{ $imgClass }}"
                        loading="lazy"
                    />
                </{{ $mediaTag }}>
            @else
                <div class="w-full {{ $aspectClass !== '' ? $aspectClass : 'aspect-video' }} {{ $rounded ? 'rounded-2xl' : '' }} border-2 border-dashed border-slate-300 bg-slate-100/80 flex flex-col items-center justify-center gap-2 p-6 text-center text-slate-400">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-10 h-10 opacity-70">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                    </svg>
                    <span class="text-xs font-semibold text-slate-500">Imagen Individual</span>
                    @if ($preview)
                        <span class="text-[11px] opacity-80">Seleccioná o subí una imagen desde el Inspector</span>
                    @endif
                </div>
            @endif

            @if ($caption !== '')
                <figcaption class="mt-3 text-xs sm:text-sm text-slate-600 {{ $align === 'center' ? 'text-center' : ($align === 'right' ? 'text-right' : 'text-left') }} {{ $isFullBleed ? 'px-4 sm:px-6 lg:px-8' : '' }}">
                    {{ $caption }}
                </figcaption>
            @endif
        </figure>
    </div>
</section>
