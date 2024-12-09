<?php

use Livewire\Volt\Component;
use Mary\Traits\Toast;

new class extends Component {
    use Toast;
    public bool $drawer = false;
    public string $newPassword = '';
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

    public function save()
    {
        // validate
        $this->validate([
            'data.name' => 'required',
            'data.lastname' => 'required',
            'data.address' => 'required',
            'data.city' => 'required',
            'data.postal_code' => 'required',
            'data.phone' => 'required',
            'data.email' => 'required|email',
        ]);

        // update OR create
        $user = \App\Models\User::updateOrCreate(
            ['id' => $this->data['id']],
            $this->data
        );
        return redirect('users');
    }

    public function changePassword()
    {
        $user = \App\Models\User::find($this->data['id']);
        $user->password = Hash::make($this->newPassword);
        $user->save();
        $this->newPassword = '';
        $this->drawer = false;
        $this->success('Password updated.', position: 'toast-bottom');
    }

    public function delete()
    {
        $user = \App\Models\User::findOrFail($this->data['id']);
        $user->delete();
        return redirect('users');
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
        <x-input label="Apellido" wire:model="data.lastname" icon="o-user" error-field="data.lastname" />
        <x-input label="Nombre/s" wire:model="data.name" icon="o-user" error-field="data.name" />
        </div>
        <div class="grid grid-cols-1 gap-2 md:grid-cols-3">
        <x-input label="Dirección" wire:model="data.address" icon="o-map-pin" error-field="data.address" />
        <x-input label="Ciudad" wire:model="data.city" icon="o-map-pin" error-field="data.city" />
        <x-input label="Código Postal" wire:model="data.postal_code" icon="o-hashtag" error-field="data.postal_code" />
        </div>
        <div class="grid grid-cols-1 gap-2 md:grid-cols-2">
        <x-input label="Teléfono" wire:model="data.phone" icon="o-phone" error-field="data.phone" />
        <x-input label="E-mail" wire:model="data.email" icon="o-envelope" error-field="data.email" />
        </div>
        <div class="grid grid-cols-1 gap-2 md:grid-cols-2">
        <x-select label="Rol" icon="o-queue-list" :options="[['name' => 'customer'],['name' => 'admin']]" option-value="name" wire:model.lazy="data.role" />
        <x-input label="Lista de Precios" wire:model="data.list_id" type="number" icon="o-numbered-list" error-field="list_id" />
        </div>
        
        <x-slot:actions>
            <x-button wire:click="save" icon="o-check" class="btn-primary"
            type="submit" spinner="save"
            label="Guardar" />
        </x-slot:actions>
    </x-form>
    </x-card>
        <!-- DRAWER -->
        <x-drawer wire:model="drawer" title="Acciones" right with-close-button 
            separator with-close-button close-on-escape
            class="lg:w-1/3">
            <x-input inline label="Password" wire:model="newPassword" type="text" icon="o-key" error-field="newPassword">
                <x-slot:append>
                    <x-button label="Cambiar Clave" icon="o-check" class="btn-primary rounded-s-none" 
                        wire:click="changePassword" spinner="changePassword"/>
                </x-slot:append>
            </x-input>
            <x-slot:actions>
                <x-dropdown label="Eliminar Usuario" class="btn-error w-full mt-1">
                    <x-menu-item title="Confirmar" wire:click.stop="delete" spinner="delete" icon="o-trash" class="btn-red-500" />
                </x-dropdown>
            </x-slot:actions>
        </x-drawer>
</div>
