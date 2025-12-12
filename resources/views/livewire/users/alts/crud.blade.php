<?php

use App\Enums\Role;
use Livewire\Volt\Component;
use Livewire\Attributes\Computed;
use Mary\Traits\Toast;
use App\Models\AltUser;
use App\Models\ListName;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;
use Illuminate\Validation\Rule;
use App\Mail\AltUserWelcomeMail;

new class extends Component {
    use Toast;

    public string $newPassword = '';
    public array $formData = [];
    public $list_names = [];
    public string $createdAtDate = '';

    public function mount($id = null): void
    {
        $this->list_names = ListName::all();

        if ($id) {
            $this->formData = AltUser::findOrFail($id)->toArray();
            // check if list_id is null and set it to 1
            if ($this->formData['list_id'] === null) {
                $this->formData['list_id'] = 1;
            }
            $this->createdAtDate = $this->formData['created_at'] ? Carbon::parse($this->formData['created_at'])->format('Y-m-d') : now()->format('Y-m-d');
        } else {
            $this->formData = [
                'name' => '',
                'lastname' => '',
                'address' => '',
                'city' => '',
                'postal_code' => '',
                'phone' => '',
                'email' => '',
                'role' => 'customer',
                'list_id' => 1,
                'is_internal' => false,
            ];
            $this->createdAtDate = now()->format('Y-m-d');
        }
    }

    #[Computed]
    public function roles(): array
    {
        return array_map(fn($role) => ['id' => $role->value, 'name' => $role->value], Role::cases());
    }

    protected function rules(): array
    {
        $rules = [
            'formData.name' => 'required|string|max:255',
            'formData.lastname' => 'required|string|max:255',
            'formData.address' => 'required|string|max:255',
            'formData.city' => 'required|string|max:255',
            'formData.postal_code' => 'required|string|max:20',
            'formData.phone' => 'required|string|max:50',
            'formData.list_id' => 'required|numeric',
            'formData.role' => 'required|string',
            'formData.is_internal' => 'boolean',
        ];

        // Add unique email validation
        $rules['formData.email'] = [
            'required',
            'email',
            Rule::unique('alt_users', 'email')->ignore($this->formData['id'] ?? null),
        ];

        return $rules;
    }

    protected function messages(): array
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
            'formData.email.unique' => 'El e-mail ya existe.',
            'formData.list_id.required' => 'La lista de precios es requerida.',
            'formData.role.required' => 'El rol es requerido.',
        ];
    }

    public function save(): void
    {
        $validated = $this->validate($this->rules(), $this->messages());

        AltUser::updateOrCreate(
            ['id' => $this->formData['id'] ?? null],
            $validated['formData']
        );

        $this->success('Usuario Alternativo guardado correctamente.', position: 'toast-bottom');
        $this->redirect('/alts', navigate: true);
    }

    public function changePassword(): void
    {
        $this->validate(['newPassword' => 'required|min:6']);
        
        $user = AltUser::findOrFail($this->formData['id']);
        $user->update(['password' => Hash::make($this->newPassword)]);
        
        $this->newPassword = '';
        $this->success('Clave actualizada.', position: 'toast-bottom');
    }

    public function delete(): void
    {
        AltUser::findOrFail($this->formData['id'])->delete();
        $this->redirect('/alts');
    }

    public function resetDate(): void
    {
        $this->validate(['createdAtDate' => 'required|date']);

        $user = AltUser::findOrFail($this->formData['id']);
        $user->created_at = $this->createdAtDate;
        $user->save();
        $this->createdAtDate = $user->created_at->format('Y-m-d');
        $this->success('Fecha de creación reiniciada.');
    }

    public function sendWelcomeEmail(): void
    {
        $user = AltUser::findOrFail($this->formData['id']);
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
        Mail::to($user->email)->send(new AltUserWelcomeMail($user, $password));
        $this->success('Correo de bienvenida enviado.', position: 'toast-bottom');
    }
}; ?>

<div>
    <x-card title="Usuario Alternativo" shadow separator class="mb-4">
        <x-form wire:submit="save">
            <!-- Estructura Grid Simplificada -->
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-6">
                <div class="lg:col-span-3">
                    <x-input label="Apellido" wire:model="formData.lastname" icon="o-user" />
                </div>
                <div class="lg:col-span-3">
                    <x-input label="Nombre/s" wire:model="formData.name" icon="o-user" />
                </div>

                <div class="lg:col-span-3">
                    <x-input label="Dirección" wire:model="formData.address" icon="o-map-pin" />
                </div>
                <div class="lg:col-span-2">
                    <x-input label="Ciudad" wire:model="formData.city" icon="o-map-pin" />
                </div>
                <div class="lg:col-span-1">
                    <x-input label="C.P." wire:model="formData.postal_code" icon="o-hashtag" />
                </div>

                <div class="lg:col-span-3">
                    <x-input label="Teléfono" wire:model="formData.phone" icon="o-phone" />
                </div>
                <div class="lg:col-span-3">
                    <x-input label="E-mail" wire:model="formData.email" icon="o-envelope" />
                </div>

                <div class="lg:col-span-2">
                    <x-select label="Rol" icon="o-queue-list" :options="$this->roles" option-value="id" option-label="name" wire:model="formData.role" />
                </div>
                <div class="lg:col-span-2">
                    <x-select label="Lista de Precios" icon="o-queue-list" :options="$list_names" wire:model="formData.list_id" option-value="id" option-label="name" />
                </div>
                <div class="lg:col-span-2 flex items-center pt-4">
                     <x-toggle label="Personal Interno" wire:model="formData.is_internal" hint="Acceso a todos los clientes" class="toggle-primary" />
                </div>
            </div>

            <x-slot:actions>
                <x-button label="Guardar" icon="o-check" class="btn-primary" type="submit" spinner="save" />
            </x-slot:actions>
        </x-form>
    </x-card>

    <x-card title="Acciones" shadow separator>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
            <div class="md:col-span-1">
                <x-input label="RESET Fecha" type="date" wire:model="createdAtDate">
                    <x-slot:append>
                        <x-button label="RESET" icon="o-clock" wire:click="resetDate" class="join-item btn-primary" spinner="resetDate" />
                    </x-slot:append>
                </x-input>
            </div>

            <div class="md:col-span-1">
                <x-input label="Password" wire:model="newPassword" type="password" icon="o-key">
                    <x-slot:append>
                        <x-button label="Cambiar Clave" icon="o-check" class="btn-primary join-item" wire:click="changePassword" spinner="changePassword" />
                    </x-slot:append>
                </x-input>
            </div>
            
            <div class="md:col-span-1">
                <x-dropdown label="Eliminar Usuario" class="btn-error w-full mt-8">
                    <x-menu-item title="Confirmar" wire:click="delete" spinner="delete" icon="o-trash" class="text-red-500" />
                </x-dropdown>
            </div>
            
            <div class="md:col-span-3 flex flex-col sm:flex-row align-middle justify-between w-full border border-gray-300/50 rounded-md p-2 gap-2">
                <p>Enviar correo de bienvenida con <br><b>· contraseña autogenerada<br>· rol: invitado<br>· reseteo de
                        fecha</b>.
                </p>
                <x-button label="Enviar" icon="o-envelope" wire:click="sendWelcomeEmail" spinner="sendWelcomeEmail"
                    class="btn-primary" />
            </div>
        </div>
    </x-card>
</div>