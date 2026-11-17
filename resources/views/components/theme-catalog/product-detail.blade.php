@props([
    'blocks' => [],
    'preview' => false,
    'layerIndices' => [],
    'product' => null,
    'media' => [],
    'primaryMedia' => [],
    'tags' => [],
    'userPrice' => 0.0,
    'offerPrice' => 0.0,
    'unitPrice' => 0.0,
    'stockBadge' => ['label' => 'En Stock', 'icon' => 'o-check-circle', 'class' => 'bg-emerald-50 text-emerald-700 border-emerald-200'],
    'canBuy' => false,
    'isGuest' => false,
    'showPrices' => true,
    'guestMessage' => 'Inicie sesión para ver precios y comprar',
    'isOutOfStock' => false,
    'relatedItems' => [],
])

@php
    $byType = [];
    foreach ((array) $blocks as $idx => $block) {
        if (is_array($block) && ! empty($block['type'])) {
            $byType[$block['type']] = [
                'enabled' => (bool) ($block['enabled'] ?? true),
                'props' => (array) ($block['props'] ?? []),
                'index' => $layerIndices[$block['type']] ?? $idx,
            ];
        }
    }

    $styleBlock = $byType['detail_style'] ?? ['enabled' => true, 'props' => [], 'index' => 0];
    $styleProps = $styleBlock['props'];
    $galleryPosition = (string) ($styleProps['gallery_position'] ?? 'left');
    $columnRatio = (string) ($styleProps['column_ratio'] ?? '50-50');
    $surfaceStyle = (string) ($styleProps['surface_style'] ?? 'light');
    $primaryColor = (string) ($styleProps['primary_color'] ?? '#1b365d');
    $priceColor = (string) ($styleProps['price_color'] ?? '#15803d');
    $accentColor = (string) ($styleProps['accent_color'] ?? '#dc2626');
    $tagBgColor = (string) ($styleProps['tag_bg_color'] ?? '#d97706');
    $backButtonLabel = (string) ($styleProps['back_button_label'] ?? 'Volver al catálogo');
    $featuredBadgeText = (string) ($styleProps['featured_badge_text'] ?? 'PRODUCTO DESTACADO ⭐');
    $outOfStockText = (string) ($styleProps['out_of_stock_text'] ?? 'Agotado');

    $isDark = $surfaceStyle === 'dark';
    $isSoft = $surfaceStyle === 'soft';

    $outerPanelClass = match ($surfaceStyle) {
        'dark' => 'bg-neutral-900 text-neutral-100 border border-white/10 rounded-xl shadow-2xl',
        'soft' => 'bg-slate-50 text-slate-900 border border-slate-200 rounded-2xl shadow-lg',
        default => 'bg-white text-slate-900 border border-gray-100 rounded-2xl shadow-xl',
    };
    $galleryPanelClass = $isDark
        ? 'bg-neutral-950/60 border-white/10'
        : ($isSoft ? 'bg-white border-slate-200' : 'bg-gray-50 border-gray-100');
    $infoPanelClass = $isDark ? 'bg-neutral-900/40' : ($isSoft ? 'bg-slate-50/70' : 'bg-white');
    $subTextClass = $isDark ? 'text-neutral-300' : 'text-slate-600';
    $mutedBorderClass = $isDark ? 'border-white/10' : 'border-slate-200';
    $priceBoxClass = $isDark
        ? 'bg-neutral-950/70 border border-white/10'
        : ($isSoft ? 'bg-white border border-slate-200 shadow-xs' : 'bg-slate-50 border border-slate-100');

    [$gridClass, $gallerySpanClass, $infoSpanClass] = match ($columnRatio) {
        '45-55' => ['grid-cols-1 md:grid-cols-12', 'md:col-span-5', 'md:col-span-7'],
        '40-60' => ['grid-cols-1 md:grid-cols-5', 'md:col-span-2', 'md:col-span-3'],
        default => ['grid-cols-1 md:grid-cols-2', 'md:col-span-1', 'md:col-span-1'],
    };

    $galleryOrderClass = $galleryPosition === 'right' ? 'md:order-2 md:border-l md:border-r-0' : 'md:order-1 md:border-r';
    $infoOrderClass = $galleryPosition === 'right' ? 'md:order-1' : 'md:order-2';

    $productId = (int) ($product->id ?? 0);
    $pkg = max(1, (int) ($product->qtty_package ?? 1));
    $effectivePrice = $offerPrice > 0 ? (float) $offerPrice : (float) $userPrice;

    $orderedInfoTypes = [];
    foreach ((array) $blocks as $block) {
        if (! is_array($block) || empty($block['type'])) {
            continue;
        }
        $type = (string) $block['type'];
        if (in_array($type, ['detail_style', 'detail_related'], true) || ! ($block['enabled'] ?? true)) {
            continue;
        }
        $orderedInfoTypes[] = $type;
    }

    $relatedBlock = $byType['detail_related'] ?? ['enabled' => true, 'props' => [], 'index' => 7];
    $relatedProps = $relatedBlock['props'];
    $relatedGridClass = ((string) ($relatedProps['related_columns'] ?? '4')) === '3'
        ? 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3'
        : 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4';
@endphp

<div class="max-w-7xl mx-auto p-4 lg:p-6 {{ $isDark ? 'bg-neutral-950 text-neutral-100 rounded-2xl' : '' }}">
    <div class="mb-5 flex items-center justify-between gap-3">
        <button
            type="button"
            @if (! $preview) onclick="window.history.back()" @else wire:click.stop="selectBlock({{ $styleBlock['index'] }})" @endif
            class="btn btn-sm btn-ghost font-bold {{ $isDark ? 'text-neutral-300 hover:text-white' : 'text-slate-700 hover:text-slate-900' }}"
        >
            <x-icon name="o-arrow-left" class="w-4 h-4" />
            <span>{{ $backButtonLabel }}</span>
        </button>
    </div>

    @if ($product && $productId !== 0)
        <div class="{{ $outerPanelClass }} overflow-hidden">
            <div class="grid {{ $gridClass }}">
                {{-- Gallery Column --}}
                <div
                    @if ($preview) wire:click.stop="selectBlock({{ $styleBlock['index'] }})" @endif
                    class="{{ $gallerySpanClass }} {{ $galleryOrderClass }} {{ $galleryPanelClass }} relative flex flex-col items-center justify-center p-6 sm:p-8 border-b md:border-b-0 {{ $preview ? 'cursor-pointer' : '' }}"
                >
                    @if (! empty($product->featured) && $featuredBadgeText !== '')
                        <div class="absolute top-4 left-4 z-10">
                            <span
                                class="px-3 py-1 text-xs font-black text-white rounded-full shadow-lg"
                                style="background-color: {{ $accentColor }};"
                            >
                                {{ $featuredBadgeText }}
                            </span>
                        </div>
                    @endif

                    <div
                        x-data="{
                            activeMedia: @js($primaryMedia),
                            mediaList: @js($media),
                            getProxiedUrl(url) {
                                if (!url) return '/imgs/fallback.webp';
                                @if (! $preview)
                                    if (url.startsWith('http') && !url.includes(window.location.hostname) && !url.includes('localhost') && !url.includes('127.0.0.1')) {
                                        return '{{ route('proxy.image') }}?url=' + encodeURIComponent(url);
                                    }
                                @endif
                                return url;
                            },
                            getYouTubeEmbedUrl(url) {
                                if (!url) return '';
                                let videoId = '';
                                try {
                                    const regExp = /^.*(youtu.be\/|v\/|u\/\w\/|embed\/|watch\?v=|\&v=)([^#\&\?]*).*/;
                                    const match = url.match(regExp);
                                    if (match && match[2].length === 11) {
                                        videoId = match[2];
                                    } else if (url.includes('v=')) {
                                        videoId = url.split('v=')[1].split('&')[0];
                                    }
                                } catch (e) {
                                    console.error('Error extracting YouTube ID', e);
                                }
                                return videoId ? 'https://www.youtube.com/embed/' + videoId : url;
                            }
                        }"
                        class="w-full flex flex-col"
                    >
                        <div class="relative group flex items-center justify-center min-h-[260px] md:min-h-[380px]" id="detail-img-{{ $productId }}">
                            <template x-if="activeMedia.type === 'image'">
                                <img
                                    :src="getProxiedUrl(activeMedia.url)"
                                    class="max-h-[380px] w-auto object-contain transition-transform duration-700 group-hover:scale-105 {{ $isOutOfStock ? 'opacity-40 grayscale' : '' }}"
                                    onerror="this.src='/imgs/fallback.webp'"
                                >
                            </template>

                            <template x-if="activeMedia.type === 'video'">
                                <div class="w-full aspect-video rounded-2xl overflow-hidden shadow-2xl border-4 border-white" :key="activeMedia.url">
                                    <iframe
                                        class="w-full h-full"
                                        :src="getYouTubeEmbedUrl(activeMedia.url)"
                                        frameborder="0"
                                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                        allowfullscreen
                                        referrerpolicy="strict-origin-when-cross-origin"
                                    ></iframe>
                                </div>
                            </template>

                            @if ($isOutOfStock)
                                <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                                    <span class="px-6 py-2 bg-white/90 backdrop-blur text-slate-600 font-black rounded-xl border-2 border-slate-300 shadow-xl uppercase tracking-[0.2em]">
                                        {{ $outOfStockText }}
                                    </span>
                                </div>
                            @endif
                        </div>

                        <template x-if="mediaList.length > 1">
                            <div class="flex flex-wrap gap-3 mt-6 justify-center">
                                <template x-for="(item, index) in mediaList" :key="index">
                                    <button
                                        type="button"
                                        @click.stop="activeMedia = item"
                                        class="relative w-16 h-16 rounded-xl overflow-hidden border-2 transition-all p-0.5 bg-white shadow-sm shrink-0 cursor-pointer"
                                        :class="activeMedia.url === item.url ? 'border-blue-600 scale-105 shadow-md z-10' : 'border-slate-200 opacity-65 hover:opacity-100'"
                                    >
                                        <img :src="getProxiedUrl(item.thumb)" class="w-full h-full object-cover rounded-lg" onerror="this.src='/imgs/fallback.webp'">
                                        <template x-if="item.type === 'video'">
                                            <div class="absolute inset-0 flex items-center justify-center bg-black/15">
                                                <div class="bg-white/90 rounded-full p-1 shadow-sm">
                                                    <x-icon name="s-play" class="w-5 h-5 text-red-600" />
                                                </div>
                                            </div>
                                        </template>
                                    </button>
                                </template>
                            </div>
                        </template>
                    </div>
                </div>

                {{-- Information & Purchase Column --}}
                <div class="{{ $infoSpanClass }} {{ $infoOrderClass }} {{ $infoPanelClass }} p-6 lg:p-10 flex flex-col gap-5">
                    @foreach ($orderedInfoTypes as $blockType)
                        @php
                            $blk = $byType[$blockType];
                            $p = $blk['props'];
                            $blkIdx = $blk['index'];
                        @endphp

                        @if ($blockType === 'detail_tags')
                            @if ((bool) ($p['show_tags'] ?? true) && count($tags) > 0)
                                <div
                                    @if ($preview) wire:click.stop="selectBlock({{ $blkIdx }})" @endif
                                    class="flex flex-wrap gap-2 {{ $preview ? 'cursor-pointer hover:ring-1 hover:ring-blue-400 rounded p-0.5' : '' }}"
                                >
                                    @foreach ($tags as $tag)
                                        <span
                                            class="px-2.5 py-1 text-[10px] font-black text-white rounded-md shadow-xs uppercase tracking-wider"
                                            style="background-color: {{ $tagBgColor }};"
                                        >
                                            {{ $tag }}
                                        </span>
                                    @endforeach
                                </div>
                            @endif

                        @elseif ($blockType === 'detail_header')
                            @php
                                $titleSizeClass = match ((string) ($p['title_size'] ?? '3xl')) {
                                    '2xl' => 'text-xl sm:text-2xl',
                                    '4xl' => 'text-3xl sm:text-4xl',
                                    default => 'text-2xl sm:text-3xl',
                                };
                                $showBrand = (bool) ($p['show_brand'] ?? true) && ! empty($product->brand);
                                $showRef = (bool) ($p['show_ref'] ?? true);
                                $refPrefix = (string) ($p['ref_prefix'] ?? 'REF: ');
                            @endphp
                            <div
                                @if ($preview) wire:click.stop="selectBlock({{ $blkIdx }})" @endif
                                class="space-y-3 {{ $preview ? 'cursor-pointer hover:ring-1 hover:ring-blue-400 rounded p-0.5' : '' }}"
                            >
                                <h1 class="{{ $titleSizeClass }} font-black leading-tight">{{ $product->description ?? '' }}</h1>
                                @if ($showBrand || $showRef)
                                    <div class="flex flex-wrap items-center gap-3">
                                        @if ($showBrand)
                                            <span
                                                class="text-xs sm:text-sm font-bold px-3 py-1 rounded-lg border border-current/25"
                                                style="color: {{ $isDark ? '#ffffff' : $primaryColor }}; background-color: color-mix(in srgb, {{ $primaryColor }} 14%, transparent);"
                                            >
                                                {{ $product->brand }}
                                            </span>
                                        @endif
                                        @if ($showRef)
                                            <span class="text-xs sm:text-sm opacity-60 font-mono font-medium">{{ $refPrefix }}{{ $productId }}</span>
                                        @endif
                                    </div>
                                @endif
                            </div>

                        @elseif ($blockType === 'detail_description')
                            @php
                                $showHtmlDesc = (bool) ($p['show_html_description'] ?? true);
                                $showChecklist = (bool) ($p['show_tags_checklist'] ?? false);
                            @endphp
                            @if ($showHtmlDesc || ($showChecklist && count($tags) > 0))
                                <div
                                    @if ($preview) wire:click.stop="selectBlock({{ $blkIdx }})" @endif
                                    class="space-y-4 {{ $subTextClass }} {{ $preview ? 'cursor-pointer hover:ring-1 hover:ring-blue-400 rounded p-0.5' : '' }}"
                                >
                                    @if ($showHtmlDesc && ! empty($product->description_html))
                                        <div class="prose prose-slate max-w-none leading-relaxed text-sm {{ $isDark ? 'prose-invert' : '' }}">
                                            {!! strip_tags((string) $product->description_html, '<p><br><b><strong><i><em><ul><ol><li><a><span><div>') !!}
                                        </div>
                                    @endif

                                    @if ($showChecklist && count($tags) > 0)
                                        <ul class="flex flex-col gap-2 text-sm">
                                            @foreach (array_slice($tags, 0, 4) as $tag)
                                                <li class="flex items-center gap-2">
                                                    <x-icon name="o-check-circle" class="w-4 h-4 shrink-0" style="color: {{ $primaryColor }};" />
                                                    <span>{{ $tag }}</span>
                                                </li>
                                            @endforeach
                                        </ul>
                                    @endif
                                </div>
                            @endif

                        @elseif ($blockType === 'detail_price')
                            @if ($showPrices)
                                @php
                                    $regPrefix = (string) ($p['regular_price_prefix'] ?? 'Precio regular:');
                                    $offerBadgeLabel = (string) ($p['offer_badge_label'] ?? 'Oferta');
                                    $showUnitPrice = (bool) ($p['show_unit_price'] ?? true) && ((int) ($product->qtty_unit ?? 1)) > 1;
                                    $unitPrefix = (string) ($p['unit_price_prefix'] ?? 'Precio por unidad:');
                                    $comingSoonText = (string) ($p['coming_soon_text'] ?? 'Muy pronto');
                                @endphp
                                <div
                                    @if ($preview) wire:click.stop="selectBlock({{ $blkIdx }})" @endif
                                    class="{{ $priceBoxClass }} p-5 rounded-2xl {{ $preview ? 'cursor-pointer hover:ring-1 hover:ring-blue-400' : '' }}"
                                >
                                    @if ($effectivePrice <= 0)
                                        <span class="text-2xl font-black opacity-60 uppercase tracking-wider">{{ $comingSoonText }}</span>
                                    @else
                                        <div class="flex flex-col">
                                            @if ($offerPrice > 0)
                                                <span class="text-sm text-red-500 line-through font-bold mb-1">
                                                    {{ $regPrefix }} $ {{ number_format((float) $userPrice, 2, ',', '.') }}
                                                </span>
                                                <div class="flex items-baseline gap-2.5 flex-wrap">
                                                    <span class="text-4xl sm:text-5xl font-black tracking-tighter" style="color: {{ $priceColor }};">
                                                        $ {{ number_format((float) $offerPrice, 2, ',', '.') }}
                                                    </span>
                                                    <span
                                                        class="text-xs font-bold text-white px-2.5 py-0.5 rounded uppercase"
                                                        style="background-color: {{ $accentColor }};"
                                                    >
                                                        {{ $offerBadgeLabel }}
                                                    </span>
                                                </div>
                                            @else
                                                <span class="text-4xl sm:text-5xl font-black tracking-tighter" style="color: {{ $priceColor }};">
                                                    $ {{ number_format((float) $userPrice, 2, ',', '.') }}
                                                </span>
                                            @endif

                                            @if ($showUnitPrice)
                                                <span class="text-sm font-bold opacity-70 mt-2 flex items-center gap-1">
                                                    <x-icon name="o-tag" class="w-4 h-4" />
                                                    {{ $unitPrefix }} $ {{ number_format((float) $unitPrice, 2, ',', '.') }}
                                                </span>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            @endif

                        @elseif ($blockType === 'detail_stock')
                            @if (! $isGuest)
                                @php
                                    $showStock = (bool) ($p['show_stock_badge'] ?? true);
                                    $showPkgBadge = (bool) ($p['show_package_badge'] ?? true);
                                    $pkgPrefix = (string) ($p['package_prefix'] ?? 'Venta por bulto:');
                                @endphp
                                @if ($showStock || $showPkgBadge)
                                    <div
                                        @if ($preview) wire:click.stop="selectBlock({{ $blkIdx }})" @endif
                                        class="flex flex-wrap justify-between items-center gap-2 {{ $preview ? 'cursor-pointer hover:ring-1 hover:ring-blue-400 rounded p-0.5' : '' }}"
                                    >
                                        @if ($showStock)
                                            <span class="font-black text-xs sm:text-sm px-3 py-1.5 rounded-full border {{ $stockBadge['class'] ?? '' }}">
                                                <x-icon :name="$stockBadge['icon'] ?? 'o-check-circle'" class="w-4 h-4 inline mr-1" />
                                                {{ $stockBadge['label'] ?? '' }}
                                            </span>
                                        @endif
                                        @if ($showPkgBadge)
                                            <span class="font-bold px-3.5 py-1.5 rounded-full border {{ $mutedBorderClass }} text-xs sm:text-sm {{ $isDark ? 'bg-neutral-800 text-neutral-200' : 'bg-white text-slate-700 shadow-xs' }}">
                                                <x-icon name="o-cube" class="w-4 h-4 inline mr-1" />
                                                {{ $pkgPrefix }} {{ $pkg }} un.
                                            </span>
                                        @endif
                                    </div>
                                @endif
                            @endif

                        @elseif ($blockType === 'detail_actions')
                            @php
                                $showBulkSteps = (bool) ($p['show_bulk_step_buttons'] ?? true) && $pkg > 1;
                                $actionsLayout = (string) ($p['buttons_layout'] ?? 'stacked');
                                $buyLabel = (string) ($p['buy_button_label'] ?? 'AGREGAR AL CARRITO');
                                $btnHeightClass = ((string) ($p['button_size'] ?? 'lg')) === 'md' ? 'h-12' : 'h-14';
                                $guestMsg = (string) ($p['guest_message'] ?? $guestMessage);
                                $guestBtnLabel = (string) ($p['guest_button_label'] ?? 'Ingresar ahora');
                            @endphp
                            <div
                                @if ($preview) wire:click.stop="selectBlock({{ $blkIdx }})" @endif
                                class="pt-1 {{ $preview ? 'cursor-pointer hover:ring-1 hover:ring-blue-400 rounded p-1' : '' }}"
                            >
                                @if (! $isGuest)
                                    @if ($canBuy)
                                        <div
                                            x-data="{
                                                @if ($preview)
                                                    qtty: {{ $pkg }},
                                                @else
                                                    qtty: @entangle('qtty'),
                                                @endif
                                                step: {{ $pkg }},
                                                add(n) { this.qtty = Math.max(1, (parseInt(this.qtty) || 0) + n); },
                                                sub(n) { this.qtty = (parseInt(this.qtty) || 1) > n ? (parseInt(this.qtty) - n) : 1; }
                                            }"
                                            class="{{ $actionsLayout === 'inline' ? 'flex flex-col sm:flex-row gap-3 items-stretch' : 'space-y-4' }}"
                                        >
                                            <div class="flex items-stretch {{ $btnHeightClass }} shadow-xs rounded-xl overflow-hidden border-2 {{ $mutedBorderClass }} {{ $actionsLayout === 'inline' ? 'sm:w-1/2' : 'w-full' }}">
                                                <button type="button" @click.stop="sub(1)" class="w-14 sm:w-16 {{ $isDark ? 'bg-neutral-800 hover:bg-neutral-700 text-neutral-100' : 'bg-slate-100 hover:bg-slate-200 text-slate-700' }} text-xl font-bold transition border-r {{ $mutedBorderClass }} cursor-pointer">-</button>
                                                @if ($showBulkSteps)
                                                    <button type="button" @click.stop="sub(step)" class="w-16 sm:w-20 bg-blue-50 hover:bg-blue-100 text-xs font-black text-blue-700 border-r {{ $mutedBorderClass }} cursor-pointer">-{{ $pkg }}</button>
                                                @endif
                                                <input type="number" x-model="qtty" @click.stop class="flex-1 min-w-0 text-center text-lg sm:text-xl font-black {{ $isDark ? 'bg-neutral-900 text-white' : 'bg-white text-slate-900' }} focus:outline-none" min="1">
                                                @if ($showBulkSteps)
                                                    <button type="button" @click.stop="add(step)" class="w-16 sm:w-20 bg-blue-50 hover:bg-blue-100 text-xs font-black text-blue-700 border-l {{ $mutedBorderClass }} cursor-pointer">+{{ $pkg }}</button>
                                                @endif
                                                <button type="button" @click.stop="add(1)" class="w-14 sm:w-16 {{ $isDark ? 'bg-neutral-800 hover:bg-neutral-700 text-neutral-100' : 'bg-slate-100 hover:bg-slate-200 text-slate-700' }} text-xl font-bold transition border-l {{ $mutedBorderClass }} cursor-pointer">+</button>
                                            </div>

                                            <button
                                                type="button"
                                                @if (! $preview)
                                                    wire:click="buy({{ $productId }})"
                                                    onclick="flyToCart('detail-img-{{ $productId }}')"
                                                @else
                                                    @click.stop
                                                @endif
                                                class="{{ $actionsLayout === 'inline' ? 'sm:flex-1' : 'w-full' }} {{ $btnHeightClass }} rounded-xl text-white text-base sm:text-lg font-black shadow-lg hover:brightness-110 transition flex items-center justify-center gap-2 cursor-pointer"
                                                style="background-color: {{ $primaryColor }};"
                                            >
                                                <x-icon name="o-shopping-cart" class="w-5 h-5" />
                                                <span>{{ $buyLabel }}</span>
                                            </button>
                                        </div>
                                    @endif
                                @else
                                    <div class="p-5 rounded-2xl border {{ $isDark ? 'bg-neutral-950/60 border-white/10 text-neutral-200' : 'bg-blue-50 border-blue-100 text-blue-800' }} text-center">
                                        <x-icon name="o-lock-closed" class="w-7 h-7 mx-auto mb-2 opacity-75" />
                                        <p class="font-bold text-sm">{{ $guestMsg }}</p>
                                        <a
                                            href="{{ $preview ? 'javascript:void(0)' : '/login' }}"
                                            @if ($preview) @click.stop @endif
                                            class="mt-3 btn btn-sm border-none text-white"
                                            style="background-color: {{ $primaryColor }};"
                                        >
                                            {{ $guestBtnLabel }}
                                        </a>
                                    </div>
                                @endif
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
        </div>
    @else
        <div class="{{ $outerPanelClass }} p-12 text-center border-2 border-dashed {{ $mutedBorderClass }}">
            <x-icon name="o-face-frown" class="w-16 h-16 opacity-30 mx-auto mb-4" />
            <h1 class="text-2xl font-black opacity-60 uppercase">Producto no disponible</h1>
            <a href="{{ $preview ? 'javascript:void(0)' : '/' }}" class="mt-6 btn btn-primary">Volver a la tienda</a>
        </div>
    @endif

    {{-- Related Products Section --}}
    @if ($relatedBlock['enabled'] && count($relatedItems) > 0)
        <div
            @if ($preview) wire:click.stop="selectBlock({{ $relatedBlock['index'] }})" @endif
            class="mt-12 {{ $preview ? 'cursor-pointer hover:ring-1 hover:ring-blue-400 rounded-xl p-2' : '' }}"
        >
            <h2 class="text-xl sm:text-2xl font-black mb-6 px-1 flex items-center gap-3">
                <span class="w-2 h-8 rounded-full" style="background-color: {{ $primaryColor }};"></span>
                <span>{{ (string) ($relatedProps['related_title'] ?? 'PRODUCTOS RELACIONADOS') }}</span>
            </h2>

            <div class="grid {{ $relatedGridClass }} gap-5">
                @foreach ($relatedItems as $relProduct)
                    @if ($preview)
                        <div class="rounded-xl border {{ $mutedBorderClass }} {{ $isDark ? 'bg-neutral-900' : 'bg-white' }} p-3 shadow-xs">
                            <div class="aspect-square rounded-lg bg-slate-100 mb-2.5 flex items-center justify-center overflow-hidden p-2">
                                <img src="/imgs/fallback.webp" alt="" class="w-full h-full object-contain opacity-80" />
                            </div>
                            <div class="text-[10px] font-bold opacity-60">{{ $relProduct->brand ?? 'MARCA' }}</div>
                            <div class="text-xs font-bold line-clamp-2 mt-0.5">{{ $relProduct->description ?? '' }}</div>
                            <div class="text-sm font-black mt-2" style="color: {{ $priceColor }};">
                                $ {{ number_format((float) ($relProduct->display_price ?? 0), 2, ',', '.') }}
                            </div>
                        </div>
                    @else
                        <div wire:key="rel-{{ $relProduct->id }}">
                            <x-product-card :product="$relProduct" />
                        </div>
                    @endif
                @endforeach
            </div>
        </div>
    @endif
</div>
