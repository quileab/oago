<div>
    <x-form>
        <x-header title="Configuración General" subtitle="Ajustes principales de la aplicación" />

        @foreach($settingDetails as $key => $details)
            @switch($details['type'])
                @case('number')
                    <x-input
                        label="{{ $details['text'] }}"
                        wire:model="settings_values.{{ $key }}"
                        type="number"
                        icon="o-hashtag"
                        hint="{{ $details['description'] }}">
                        <x-slot:append>
                            <x-button icon="o-check" class="join-item btn-primary" wire:click="saveSetting('{{ $key }}')" spinner="saveSetting('{{ $key }}')" />
                        </x-slot:append>
                    </x-input>
                    @break

                @case('string')
                    <x-input
                        label="{{ $details['text'] }}"
                        wire:model="settings_values.{{ $key }}"
                        icon="o-variable"
                        hint="{{ $details['description'] }}">
                        <x-slot:append>
                            <x-button icon="o-check" class="join-item btn-primary" wire:click="saveSetting('{{ $key }}')" spinner="saveSetting('{{ $key }}')" />
                        </x-slot:append>
                    </x-input>
                    @break

                @case('json')
                    <x-input
                        label="{{ $details['text'] }}"
                        wire:model="settings_values.{{ $key }}"
                        icon="o-code-bracket-square"
                        hint="{{ $details['description'] }}">
                        <x-slot:append>
                            <x-button icon="o-check" class="join-item btn-primary" wire:click="saveSetting('{{ $key }}')" spinner="saveSetting('{{ $key }}')" />
                        </x-slot:append>
                    </x-input>
                    @break

                @case('boolean')
                    <x-toggle
                        label="{{ $details['text'] }}"
                        wire:model.live="settings_values.{{ $key }}"
                        hint="{{ $details['description'] }}"
                        wire:change="saveSetting('{{ $key }}')"
                    />
                    @break

                @default
                    <x-input
                        label="{{ $details['text'] }}"
                        wire:model="settings_values.{{ $key }}"
                        icon="o-variable"
                        hint="{{ $details['description'] }}">
                        <x-slot:append>
                            <x-button icon="o-check" class="join-item btn-primary" wire:click="saveSetting('{{ $key }}')" spinner="saveSetting('{{ $key }}')" />
                        </x-slot:append>
                    </x-input>
            @endswitch
        @endforeach

        <x-slot:actions>
            {{-- The main save button is removed as per user's request for individual saves --}}
        </x-slot:actions>
    </x-form>
</div>