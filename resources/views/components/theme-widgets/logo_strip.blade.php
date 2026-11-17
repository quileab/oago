@props([
    'props' => [],
    'preview' => false,
])

@php
    $title = (string) ($props['title'] ?? '');
    $subtitle = (string) ($props['subtitle'] ?? '');
    $bgColor = (string) ($props['background_color'] ?? '#ffffff');
    $accentColor = (string) ($props['accent_color'] ?? '#1b365d');
    $itemHeight = (string) ($props['item_height'] ?? 'md');
    $logos = is_array($props['logos'] ?? null) ? $props['logos'] : [];

    $heightClass = match ($itemHeight) {
        'sm' => 'h-7 md:h-8',
        'lg' => 'h-12 md:h-14',
        default => 'h-9 md:h-11',
    };

    $safeHref = static function (string $url): string {
        $trimmed = trim($url);
        if ($trimmed === '') {
            return '';
        }
        if (str_starts_with($trimmed, '/') || str_starts_with($trimmed, '#') || preg_match('#^(https?://)#i', $trimmed)) {
            return $trimmed;
        }

        return '';
    };
@endphp

<section class="w-full py-6" style="background-color: {{ $bgColor }};">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        @if ($title !== '' || $subtitle !== '')
            <div class="text-center mb-5">
                @if ($title !== '')
                    <h2 class="text-sm font-bold uppercase tracking-wider" style="color: {{ $accentColor }};">
                        {{ $title }}
                    </h2>
                @endif
                @if ($subtitle !== '')
                    <p class="mt-1 text-xs text-slate-500">{{ $subtitle }}</p>
                @endif
            </div>
        @endif

        @if (count($logos) > 0)
            <div class="rounded-2xl border border-slate-200/80 bg-white/90 shadow-xs py-4 px-6 flex flex-wrap justify-center items-center gap-6 md:gap-10">
                @foreach ($logos as $logo)
                    @php
                        $logoName = (string) ($logo['name'] ?? 'Logo');
                        $logoImg = (string) ($logo['image'] ?? '');
                        $logoUrl = $safeHref((string) ($logo['url'] ?? ''));
                    @endphp
                    @if ($logoImg !== '')
                        @if ($logoUrl !== '' && ! $preview)
                            <a href="{{ $logoUrl }}" target="_blank" rel="noopener noreferrer" class="transition opacity-85 hover:opacity-100 hover:scale-105">
                                <img src="{{ $logoImg }}" alt="{{ $logoName }}" class="{{ $heightClass }} w-auto object-contain" loading="lazy" />
                            </a>
                        @else
                            <div class="flex items-center">
                                <img src="{{ $logoImg }}" alt="{{ $logoName }}" class="{{ $heightClass }} w-auto object-contain opacity-90" loading="lazy" />
                            </div>
                        @endif
                    @elseif ($logoName !== '')
                        <span class="px-3 py-1.5 rounded-lg bg-slate-100 text-slate-600 text-xs font-semibold">
                            {{ $logoName }}
                        </span>
                    @endif
                @endforeach
            </div>
        @elseif ($preview)
            <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 py-6 px-4 text-center text-xs text-slate-500">
                Agregá logos en el inspector lateral para mostrar marcas o medios de pago.
            </div>
        @endif
    </div>
</section>
