@props([
    'props' => [],
    'preview' => false,
])

@php
    $badge = (string) ($props['badge'] ?? '');
    $title = (string) ($props['title'] ?? '');
    $subtitle = (string) ($props['subtitle'] ?? '');
    $layout = (string) ($props['layout'] ?? '3');
    $verticalAlign = (string) ($props['vertical_align'] ?? 'stretch');
    $style = (string) ($props['style'] ?? 'card');
    $textAlign = (string) ($props['text_align'] ?? 'left');
    $bgColor = (string) ($props['background_color'] ?? '#ffffff');
    $accentColor = (string) ($props['accent_color'] ?? '#a6282e');
    $textColor = (string) ($props['text_color'] ?? '#1e293b');
    $items = is_array($props['columns_items'] ?? null) ? $props['columns_items'] : [];

    $gridColsClass = match ($layout) {
        '2' => 'grid-cols-1 md:grid-cols-2',
        '4' => 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-4',
        '1-2', '2-1' => 'grid-cols-1 lg:grid-cols-3',
        default => 'grid-cols-1 md:grid-cols-3',
    };

    $itemsAlignClass = match ($verticalAlign) {
        'top' => 'items-start',
        'center' => 'items-center',
        default => 'items-stretch',
    };

    $isCenteredText = $textAlign === 'center';
    $cardAlignClass = $isCenteredText ? 'text-center items-center' : 'text-left items-start';

    $cardSurfaceClass = match ($style) {
        'soft' => 'rounded-2xl bg-slate-50/90 border border-slate-200/75 p-5 sm:p-6 text-slate-900',
        'plain' => 'py-2',
        default => 'rounded-2xl bg-white border border-slate-200/80 shadow-md p-5 sm:p-6 text-slate-900',
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

<section class="w-full py-12 sm:py-16" style="background-color: {{ $bgColor }}; color: {{ $textColor }};">
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
                    <h2 class="text-2xl sm:text-3xl font-black tracking-tight">{{ $title }}</h2>
                @endif
                @if ($subtitle !== '')
                    <p class="mt-2.5 text-sm sm:text-base opacity-80">{{ $subtitle }}</p>
                @endif
            </div>
        @endif

        @if (count($items) > 0)
            <div class="grid {{ $gridColsClass }} {{ $itemsAlignClass }} gap-6">
                @foreach ($items as $idx => $item)
                    @php
                        $colSpanClass = match ($layout) {
                            '1-2' => ($idx % 2 === 0) ? 'lg:col-span-1' : 'lg:col-span-2',
                            '2-1' => ($idx % 2 === 0) ? 'lg:col-span-2' : 'lg:col-span-1',
                            default => '',
                        };
                        $itemImage = (string) ($item['image'] ?? '');
                        $itemBadge = (string) ($item['badge'] ?? '');
                        $itemTitle = (string) ($item['title'] ?? '');
                        $itemContent = (string) ($item['content'] ?? '');
                        $itemBtnLabel = (string) ($item['button_label'] ?? '');
                        $itemBtnUrl = $safeHref((string) ($item['button_url'] ?? ''));
                    @endphp
                    <article class="{{ $colSpanClass }} {{ $cardSurfaceClass }} flex flex-col justify-between gap-4">
                        <div class="w-full flex flex-col {{ $cardAlignClass }}">
                            @if ($itemImage !== '')
                                <div class="w-full aspect-[16/10] rounded-xl overflow-hidden bg-slate-100 mb-4">
                                    <img
                                        src="{{ $itemImage }}"
                                        alt="{{ $itemTitle !== '' ? $itemTitle : 'Columna ' . ($idx + 1) }}"
                                        class="w-full h-full object-cover"
                                        loading="lazy"
                                    />
                                </div>
                            @elseif ($preview)
                                <div class="w-full aspect-[16/10] rounded-xl border border-dashed border-slate-300 bg-slate-100/70 mb-4 flex flex-col items-center justify-center gap-1.5 text-slate-400 px-4">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-7 h-7 opacity-70">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                                    </svg>
                                    <span class="text-[11px] font-medium">Imagen opcional — Columna #{{ $idx + 1 }}</span>
                                </div>
                            @endif

                            @if ($itemBadge !== '')
                                <span
                                    class="inline-block px-2.5 py-0.5 rounded-full text-[11px] font-bold uppercase tracking-wider text-white mb-2.5"
                                    style="background-color: {{ $accentColor }};"
                                >
                                    {{ $itemBadge }}
                                </span>
                            @endif

                            @if ($itemTitle !== '')
                                <h3 class="text-lg sm:text-xl font-bold leading-snug">{{ $itemTitle }}</h3>
                            @endif

                            @if ($itemContent !== '')
                                <p class="mt-2 text-sm leading-relaxed {{ $style === 'plain' ? 'opacity-85' : 'text-slate-600' }} whitespace-pre-line">{{ $itemContent }}</p>
                            @endif
                        </div>

                        @if ($itemBtnLabel !== '' && $itemBtnUrl !== '')
                            <div class="pt-1 w-full flex {{ $isCenteredText ? 'justify-center' : 'justify-start' }}">
                                <a
                                    href="{{ $preview ? 'javascript:void(0)' : $itemBtnUrl }}"
                                    class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs sm:text-sm font-bold text-white shadow-xs hover:brightness-110 transition"
                                    style="background-color: {{ $accentColor }};"
                                >
                                    <span>{{ $itemBtnLabel }}</span>
                                    <span aria-hidden="true">&rarr;</span>
                                </a>
                            </div>
                        @endif
                    </article>
                @endforeach
            </div>
        @endif
    </div>
</section>
