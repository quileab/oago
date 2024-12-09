<?php

use Livewire\Volt\Component;

new class extends Component {
    public bool $drawer = false;
    public $data = [];
    
    public function mount($id = null)
    {
        if($id) {
            $this->data = \App\Models\User::findOrFail($id)->toArray();
        } 
        else {
            $this->data = [
                'name' => '',
                'lastname' => '',
                'address' => '',
                'city' => '',
                'postal_code' => '',
                'phone' => '',
                'email' => '',
                'password' => '',
                'role' => 'customer',
                'list_id' => 0,
            ];
        }
    }
}; ?>

<div>
    <x-card title="Usuario" shadow separator>
        <x-slot:menu>
            <x-button @click="$wire.drawer = true" responsive 
            icon="o-ellipsis-vertical"
            class="btn-ghost btn-circle btn-outline btn-sm" />
        </x-slot:menu>
    <x-form wire:submit="save">
        <div class="grid grid-cols-1 gap-2 md:grid-cols-2">
        <x-input label="Apellido" wire:model="data.lastname" icon="o-user" error-field="lastname" />
        <x-input label="Nombre/s" wire:model="data.name" icon="o-user" error-field="name" />
        </div>
        <div class="grid grid-cols-1 gap-2 md:grid-cols-3">
        <x-input label="Dirección" wire:model="data.address" icon="o-map-pin" error-field="address" />
        <x-input label="Ciudad" wire:model="data.city" icon="o-map-pin" error-field="city" />
        <x-input label="Código Postal" wire:model="data.postal_code" icon="o-hashtag" error-field="postal_code" />
        </div>
        <div class="grid grid-cols-1 gap-2 md:grid-cols-2">
        <x-input label="Teléfono" wire:model="data.phone" icon="o-phone" error-field="phone" />
        <x-input label="E-mail" wire:model="data.email" icon="o-envelope" error-field="email" />
        </div>
        <div class="grid grid-cols-1 gap-2 md:grid-cols-2">
        <x-input label="Role" wire:model="data.role" icon="o-user" error-field="role" />
        <x-input label="List ID" wire:model="data.list_id" icon="o-user" error-field="list_id" />
        </div>
        
        <x-slot:actions>
            <x-input inline label="Password" wire:model="data.password" type="text" icon="o-key" error-field="password"
                readonly               
                >
                <x-slot:append>
                    {{-- Add `rounded-s-none` class (RTL support) --}}
                    <x-button label="Cambiar Clave" icon="o-check" class="btn-primary rounded-s-none" />
                </x-slot:append>
            </x-input>
        </x-slot:actions>
    </x-form>
    </x-card>
        <!-- DRAWER -->
        <x-drawer wire:model="drawer" title="Acciones" right with-close-button class="lg:w-1/3">
            <x-button wire:click="save" icon="o-check" class="mt-2 btn-primary w-full"
                type="submit" spinner="save"
                label="Guardar" />
            <x-dropdown label="Eliminar" class="btn-error w-full mt-1">
                <x-menu-item title="Confirmar" wire:click.stop="delete" spinner="delete" icon="o-trash" class="btn-error" />
            </x-dropdown>
        </x-drawer>
</div>
