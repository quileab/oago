@props([
    'props' => [],
    'preview' => false,
])

@php
    $imagePosition = (string) ($props['image_position'] ?? 'left');
    $columnRatio = (string) ($props['column_ratio'] ?? '50-50');
    $image = (string) ($props['image'] ?? '');
    $imageCaption = (string) ($props['image_caption'] ?? '');
    $badge = (string) ($props['badge'] ?? '');
    $title = (string) ($props['title'] ?? '');
    $content = (string) ($props['content'] ?? '');
    $bullets = is_array($props['bullets'] ?? null) ? $props['bullets'] : [];
    $buttonLabel = (string) ($props['button_label'] ?? '');
    $rounded = (bool) ($props['rounded'] ?? true);
    $bgColor = (string) ($props['background_color'] ?? '#ffffff');
    $accentColor = (string) ($props['accent_color'] ?? '#a6282e');
    $textColor = (string) ($props['text_color'] ?? '#1e293b');

    [$gridClass, $imgSpanClass, $textSpanClass] = match ($columnRatio) {
        '40-60' => ['grid-cols-1 lg:grid-cols-5', 'lg:col-span-2', 'lg:col-span-3'],
        '60-40' => ['grid-cols-1 lg:grid-cols-5', 'lg:col-span-3', 'lg:col-span-2'],
        default => ['grid-cols-1 lg:grid-cols-2', 'lg:col-span-1', 'lg:col-span-1'],
    };

    $imgOrderClass = $imagePosition === 'right' ? 'lg:order-2' : 'lg:order-1';
    $textOrderClass = $imagePosition === 'right' ? 'lg:order-1' : 'lg:order-2';

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

    $buttonUrl = $safeHref((string) ($props['button_url'] ?? ''));
@endphp

<section class="w-full py-12 sm:py-16" style="background-color: {{ $bgColor }}; color: {{ $textColor }};">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid {{ $gridClass }} gap-8 lg:gap-12 items-center">
            {{-- Media column --}}
            <div class="{{ $imgSpanClass }} {{ $imgOrderClass }}">
                @if ($image !== '')
                    <figure class="w-full">
                        <div class="overflow-hidden {{ $rounded ? 'rounded-2xl shadow-lg' : '' }} bg-slate-100">
                            <img
                                src="{{ $image }}"
                                alt="{{ $title !== '' ? $title : 'Imagen destacada' }}"
                                class="w-full h-auto max-h-[520px] object-cover"
                                loading="lazy"
                            />
                        </div>
                        @if ($imageCaption !== '')
                            <figcaption class="mt-2.5 text-xs opacity-70 text-center">{{ $imageCaption }}</figcaption>
                        @endif
                    </figure>
                @else
                    <div class="w-full aspect-[4/3] {{ $rounded ? 'rounded-2xl' : '' }} border-2 border-dashed border-slate-300 bg-slate-100/80 flex flex-col items-center justify-center gap-2 p-6 text-center text-slate-400">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-10 h-10 opacity-70">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                        </svg>
                        <span class="text-xs font-semibold text-slate-500">Sin imagen asignada</span>
                        @if ($preview)
                            <span class="text-[11px] opacity-80">Elegí o subí una imagen en el Inspector de Bloque</span>
                        @endif
                    </div>
                @endif
            </div>

            {{-- Text column --}}
            <div class="{{ $textSpanClass }} {{ $textOrderClass }} space-y-4">
                @if ($badge !== '')
                    <span
                        class="inline-block px-3.5 py-1 rounded-full text-xs font-bold uppercase tracking-wider text-white"
                        style="background-color: {{ $accentColor }};"
                    >
                        {{ $badge }}
                    </span>
                @endif

                @if ($title !== '')
                    <h2 class="text-2xl sm:text-3xl lg:text-4xl font-black tracking-tight leading-tight">{{ $title }}</h2>
                @endif

                @if ($content !== '')
                    <p class="text-sm sm:text-base leading-relaxed opacity-85 whitespace-pre-line">{{ $content }}</p>
                @endif

                @if (count($bullets) > 0)
                    <ul class="space-y-2.5 pt-1">
                        @foreach ($bullets as $bullet)
                            @php
                                $bulletText = trim((string) (is_array($bullet) ? ($bullet['text'] ?? '') : $bullet));
                            @endphp
                            @if ($bulletText !== '')
                                <li class="flex items-start gap-2.5 text-sm sm:text-base">
                                    <span
                                        class="mt-0.5 w-5 h-5 rounded-full flex items-center justify-center text-white shrink-0"
                                        style="background-color: {{ $accentColor }};"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-3 h-3">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                        </svg>
                                    </span>
                                    <span class="opacity-90">{{ $bulletText }}</span>
                                </li>
                            @endif
                        @endforeach
                    </ul>
                @endif

                @if ($buttonLabel !== '' && $buttonUrl !== '')
                    <div class="pt-2">
                        <a
                            href="{{ $preview ? 'javascript:void(0)' : $buttonUrl }}"
                            class="inline-flex items-center gap-2 px-6 py-3 rounded-xl text-sm font-bold text-white shadow-md hover:brightness-110 transition"
                            style="background-color: {{ $accentColor }};"
                        >
                            <span>{{ $buttonLabel }}</span>
                            <span aria-hidden="true">&rarr;</span>
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</section>
