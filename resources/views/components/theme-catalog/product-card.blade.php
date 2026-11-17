@props([
    'blocks' => [],
    'preview' => false,
    'layerIndices' => [],
    'product' => null,
    'displayPrice' => 0.0,
    'displayOffer' => 0.0,
    'cart' => [],
    'isGuest' => false,
    'showPrices' => true,
    'guestMessage' => 'Regístrese para ver precios',
    'isOutOfStock' => false,
    'hasAvailableStock' => true,
    'stockBadge' => ['label' => 'En Stock', 'icon' => 'o-check-circle', 'class' => 'bg-emerald-50 text-emerald-700 border-emerald-200'],
    'cartPayload' => [],
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

    $styleProps = $byType['card_style']['props'] ?? [];
    $layout = (string) ($styleProps['layout'] ?? 'horizontal');
    $roundedClass = match ((string) ($styleProps['rounded'] ?? 'rounded-xl')) {
        'rounded-none', 'rounded-lg', 'rounded-xl', 'rounded-2xl' => (string) $styleProps['rounded'],
        default => 'rounded-xl',
    };
    $shadowClass = match ((string) ($styleProps['shadow'] ?? 'shadow-md')) {
        'shadow-sm', 'shadow-md', 'shadow-lg' => (string) $styleProps['shadow'],
        default => 'shadow-md',
    };

    $cardBg = (string) ($styleProps['card_bg'] ?? '#ffffff');
    $imageBg = (string) ($styleProps['image_bg'] ?? '#f8fafc');
    $textColor = (string) ($styleProps['text_color'] ?? '#111827');
    $primaryColor = (string) ($styleProps['primary_color'] ?? '#1b365d');
    $secondaryColor = (string) ($styleProps['secondary_color'] ?? '#f97316');
    $priceColor = (string) ($styleProps['price_color'] ?? '#15803d');
    $offerBadgeColor = (string) ($styleProps['offer_badge_color'] ?? '#dc2626');
    $tagBgColor = (string) ($styleProps['tag_bg_color'] ?? '#d97706');

    $enableDivisionColors = (bool) ($styleProps['enable_division_colors'] ?? false);
    if ($enableDivisionColors) {
        $resolvedDivision = is_array($product->division ?? null)
            ? $product->division
            : \App\Models\Product::resolveDivisionFromRaw($product);
        $divisionColor = (string) ($resolvedDivision['color'] ?? $primaryColor);
        $primaryColor = $divisionColor;
        $offerBadgeColor = $divisionColor;
    }

    $imageBlock = $byType['card_image'] ?? ['enabled' => true, 'props' => [], 'index' => 1];
    $imageProps = $imageBlock['props'];
    $tagsBlock = $byType['card_tags'] ?? ['enabled' => true, 'props' => [], 'index' => 2];
    $tagsProps = $tagsBlock['props'];
    $tagsOverlay = $tagsBlock['enabled'] && (($tagsProps['position'] ?? 'body') === 'overlay');

    $aspectClass = match ((string) ($imageProps['aspect_ratio'] ?? '1-1')) {
        '4-3' => 'aspect-[4/3]',
        'auto' => '',
        default => 'aspect-square',
    };

    $maxTagsRaw = (string) ($tagsProps['max_tags'] ?? '3');
    $allTags = (array) ($product->commercial_tags_array ?? \App\Models\Tag::filterCommercialNames((array) ($product->tags_array ?? [])));
    $visibleTags = $maxTagsRaw === 'all' ? $allTags : array_slice($allTags, 0, max(1, (int) $maxTagsRaw));

    $productId = (int) ($product->id ?? 0);
    $pkg = max(1, (int) ($product->qtty_package ?? 1));
    $initialMode = $pkg > 1 ? 'bulk' : 'unit';
    $initialQtty = $pkg;
    $initialInCart = (int) ($cart[$productId]['quantity'] ?? 0);
    $effectivePrice = $displayOffer > 0 ? (float) $displayOffer : (float) $displayPrice;

    $currUser = $preview ? ($isGuest ? null : (object) ['role' => (object) ['value' => 'customer']]) : current_user();
    $canBuy = $hasAvailableStock && $currUser && ! in_array($currUser->role->value ?? '', ['none', 'guest'], true) && $effectivePrice > 0;
    $detailHref = $preview ? 'javascript:void(0)' : "./?product_id={$productId}";

    $isHorizontal = $layout === 'horizontal';
    $isCompact = $layout === 'compact';
    $bodyPadding = $isCompact ? 'p-2.5' : 'p-3.5';

    $orderedContentTypes = [];
    $orderedFooterTypes = [];
    foreach ((array) $blocks as $block) {
        if (! is_array($block) || empty($block['type'])) {
            continue;
        }
        $type = (string) $block['type'];
        if (in_array($type, ['card_style', 'card_image'], true)) {
            continue;
        }
        if ($type === 'card_tags' && $tagsOverlay) {
            continue;
        }
        if (! ($block['enabled'] ?? true)) {
            continue;
        }
        if ($isHorizontal && in_array($type, ['card_stock', 'card_actions'], true)) {
            $orderedFooterTypes[] = $type;
        } else {
            $orderedContentTypes[] = $type;
        }
    }
@endphp

<div
    @if (! $preview) wire:key="prod-card-{{ $productId }}" @endif
    x-data="{
        pkg: {{ $pkg }},
        mode: '{{ $initialMode }}',
        qtty: {{ $initialQtty }},
        inCartQty: {{ $initialInCart }},
        setMode(m) {
            this.mode = m;
            this.qtty = m === 'bulk' ? this.pkg : 1;
        },
        dec() {
            const step = this.mode === 'bulk' ? this.pkg : 1;
            const current = parseInt(this.qtty) || step;
            this.qtty = current > step ? current - step : step;
        },
        inc() {
            const step = this.mode === 'bulk' ? this.pkg : 1;
            const current = parseInt(this.qtty) || 0;
            this.qtty = current + step;
        },
        get subtitle() {
            const q = parseInt(this.qtty) || 1;
            if (this.mode === 'bulk') {
                const b = +(q / this.pkg).toFixed(2);
                return `${b} bulto${b !== 1 ? 's' : ''} · ${q} un.`;
            }
            return `${q} unidad${q !== 1 ? 'es' : ''}`;
        },
        addToCart() {
            const qty = Math.max(1, parseInt(this.qtty) || 1);
            @if (! $preview)
                if (window.Livewire) {
                    window.Livewire.dispatch('addToCart', { product: @js($cartPayload), quantity: qty });
                }
            @else
                this.inCartQty += qty;
            @endif
            this.qtty = this.mode === 'bulk' ? this.pkg : 1;
        },
        searchSimilar() {
            @if (! $preview)
                if (window.Livewire) {
                    window.Livewire.dispatch('updateProducts', {
                        filters: {
                            category: null,
                            brand: null,
                            search: null,
                            tag: null,
                            similar: @js($product->model ?? ''),
                            resetPage: true
                        }
                    });
                }
            @endif
        }
    }"
    @if (! $preview)
        x-on:cart-updated.window="
            const qMap = Array.isArray($event.detail) ? $event.detail[0]?.quantities : $event.detail?.quantities;
            if (qMap !== undefined) {
                inCartQty = parseInt(qMap[{{ $productId }}] ?? 0);
            }
        "
    @endif
    class="card {{ $roundedClass }} {{ $shadowClass }} border border-slate-200/80 overflow-hidden hover:shadow-lg transition-all duration-300 flex flex-col h-full group"
    style="background-color: {{ $cardBg }}; color: {{ $textColor }};"
>
    <div class="{{ $isHorizontal ? 'flex flex-grow' : 'flex flex-col flex-grow' }}">
        {{-- Image Section --}}
        @if ($imageBlock['enabled'])
            @php
                $showFeaturedBadge = (bool) ($imageProps['show_featured_badge'] ?? true);
                $featuredBadgeText = (string) ($imageProps['featured_badge_text'] ?? '⭐');
                $offerBadgeText = (string) ($imageProps['offer_badge_text'] ?? 'OFERTA');
                $outOfStockText = (string) ($imageProps['out_of_stock_text'] ?? 'Agotado');
                $hoverZoom = (bool) ($imageProps['hover_zoom'] ?? true);
                $showFavorite = (bool) ($imageProps['show_favorite_icon'] ?? false);
            @endphp
            <div
                @if ($preview) wire:click.stop="selectBlock({{ $imageBlock['index'] }})" @endif
                class="{{ $isHorizontal ? 'w-[45%] border-r border-slate-100 shrink-0' : 'w-full border-b border-slate-100' }} relative flex items-center justify-center overflow-hidden {{ $preview ? 'cursor-pointer hover:ring-2 hover:ring-blue-500/60 hover:ring-inset' : '' }}"
                style="background-color: {{ $imageBg }};"
            >
                @if ($showFeaturedBadge && ($displayOffer > 0 || ! empty($product->featured)))
                    <div class="absolute top-0 left-0 z-10 pointer-events-none">
                        <span
                            class="text-[9px] font-black px-2 py-0.5 text-white rounded-br-lg shadow-sm uppercase tracking-wider inline-block"
                            style="background-color: {{ $offerBadgeColor }};"
                        >
                            {{ $displayOffer > 0 ? $offerBadgeText : $featuredBadgeText }}
                        </span>
                    </div>
                @endif

                @if ($tagsOverlay && count($visibleTags) > 0)
                    <div
                        @if ($preview) wire:click.stop="selectBlock({{ $tagsBlock['index'] }})" @endif
                        class="absolute top-2 left-2 z-10 flex flex-col gap-1 {{ $preview ? 'cursor-pointer' : '' }}"
                    >
                        @foreach ($visibleTags as $tagName)
                            <span
                                class="px-1.5 py-0.5 text-[9px] font-black text-white rounded shadow-sm uppercase tracking-wider"
                                style="background-color: {{ $tagBgColor }};"
                            >
                                {{ $tagName }}
                            </span>
                        @endforeach
                    </div>
                @endif

                @if ($showFavorite)
                    <div class="absolute top-2 right-2 z-10 bg-white/90 rounded-full p-1 shadow-xs pointer-events-none">
                        <x-icon name="s-heart" class="w-4 h-4" style="color: {{ $offerBadgeColor }};" />
                    </div>
                @endif

                <a href="{{ $detailHref }}" class="w-full {{ $aspectClass }} flex items-center justify-center p-2" id="prod-img-{{ $productId }}">
                    <x-image-proxy
                        url="{{ $product->image_url ?? '' }}"
                        class="w-full h-full object-contain {{ $hoverZoom ? 'transition-transform duration-300 group-hover:scale-105' : '' }} {{ $isOutOfStock ? 'opacity-40 grayscale' : '' }}"
                    />
                </a>

                @if ($isOutOfStock)
                    <div class="absolute inset-0 flex items-center justify-center bg-white/35 pointer-events-none">
                        <span class="text-[9px] font-black px-2.5 py-0.5 text-slate-600 uppercase tracking-widest border border-slate-300 rounded bg-white/90 shadow-xs">
                            {{ $outOfStockText }}
                        </span>
                    </div>
                @endif
            </div>
        @else
            <div id="prod-img-{{ $productId }}" class="hidden">
                <x-image-proxy url="{{ $product->image_url ?? '' }}" class="w-8 h-8 object-contain" />
            </div>
        @endif

        {{-- Body + Footer Content --}}
        <div class="{{ $isHorizontal ? 'w-[55%]' : 'w-full' }} {{ $bodyPadding }} flex-1 flex flex-col gap-2">
            @foreach ($orderedContentTypes as $blockType)
                @php
                    $blk = $byType[$blockType];
                    $p = $blk['props'];
                    $blkIdx = $blk['index'];
                @endphp

                @if ($blockType === 'card_tags' && count($visibleTags) > 0)
                    <div
                        @if ($preview) wire:click.stop="selectBlock({{ $blkIdx }})" @endif
                        class="flex flex-wrap gap-1 {{ $preview ? 'cursor-pointer hover:ring-1 hover:ring-blue-400 rounded' : '' }}"
                    >
                        @foreach ($visibleTags as $tagName)
                            <span
                                class="px-1.5 py-0.5 text-[9px] font-black text-white rounded shadow-xs uppercase tracking-wider"
                                style="background-color: {{ $tagBgColor }};"
                            >
                                {{ $tagName }}
                            </span>
                        @endforeach
                    </div>

                @elseif ($blockType === 'card_meta')
                    @php
                        $showBrand = (bool) ($p['show_brand'] ?? true) && ! empty($product->brand);
                        $showId = (bool) ($p['show_id'] ?? true);
                        $metaAlign = match ((string) ($p['align'] ?? 'left')) {
                            'center' => 'justify-center',
                            'between' => 'justify-between',
                            default => 'justify-start',
                        };
                    @endphp
                    @if ($showBrand || $showId)
                        <div
                            @if ($preview) wire:click.stop="selectBlock({{ $blkIdx }})" @endif
                            class="flex flex-wrap items-center gap-1.5 {{ $metaAlign }} {{ $preview ? 'cursor-pointer hover:ring-1 hover:ring-blue-400 rounded' : '' }}"
                        >
                            @if ($showBrand)
                                <span
                                    class="text-[10px] font-bold px-1.5 py-0.5 rounded border border-current/20"
                                    style="color: {{ $primaryColor }}; background-color: color-mix(in srgb, {{ $primaryColor }} 10%, transparent);"
                                >
                                    {{ $product->brand }}
                                </span>
                            @endif
                            @if ($showId)
                                <span class="text-[10px] opacity-55 font-mono">
                                    {{ (string) ($p['id_prefix'] ?? '#') }}{{ $productId }}
                                </span>
                            @endif
                        </div>
                    @endif

                @elseif ($blockType === 'card_title')
                    @php
                        $clampClass = match ((string) ($p['lines'] ?? '2')) {
                            '1' => 'line-clamp-1',
                            '3' => 'line-clamp-3',
                            default => 'line-clamp-2',
                        };
                        $sizeClass = match ((string) ($p['font_size'] ?? 'sm')) {
                            'xs' => 'text-xs',
                            'base' => 'text-base',
                            default => 'text-sm',
                        };
                        $upperClass = (bool) ($p['uppercase'] ?? false) ? 'uppercase' : '';
                    @endphp
                    <div
                        @if ($preview) wire:click.stop="selectBlock({{ $blkIdx }})" @endif
                        class="{{ $preview ? 'cursor-pointer hover:ring-1 hover:ring-blue-400 rounded' : '' }}"
                    >
                        <a href="{{ $detailHref }}" class="block hover:opacity-80 transition">
                            <h2 class="{{ $sizeClass }} {{ $upperClass }} {{ $clampClass }} font-bold leading-snug" title="{{ $product->description ?? '' }}">
                                {{ $product->description ?? '' }}
                            </h2>
                        </a>
                        @if ((bool) ($p['show_divider'] ?? false))
                            <hr class="border-slate-200/80 mt-2">
                        @endif
                    </div>

                @elseif ($blockType === 'card_excerpt')
                    @php
                        $excerptClamp = ((string) ($p['lines'] ?? '2')) === '3' ? 'line-clamp-3' : 'line-clamp-2';
                    @endphp
                    @if (! empty($product->description_html))
                        <div
                            @if ($preview) wire:click.stop="selectBlock({{ $blkIdx }})" @endif
                            class="text-[11px] leading-relaxed opacity-75 {{ $excerptClamp }} border-t border-slate-100 pt-1 overflow-hidden {{ $preview ? 'cursor-pointer hover:ring-1 hover:ring-blue-400 rounded' : '' }}"
                        >
                            {!! strip_tags((string) $product->description_html, '<p><br><b><strong><i><em><ul><ol><li><a><span><div>') !!}
                        </div>
                    @endif

                @elseif ($blockType === 'card_price')
                    @php
                        $priceAlign = match ((string) ($p['align'] ?? 'right')) {
                            'left' => 'items-start text-left',
                            'center' => 'items-center text-center',
                            default => 'items-end text-right',
                        };
                        $showUnitPrice = (bool) ($p['show_unit_price'] ?? true) && ((int) ($product->qtty_unit ?? 1)) > 1;
                    @endphp
                    <div
                        @if ($preview) wire:click.stop="selectBlock({{ $blkIdx }})" @endif
                        class="flex flex-col {{ $priceAlign }} mt-auto pt-1 {{ $preview ? 'cursor-pointer hover:ring-1 hover:ring-blue-400 rounded p-0.5' : '' }}"
                    >
                        @if ($showPrices)
                            @if ($effectivePrice <= 0)
                                <span class="text-[10px] font-bold px-2 py-0.5 text-slate-500 bg-slate-100 rounded border border-slate-200 uppercase tracking-wider">
                                    {{ (string) ($p['coming_soon_text'] ?? 'Muy pronto') }}
                                </span>
                            @else
                                @if ($displayOffer > 0)
                                    <span class="text-[11px] text-red-500 line-through font-bold">
                                        $ {{ number_format((float) $displayPrice, 2, ',', '.') }}
                                    </span>
                                    <span class="text-xl sm:text-2xl font-black leading-none" style="color: {{ $priceColor }};">
                                        $ {{ number_format((float) $displayOffer, 2, ',', '.') }}
                                    </span>
                                @else
                                    <span class="text-lg sm:text-xl font-black leading-none" style="color: {{ $priceColor }};">
                                        $ {{ number_format((float) $displayPrice, 2, ',', '.') }}
                                    </span>
                                @endif

                                @if ($showUnitPrice)
                                    <span class="text-[11px] font-bold opacity-65 mt-0.5">
                                        $ {{ number_format($effectivePrice / max(1, (int) $product->qtty_unit), 2, ',', '.') }} {{ (string) ($p['unit_price_suffix'] ?? 'x un.') }}
                                    </span>
                                @endif
                            @endif
                        @else
                            <div class="text-[10px] opacity-65 italic leading-tight">
                                {{ (string) ($p['guest_price_text'] ?? 'Precios solo usuarios registrados') }}
                            </div>
                        @endif
                    </div>

                @elseif ($blockType === 'card_stock')
                    @include('components.theme-catalog.partials.card-stock', [
                        'p' => $p,
                        'blkIdx' => $blkIdx,
                        'preview' => $preview,
                        'isGuest' => $isGuest,
                        'stockBadge' => $stockBadge,
                        'initialInCart' => $initialInCart,
                    ])

                @elseif ($blockType === 'card_actions')
                    @include('components.theme-catalog.partials.card-actions', [
                        'p' => $p,
                        'blkIdx' => $blkIdx,
                        'preview' => $preview,
                        'isGuest' => $isGuest,
                        'guestMessage' => $guestMessage,
                        'canBuy' => $canBuy,
                        'productId' => $productId,
                        'pkg' => $pkg,
                        'primaryColor' => $primaryColor,
                        'secondaryColor' => $secondaryColor,
                    ])
                @endif
            @endforeach
        </div>
    </div>

    {{-- Horizontal Layout Bottom Bar --}}
    @if ($isHorizontal && count($orderedFooterTypes) > 0)
        <div class="p-2.5 border-t border-slate-100 space-y-2" style="background-color: {{ $cardBg }};">
            @foreach ($orderedFooterTypes as $footerType)
                @php
                    $blk = $byType[$footerType];
                    $p = $blk['props'];
                    $blkIdx = $blk['index'];
                @endphp
                @if ($footerType === 'card_stock')
                    @include('components.theme-catalog.partials.card-stock', [
                        'p' => $p,
                        'blkIdx' => $blkIdx,
                        'preview' => $preview,
                        'isGuest' => $isGuest,
                        'stockBadge' => $stockBadge,
                        'initialInCart' => $initialInCart,
                    ])
                @elseif ($footerType === 'card_actions')
                    @include('components.theme-catalog.partials.card-actions', [
                        'p' => $p,
                        'blkIdx' => $blkIdx,
                        'preview' => $preview,
                        'isGuest' => $isGuest,
                        'guestMessage' => $guestMessage,
                        'canBuy' => $canBuy,
                        'productId' => $productId,
                        'pkg' => $pkg,
                        'primaryColor' => $primaryColor,
                        'secondaryColor' => $secondaryColor,
                    ])
                @endif
            @endforeach
        </div>
    @endif
</div>
