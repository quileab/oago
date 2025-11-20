<?php

use App\Enums\Role;
use Livewire\Volt\Component;
use Mary\Traits\Toast;
use App\Models\AltUser; // Ensure AltUser model is imported
use App\Models\ListName; // Ensure ListName is imported
use Illuminate\Support\Facades\Hash; // Ensure Hash is imported
use Illuminate\Support\Facades\Mail; // Ensure Mail is imported
use Carbon\Carbon; // Ensure Carbon is imported
use Illuminate\Validation\Rule; // Ensure Rule is imported

new class extends Component {
    use Toast;
    public string $newPassword = '';
    public array $formData = []; // Use array for form data
    public $list_names = [];

    public $createdAtDate = '';

    public function mount($id = null)
    {
        $this->list_names = ListName::all();

        if ($id) {
            $this->formData = AltUser::findOrFail($id)->toArray();
            // check if list_id is null and set it to 1
            if ($this->formData['list_id'] === null) {
                $this->formData['list_id'] = 1;
            }
            $this->createdAtDate = $this->formData['created_at'] ? Carbon::parse($this->formData['created_at'])->format('Y-m-d') : null;
        } else {
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
                'list_id' => 1,
            ];
            $this->createdAtDate = now()->format('Y-m-d');
        }
    }

    protected function rules()
    {
        $rules = [
            'formData.name' => 'required',
            'formData.lastname' => 'required',
            'formData.address' => 'required',
            'formData.city' => 'required',
            'formData.postal_code' => 'required',
            'formData.phone' => 'required',
            'formData.list_id' => 'required|numeric',
        ];

        // Add unique email validation
        $rules['formData.email'] = [
            'required',
            'email',
            Rule::unique('alt_users', 'email')->ignore($this->formData['id'] ?? null),
        ];

        return $rules;
    }

    protected function messages()
    {
        return [
            'formData.name.required' => 'El nombre es requerido.',
            'formData.lastname.required' => 'El apellido es requerido.',
            'formData.address.required' => 'La dirección es requerida.',
            'formData.city.required' => 'La ciudad es requerida.',
            'formData.postal_code.required' => 'El código postal es requerido.',
            'formData.phone.required' => 'El teléfono es requerido.',
            'formData.email.required' => 'El e-mail es requerido.',
            'formData.email.email' => 'El e-mail no es válido.',
            'formData.email.unique' => 'El e-mail ya existe.', // New message
            'formData.list_id.required' => 'La lista de precios es requerida.',
        ];
    }

    public function save()
    {
        // validate
        $this->validate($this->rules(), $this->messages()); // Use the rules() and messages() methods
        // update OR create
        $user = AltUser::updateOrCreate(
            ['id' => $this->formData['id'] ?? null], // Use formData and handle new records
            $this->formData
        );
        return redirect('alts');
    }

    public function changePassword()
    {
        $user = AltUser::find($this->formData['id']);
        $user->password = Hash::make($this->newPassword);
        $user->save();
        $this->newPassword = '';
        $this->success('Password updated.', position: 'toast-bottom');
    }

    public function delete()
    {
        $user = AltUser::findOrFail($this->formData['id']);
        $user->delete();
        return redirect('alts');
    }

    public function resetDate()
    {
        $user = AltUser::find($this->formData['id']);
        $user->created_at = $this->createdAtDate;
        $user->save();
        $this->createdAtDate = $user->created_at->format('Y-m-d'); // Update the displayed date
        $this->success('Fecha de creación reiniciada.');
    }

    public function sendWelcomeEmail()
    {
        $user = AltUser::find($this->formData['id']);
        // update created_at and updated_at timestamps to current time
        $user->created_at = now();
        $user->updated_at = $user->created_at;
        // set user role to 'guest'
        $user->role = Role::GUEST;
        // create 8 characters password string with 4 letters and 4 numbers
        $password = substr(str_shuffle('abcdefghijklmnopqrstuvwxyz0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 8);
        // hash password
        $user->password = Hash::make($password);
        $user->save();
        // Assuming you have a default password or a way to generate one
        Mail::to($user->email)->send(new \App\Mail\AltUserWelcomeMail($user, $password));
        $this->success('Correo de bienvenida enviado.', position: 'toast-bottom');
    }

    public function roles()
    {
        return array_map(fn($role) => ['name' => $role->value], Role::cases());
    }
}; ?>

<div>
    <x-card title="Usuario Alternativo" shadow separator class="mb-4">
        <x-form wire:submit="save">
            <div class="grid grid-cols-1 gap-2 md:grid-cols-2">
                <x-input label="Apellido" wire:model="formData.lastname" icon="o-user"
                    error-field="formData.lastname" />
                <x-input label="Nombre/s" wire:model="formData.name" icon="o-user" error-field="formData.name" />
            </div>
            <div class="grid grid-cols-1 gap-2 md:grid-cols-3">
                <x-input label="Dirección" wire:model="formData.address" icon="o-map-pin"
                    error-field="formData.address" />
                <x-input label="Ciudad" wire:model="formData.city" icon="o-map-pin" error-field="formData.city" />
                <x-input label="Código Postal" wire:model="formData.postal_code" icon="o-hashtag"
                    error-field="formData.postal_code" />
            </div>
            <div class="grid grid-cols-1 gap-2 md:grid-cols-2">
                <x-input label="Teléfono" wire:model="formData.phone" icon="o-phone" error-field="formData.phone" />
                <x-input label="E-mail" wire:model="formData.email" icon="o-envelope" error-field="formData.email" />
            </div>
            <div class="grid grid-cols-1 gap-2 md:grid-cols-2">
                <x-select label="Rol" icon="o-queue-list" :options="$this->roles()" option-value="name"
                    wire:model.lazy="formData.role" />
                <x-select label="Lista de Precios" icon="o-queue-list" :options="$list_names"
                    wire:model="formData.list_id" error-field="list_id" option-value="id" option-label="name" />
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