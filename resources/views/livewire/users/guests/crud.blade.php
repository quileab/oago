<?php

use Livewire\Volt\Component;
use Mary\Traits\Toast;
use \App\Models\GuestUser as User;

new class extends Component {
    use Toast;
    public string $newPassword = '';
    public $data = [];
    public $list_names = [];

    public $createdAtDate = '';

    public function mount($id = null)
    {
        if ($id) {
            $this->data = User::findOrFail($id)->toArray();
            $this->createdAtDate = $this->data['created_at'] ? \Carbon\Carbon::parse($this->data['created_at'])->format('Y-m-d') : null;
        } else {
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
                'list_id' => 1,
            ];
            $this->createdAtDate = now()->format('Y-m-d');
        }
        $this->list_names = \App\Models\ListName::all();
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
            'data.list_id' => 'required|numeric',
        ], [
            'data.name.required' => 'El nombre es requerido.',
            'data.lastname.required' => 'El apellido es requerido.',
            'data.address.required' => 'La dirección es requerida.',
            'data.city.required' => 'La ciudad es requerida.',
            'data.postal_code.required' => 'El código postal es requerido.',
            'data.phone.required' => 'El teléfono es requerido.',
            'data.email.required' => 'El e-mail es requerido.',
            'data.email.email' => 'El e-mail no es válido.',
            'data.list_id.required' => 'La lista de precios es requerida.',
        ]);

        // update OR create
        $user = User::updateOrCreate(
            ['id' => $this->data['id']],
            $this->data
        );
        return redirect('users');
    }

    public function changePassword()
    {
        $user = User::find($this->data['id']);
        $user->password = Hash::make($this->newPassword);
        $user->save();
        $this->newPassword = '';
        $this->success('Password updated.', position: 'toast-bottom');
    }

    public function delete()
    {
        $user = User::findOrFail($this->data['id']);
        $user->delete();
        return redirect('users');
    }

    public function resetDate()
    {
        $user = User::find($this->data['id']);
        $user->created_at = now();
        $user->save();
        $this->data['created_at'] = $user->created_at; // Update data array for consistency
        $this->createdAtDate = $user->created_at->format('Y-m-d'); // Update the displayed date
        $this->success('Fecha de creación reiniciada.', position: 'toast-bottom');
    }

    public function sendWelcomeEmail()
    {
        $user = User::find($this->data['id']);
        // update created_at and updated_at timestamps to current time
        $user->created_at = now();
        $user->updated_at = $user->created_at;
        // set user role to 'guest'
        $user->role = 'guest';
        // create 8 characters password string with 4 letters and 4 numbers
        $password = substr(str_shuffle('abcdefghijklmnopqrstuvwxyz0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 8);
        // hash password
        $user->password = Hash::make($password);
        $user->save();
        // Assuming you have a default password or a way to generate one
        Mail::to($user->email)->send(new \App\Mail\GuestUserWelcomeMail($user, $password));
        $this->success('Correo de bienvenida enviado.', position: 'toast-bottom');
    }
}; ?>

<div>
    <x-card title="Usuario Invitado" shadow separator class="mb-4">
        <x-form wire:submit="save">
            <div class="grid grid-cols-1 gap-2 md:grid-cols-2">
                <x-input label="Apellido" wire:model="data.lastname" icon="o-user" error-field="data.lastname" />
                <x-input label="Nombre/s" wire:model="data.name" icon="o-user" error-field="data.name" />
            </div>
            <div class="grid grid-cols-1 gap-2 md:grid-cols-3">
                <x-input label="Dirección" wire:model="data.address" icon="o-map-pin" error-field="data.address" />
                <x-input label="Ciudad" wire:model="data.city" icon="o-map-pin" error-field="data.city" />
                <x-input label="Código Postal" wire:model="data.postal_code" icon="o-hashtag"
                    error-field="data.postal_code" />
            </div>
            <div class="grid grid-cols-1 gap-2 md:grid-cols-2">
                <x-input label="Teléfono" wire:model="data.phone" icon="o-phone" error-field="data.phone" />
                <x-input label="E-mail" wire:model="data.email" icon="o-envelope" error-field="data.email" />
            </div>
            <div class="grid grid-cols-1 gap-2 md:grid-cols-2">
                <x-select label="Rol" icon="o-queue-list" :options="[['name' => 'none'], ['name' => 'guest']]"
                    option-value="name" wire:model.lazy="data.role" />
                <x-select label="Lista de Precios" icon="o-queue-list" :options="$list_names"
                    prefix="{{ $data['list_id'] ? $data['list_id'] : '' }}" wire:model.lazy="data.list_id"
                    error-field="list_id" />
            </div>

            <x-slot:actions>
                <x-button wire:click="save" icon="o-check" class="btn-primary" type="submit" spinner="save"
                    label="Guardar" />
            </x-slot:actions>
        </x-form>
    </x-card>

    {{-- reset created_at to today --}}
    <x-card title="Acciones" shadow separator>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
            <x-input label="RESET Fecha" type="date" wire:model="createdAtDate">
                <x-slot:append>
                    <x-button label="RESET" icon="o-clock" wire:click="resetDate" class="join-item btn-primary" />
                </x-slot:append>
            </x-input>

            <x-input label="Password" wire:model="newPassword" type="text" icon="o-key" error-field="newPassword">
                <x-slot:append>
                    <x-button label="Cambiar Clave" icon="o-check" class="btn-primary join-item"
                        wire:click="changePassword" spinner="changePassword" />
                </x-slot:append>
            </x-input>
            <x-dropdown label="Eliminar Usuario" class="btn-error mt-8">
                <x-menu-item title="Confirmar" wire:click.stop="delete" spinner="delete" icon="o-trash"
                    class="bg-red-500" />
            </x-dropdown>
            <div class="flex align-middle justify-between w-full border border-gray-300/50 rounded-md p-2 gap-2">
                <p>Enviar correo de bienvenida con <br><b>· contraseña autogenerada<br>· rol: invitado<br>· reseteo de
                        fecha</b>.
                </p>
                <x-button label="Enviar" icon="o-envelope" wire:click="sendWelcomeEmail" spinner="sendWelcomeEmail"
                    class="btn-primary" />
            </div>
        </div>
    </x-card>
</div>