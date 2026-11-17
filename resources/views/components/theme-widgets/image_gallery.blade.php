@props([
    'props' => [],
    'preview' => false,
])

@php
    $badge = (string) ($props['badge'] ?? '');
    $title = (string) ($props['title'] ?? '');
    $subtitle = (string) ($props['subtitle'] ?? '');
    $columns = (string) ($props['columns'] ?? '3');
    $aspectRatio = (string) ($props['aspect_ratio'] ?? '4-3');
    $gap = (string) ($props['gap'] ?? 'md');
    $rounded = (bool) ($props['rounded'] ?? true);
    $bgColor = (string) ($props['background_color'] ?? '#ffffff');
    $accentColor = (string) ($props['accent_color'] ?? '#a6282e');
    $images = is_array($props['images'] ?? null) ? $props['images'] : [];

    $gridColsClass = match ($columns) {
        '2' => 'grid-cols-1 sm:grid-cols-2',
        '4' => 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-4',
        default => 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3',
    };

    $aspectClass = match ($aspectRatio) {
        '1-1' => 'aspect-square',
        '16-9' => 'aspect-video',
        'auto' => '',
        default => 'aspect-[4/3]',
    };

    $gapClass = match ($gap) {
        'sm' => 'gap-3',
        'lg' => 'gap-8',
        default => 'gap-5',
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

        @if (count($images) > 0)
            <div class="grid {{ $gridColsClass }} {{ $gapClass }}">
                @foreach ($images as $idx => $item)
                    @php
                        $itemImg = (string) ($item['image'] ?? '');
                        $itemTitle = (string) ($item['title'] ?? '');
                        $itemCaption = (string) ($item['caption'] ?? '');
                        $itemUrl = $safeHref((string) ($item['url'] ?? ''));
                        $wrapperTag = $itemUrl !== '' ? 'a' : 'figure';
                        $captionTag = $itemUrl !== '' ? 'div' : 'figcaption';
                        $cardClasses = 'group overflow-hidden bg-white border border-slate-200/80 shadow-sm flex flex-col '
                            .($rounded ? 'rounded-2xl ' : '')
                            .($itemUrl !== '' ? 'hover:shadow-md transition' : '');
                        $mediaWrapperClass = 'w-full overflow-hidden bg-slate-100 '.($aspectClass !== '' ? $aspectClass : 'aspect-[4/3]');
                    @endphp

                    <{{ $wrapperTag }}
                        @if ($itemUrl !== '')
                            href="{{ $preview ? 'javascript:void(0)' : $itemUrl }}"
                        @endif
                        class="{{ $cardClasses }}"
                    >
                        @if ($itemImg !== '')
                            <div class="{{ $aspectClass !== '' ? $mediaWrapperClass : 'w-full overflow-hidden bg-slate-100' }}">
                                <img
                                    src="{{ $itemImg }}"
                                    alt="{{ $itemTitle !== '' ? $itemTitle : 'Imagen ' . ($idx + 1) }}"
                                    class="w-full {{ $aspectClass !== '' ? 'h-full object-cover' : 'h-auto object-cover' }} {{ $itemUrl !== '' ? 'transition duration-300 group-hover:scale-105' : '' }}"
                                    loading="lazy"
                                />
                            </div>
                        @else
                            <div class="{{ $mediaWrapperClass }} border-b border-dashed border-slate-300 flex flex-col items-center justify-center gap-1.5 text-slate-400 p-4">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8 opacity-70">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                                </svg>
                                <span class="text-[11px] font-medium">Imagen #{{ $idx + 1 }}</span>
                            </div>
                        @endif

                        @if ($itemTitle !== '' || $itemCaption !== '')
                            <{{ $captionTag }} class="p-4">
                                @if ($itemTitle !== '')
                                    <h3 class="text-sm font-bold text-slate-900 {{ $itemUrl !== '' ? 'group-hover:underline' : '' }}">{{ $itemTitle }}</h3>
                                @endif
                                @if ($itemCaption !== '')
                                    <p class="mt-1 text-xs text-slate-600">{{ $itemCaption }}</p>
                                @endif
                            </{{ $captionTag }}>
                        @endif
                    </{{ $wrapperTag }}>
                @endforeach
            </div>
        @endif
    </div>
</section>
