<?php

use Livewire\Volt\Component;
use Mary\Traits\Toast;
use App\Models\User; // Ensure User model is imported
use Illuminate\Support\Facades\Hash; // Ensure Hash is imported

new class extends Component {
    use Toast;
    public bool $drawer = false;
    public string $newPassword = '';
    public array $formData = []; // Use array for form data

    public function mount($id = null)
    {
        if($id) {
            $this->formData = User::findOrFail($id)->toArray();
        }
        else {
            $this->formData = [
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

    protected function rules()
    {
        return [
            'formData.name' => 'required',
            'formData.lastname' => 'required',
            'formData.address' => 'required',
            'formData.city' => 'required',
            'formData.postal_code' => 'required',
            'formData.phone' => 'required',
            'formData.email' => 'required|email',
        ];
    }

    

    public function save()
    {
        // validate
        $this->validate($this->rules()); // Use the rules() method

        // update OR create
        $user = \App\Models\User::updateOrCreate(
            ['id' => $this->formData['id'] ?? null], // Use formData and handle new records
            $this->formData
        );
        return redirect('users');
    }

    public function changePassword()
    {
        $user = \App\Models\User::find($this->formData['id']);
        $user->password = Hash::make($this->newPassword);
        $user->save();
        $this->newPassword = '';
        $this->drawer = false;
        $this->success('Password updated.', position: 'toast-bottom');
    }

    public function delete()
    {
        $user = \App\Models\User::findOrFail($this->formData['id']);
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
        <x-input label="Apellido" wire:model="formData.lastname" icon="o-user" error-field="formData.lastname" />
        <x-input label="Nombre/s" wire:model="formData.name" icon="o-user" error-field="formData.name" />
        </div>
        <div class="grid grid-cols-1 gap-2 md:grid-cols-3">
        <x-input label="Dirección" wire:model="formData.address" icon="o-map-pin" error-field="formData.address" />
        <x-input label="Ciudad" wire:model="formData.city" icon="o-map-pin" error-field="formData.city" />
        <x-input label="Código Postal" wire:model="formData.postal_code" icon="o-hashtag" error-field="formData.postal_code" />
        </div>
        <div class="grid grid-cols-1 gap-2 md:grid-cols-2">
        <x-input label="Teléfono" wire:model="formData.phone" icon="o-phone" error-field="formData.phone" />
        <x-input label="E-mail" wire:model="formData.email" icon="o-envelope" error-field="formData.email" />
        </div>
        <div class="grid grid-cols-1 gap-2 md:grid-cols-2">
        <x-select label="Rol" icon="o-queue-list" :options="[['name' => 'customer'],['name' => 'admin']]" option-value="name" wire:model.lazy="formData.role" />
        <x-input label="Lista de Precios" wire:model="formData.list_id" type="number" icon="o-numbered-list" error-field="list_id" />
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