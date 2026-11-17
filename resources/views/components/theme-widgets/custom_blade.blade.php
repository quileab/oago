@props([
    'props' => [],
    'preview' => false,
])

@php
    $container = (bool) ($props['container'] ?? true);
@endphp

<section class="w-full py-6">
    <div class="{{ $container ? 'max-w-7xl mx-auto px-4 sm:px-6 lg:px-8' : 'w-full' }}">
        @if (isset($htmlContent) && $htmlContent->isNotEmpty())
            {{ $htmlContent }}
        @else
            {!! $props['html'] ?? '' !!}
        @endif
    </div>
</section>
