@props([
    'props' => [],
    'preview' => false,
])

@php
    $badge = (string) ($props['badge'] ?? '');
    $title = (string) ($props['title'] ?? '');
    $subtitle = (string) ($props['subtitle'] ?? '');
    $accentColor = (string) ($props['accent_color'] ?? '#a6282e');
    $secondaryColor = (string) ($props['secondary_color'] ?? '#1b365d');
    $bgColor = (string) ($props['background_color'] ?? '#f8fafc');
    $timeline = is_array($props['timeline'] ?? null) ? $props['timeline'] : [];
@endphp

<section class="w-full py-12 sm:py-16" style="background-color: {{ $bgColor }};">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
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

        @if (count($timeline) > 0)
            <div class="relative space-y-6 before:content-[''] before:hidden sm:before:block before:absolute before:left-6 before:top-4 before:bottom-4 before:w-0.5 before:bg-slate-200">
                @foreach ($timeline as $item)
                    @php
                        $year = (string) ($item['year'] ?? '');
                        $itemTitle = (string) ($item['title'] ?? '');
                        $itemDesc = (string) ($item['description'] ?? '');
                        $itemImg = (string) ($item['image'] ?? '');
                    @endphp
                    <div class="relative sm:pl-16">
                        <div
                            class="hidden sm:flex absolute left-2 top-5 w-8 h-8 rounded-full items-center justify-center text-white text-xs font-black shadow-md ring-4 ring-white"
                            style="background-color: {{ $accentColor }};"
                        >
                            ●
                        </div>

                        <div class="rounded-2xl bg-white border border-slate-200/80 shadow-sm p-5 sm:p-6 flex flex-col sm:flex-row gap-5 items-start">
                            <div class="flex-1 min-w-0">
                                @if ($year !== '')
                                    <span
                                        class="inline-block px-2.5 py-0.5 rounded-md text-xs font-extrabold uppercase tracking-wide text-white mb-2"
                                        style="background-color: {{ $secondaryColor }};"
                                    >
                                        {{ $year }}
                                    </span>
                                @endif
                                @if ($itemTitle !== '')
                                    <h3 class="text-lg font-bold text-slate-900">{{ $itemTitle }}</h3>
                                @endif
                                @if ($itemDesc !== '')
                                    <p class="mt-2 text-sm text-slate-600 leading-relaxed">{{ $itemDesc }}</p>
                                @endif
                            </div>

                            @if ($itemImg !== '')
                                <div class="w-full sm:w-44 shrink-0 overflow-hidden rounded-xl border border-slate-200 bg-slate-100">
                                    <img src="{{ $itemImg }}" alt="{{ $itemTitle }}" class="w-full h-32 object-cover" loading="lazy" />
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</section>
