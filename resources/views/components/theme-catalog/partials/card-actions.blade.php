@props([
    'p' => [],
    'blkIdx' => 0,
    'preview' => false,
    'isGuest' => false,
    'guestMessage' => 'Regístrese para ver precios',
    'canBuy' => false,
    'productId' => 0,
    'pkg' => 1,
    'primaryColor' => '#1b365d',
    'secondaryColor' => '#f97316',
])

@php
    $showBulkToggle = (bool) ($p['show_bulk_toggle'] ?? true) && $pkg > 1;
    $bulkLabel = (string) ($p['bulk_label'] ?? 'BULTO');
    $unitLabel = (string) ($p['unit_label'] ?? 'UNIDAD');
    $showQtySelector = (bool) ($p['show_qty_selector'] ?? true);
    $qtyHeightClass = ((string) ($p['qty_height'] ?? 'md')) === 'sm' ? 'h-8' : 'h-11';
    $buttonsLayout = (string) ($p['buttons_layout'] ?? 'similar_add_row');
    $addLabel = (string) ($p['add_button_label'] ?? 'AGREGAR');
    $similarLabel = (string) ($p['similar_button_label'] ?? 'Similares');
    $guestStyle = (string) ($p['guest_actions_style'] ?? 'message_only');
    $guestRegisterLabel = (string) ($p['guest_register_label'] ?? 'REGISTRARSE');
    $guestLoginLabel = (string) ($p['guest_login_label'] ?? 'INGRESAR');
@endphp

<div
    @if ($preview) wire:click.stop="selectBlock({{ $blkIdx }})" @endif
    class="pt-1 {{ $preview ? 'cursor-pointer hover:ring-1 hover:ring-blue-400 rounded p-0.5' : '' }}"
>
    @if ($isGuest)
        @if ($guestStyle === 'register_and_login')
            <div class="flex flex-col gap-1.5">
                <a
                    href="{{ $preview ? 'javascript:void(0)' : '/registrate' }}"
                    @if ($preview) @click.stop @endif
                    class="w-full bg-slate-500 hover:bg-slate-600 text-white text-[10px] font-bold py-2 rounded-lg flex items-center justify-center gap-1.5 transition uppercase"
                >
                    <x-icon name="o-lock-closed" class="w-3.5 h-3.5" /> {{ $guestRegisterLabel }}
                </a>
                <a
                    href="{{ $preview ? 'javascript:void(0)' : '/login' }}"
                    @if ($preview) @click.stop @endif
                    class="w-full text-white text-[10px] font-bold py-2 rounded-lg flex items-center justify-center gap-1.5 transition hover:brightness-110 uppercase"
                    style="background-color: {{ $primaryColor }};"
                >
                    <x-icon name="o-shopping-cart" class="w-3.5 h-3.5" /> {{ $guestLoginLabel }}
                </a>
            </div>
        @elseif ($guestStyle === 'register_and_disabled')
            <div class="flex flex-col gap-1.5">
                <a
                    href="{{ $preview ? 'javascript:void(0)' : '/registrate' }}"
                    @if ($preview) @click.stop @endif
                    class="w-full bg-slate-500 hover:bg-slate-600 text-white text-[10px] font-bold py-2 rounded-lg flex items-center justify-center gap-1.5 transition uppercase"
                >
                    <x-icon name="o-lock-closed" class="w-3.5 h-3.5" /> {{ $guestRegisterLabel }}
                </a>
                <button
                    type="button"
                    disabled
                    class="w-full text-white text-[10px] font-bold py-2 rounded-lg flex items-center justify-center gap-1.5 opacity-50 cursor-not-allowed uppercase"
                    style="background-color: {{ $primaryColor }};"
                >
                    <x-icon name="o-shopping-cart" class="w-3.5 h-3.5" /> {{ $addLabel }}
                </button>
            </div>
        @else
            <div class="p-2 bg-slate-50 rounded-lg border border-slate-100 text-center text-[11px] text-slate-500 font-medium">
                <x-icon name="o-lock-closed" class="w-3 h-3 inline mr-1" /> {{ $guestMessage }}
            </div>
        @endif
    @elseif ($canBuy)
        <div class="space-y-2" @if ($preview) @click.stop @endif>
            @if ($showBulkToggle)
                <div class="flex items-center bg-slate-100 rounded-full p-0.5 gap-0.5">
                    <button
                        type="button"
                        @click.stop="setMode('bulk')"
                        :class="mode === 'bulk' ? 'text-white shadow-sm' : 'text-slate-500 hover:text-slate-700'"
                        :style="mode === 'bulk' ? { backgroundColor: '{{ $primaryColor }}' } : {}"
                        class="flex-1 flex items-center justify-center gap-1 rounded-full py-1 text-[10px] font-black transition-all duration-200 cursor-pointer"
                    >
                        <x-icon name="o-cube" class="w-3 h-3 inline shrink-0" />
                        {{ $bulkLabel }} (x{{ $pkg }})
                    </button>
                    <button
                        type="button"
                        @click.stop="setMode('unit')"
                        :class="mode === 'unit' ? 'text-white shadow-sm' : 'text-slate-500 hover:text-slate-700'"
                        :style="mode === 'unit' ? { backgroundColor: '{{ $primaryColor }}' } : {}"
                        class="flex-1 flex items-center justify-center gap-1 rounded-full py-1 text-[10px] font-black transition-all duration-200 cursor-pointer"
                    >
                        <x-icon name="o-tag" class="w-3 h-3 inline shrink-0" />
                        {{ $unitLabel }}
                    </button>
                </div>
            @endif

            @if ($showQtySelector)
                <div
                    class="flex items-stretch {{ $qtyHeightClass }} shadow-xs rounded-lg overflow-hidden border bg-white"
                    :class="mode === 'bulk' ? 'border-blue-300' : 'border-slate-300'"
                >
                    <button
                        type="button"
                        @click.stop="dec()"
                        class="w-9 shrink-0 flex items-center justify-center text-lg font-black transition-colors border-r bg-slate-100 hover:bg-slate-200 text-slate-700 border-slate-200 cursor-pointer"
                    >−</button>

                    <div class="flex-1 flex flex-col items-center justify-center bg-white min-w-0">
                        <input
                            type="number"
                            x-model.number="qtty"
                            @click.stop
                            class="w-full text-center {{ $qtyHeightClass === 'h-8' ? 'text-sm' : 'text-lg' }} font-black text-slate-900 bg-transparent focus:outline-none leading-none"
                            min="1"
                        >
                        @if ($qtyHeightClass !== 'h-8')
                            <span
                                class="text-[9px] leading-none font-semibold text-slate-400"
                                x-text="subtitle"
                            ></span>
                        @endif
                    </div>

                    <button
                        type="button"
                        @click.stop="inc()"
                        class="w-9 shrink-0 flex items-center justify-center text-lg font-black transition-colors border-l bg-slate-100 hover:bg-slate-200 text-slate-700 border-slate-200 cursor-pointer"
                    >+</button>
                </div>
            @endif

            @php
                $btnContainerClass = match ($buttonsLayout) {
                    'add_only', 'add_similar_stack', 'similar_add_stack' => 'flex flex-col gap-1.5',
                    default => 'grid grid-cols-2 gap-2',
                };
                $buttonOrder = match ($buttonsLayout) {
                    'add_only' => ['add'],
                    'add_similar_row', 'add_similar_stack' => ['add', 'similar'],
                    default => ['similar', 'add'],
                };
            @endphp

            <div class="{{ $btnContainerClass }}">
                @foreach ($buttonOrder as $btnType)
                    @if ($btnType === 'add')
                        <button
                            type="button"
                            @click.stop="addToCart(); @if (! $preview) flyToCart('prod-img-{{ $productId }}'); @endif"
                            class="btn btn-sm border-none text-white font-bold shadow-sm hover:brightness-110 w-full cursor-pointer"
                            style="background-color: {{ $primaryColor }};"
                        >
                            <x-icon name="o-shopping-cart" class="w-4 h-4 shrink-0" />
                            <span>{{ $addLabel }}</span>
                        </button>
                    @else
                        <button
                            type="button"
                            @click.stop="searchSimilar()"
                            class="btn btn-sm border-none text-white font-bold hover:brightness-110 w-full cursor-pointer"
                            style="background-color: {{ $secondaryColor }};"
                        >
                            <x-icon name="o-magnifying-glass" class="w-4 h-4 shrink-0" />
                            <span>{{ $similarLabel }}</span>
                        </button>
                    @endif
                @endforeach
            </div>
        </div>
    @endif
</div>
