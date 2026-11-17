@props([
    'p' => [],
    'blkIdx' => 0,
    'preview' => false,
    'isGuest' => false,
    'stockBadge' => ['label' => 'En Stock', 'icon' => 'o-check-circle', 'class' => ''],
    'initialInCart' => 0,
])

@php
    $showStock = (bool) ($p['show_stock_badge'] ?? true);
    $showInCart = (bool) ($p['show_in_cart_badge'] ?? true);
    $inCartLabel = (string) ($p['in_cart_label'] ?? 'en carrito:');
@endphp

@if (! $isGuest && ($showStock || $showInCart))
    <div
        @if ($preview) wire:click.stop="selectBlock({{ $blkIdx }})" @endif
        class="flex flex-wrap items-center justify-between gap-2 text-[10px] px-0.5 {{ $preview ? 'cursor-pointer hover:ring-1 hover:ring-blue-400 rounded py-0.5' : '' }}"
    >
        <div>
            @if ($showStock)
                <span class="font-bold px-1.5 py-0.5 rounded border {{ $stockBadge['class'] ?? '' }}">
                    <x-icon :name="$stockBadge['icon'] ?? 'o-check-circle'" class="w-3 h-3 inline" />
                    {{ $stockBadge['label'] ?? '' }}
                </span>
            @endif
        </div>

        @if ($showInCart)
            <div>
                <span
                    class="{{ $initialInCart > 0 ? '' : 'hidden' }} text-green-700 font-black bg-green-100 px-2 py-0.5 rounded-full border border-green-200"
                    :class="{ 'hidden': inCartQty <= 0 }"
                    title="Cantidad en carrito"
                >
                    <x-icon name="o-shopping-cart" class="w-3 h-3 inline mr-0.5" />
                    <span class="uppercase">{{ $inCartLabel }}</span>
                    <span x-text="inCartQty">{{ $initialInCart }}</span>
                </span>
            </div>
        @endif
    </div>
@endif
