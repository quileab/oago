<x-layouts.app>
    <x-slot name="header">
        <h2 class="font-semibold text-xl leading-tight">
            {{ __('Settings') }}
        </h2>
    </x-slot>
    @livewire('settings')
</x-layouts.app>