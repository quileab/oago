<?php

use App\Models\User;
use Livewire\Attributes\Rule;
use Livewire\Volt\Component;
use Mary\Traits\Toast;

new class extends Component
{
    use Toast;

    public $user;

    #[Rule('required|string|max:30')]
    public string $name = '';

    #[Rule('required|string|max:30')]
    public string $lastname = '';

    #[Rule('required|string|max:100')]
    public string $address = '';

    #[Rule('required|string|max:30')]
    public string $city = '';

    #[Rule('required|string|max:10')]
    public string $postal_code = '';

    #[Rule('required|string|max:50')]
    public string $phone = '';

    #[Rule('required|email|max:255')]
    public string $email = '';

    public string $current_password = '';

    #[Rule('nullable|string|min:8|confirmed')]
    public string $password = '';

    public string $password_confirmation = '';

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $this->user = current_user();

        if (! $this->user) {
            $this->redirect('/');

            return;
        }

        $this->name = $this->user->name ?? '';
        $this->lastname = $this->user->lastname ?? '';
        $this->address = $this->user->address ?? '';
        $this->city = $this->user->city ?? '';
        $this->postal_code = $this->user->postal_code ?? '';
        $this->phone = $this->user->phone ?? '';
        $this->email = $this->user->email ?? '';
    }

    /**
     * Update the profile information.
     */
    public function updateProfile(): void
    {
        $table = $this->user->getTable();

        $validated = $this->validate(
            $this->user::getProfileRules($table, $this->user->id),
            $this->user::getProfileMessages()
        );

        $this->user->fill($validated);

        if ($this->user instanceof User && $this->user->isDirty('email')) {
            $this->user->email_verified_at = null;
        }

        $this->user->save();

        $this->success('Perfil actualizado exitosamente!');
    }

    /**
     * Update the password.
     */
    public function updatePassword(): void
    {
        $realUser = real_user();
        $isAdminEditing = ($realUser && $realUser->role->value === 'admin')
            && ($realUser->id !== $this->user->id || get_class($realUser) !== get_class($this->user));

        $rules = [
            'password' => 'required|string|min:8|confirmed',
        ];

        if (! $isAdminEditing) {
            $rules['current_password'] = 'required|current_password';
        }

        $validated = $this->validate($rules);

        $this->user->update([
            'password' => $validated['password'],
        ]);

        $this->reset('current_password', 'password', 'password_confirmation');

        $this->success('Contraseña actualizada exitosamente!');
    }
}; ?>

<div>
    <x-header :title="$user->name" subtitle="Actualiza tu información de perfil y contraseña." separator />

    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mt-8">
        {{-- Update Profile Information --}}
        <div class="p-6 bg-base-100 border border-base-300 rounded-lg shadow-sm">
            <h2 class="text-lg font-medium text-base-content">Información de tu cuenta</h2>

            <x-form wire:submit="updateProfile" class="mt-6 space-y-6">
                <x-input label="Nombre / Empresa" wire:model="name" icon="o-user" maxlength="30" inline />
                <x-input label="Apellido" wire:model="lastname" icon="o-user" maxlength="30" inline />
                <x-input label="Dirección" wire:model="address" icon="o-map-pin" maxlength="100" inline />
                <x-input label="Ciudad" wire:model="city" icon="o-building-office" maxlength="30" inline />
                <x-input label="Código Postal" wire:model="postal_code" icon="o-envelope" maxlength="10" inline />
                <x-input label="Teléfono" wire:model="phone" icon="o-phone" maxlength="50" inline />
                <x-input label="Email" wire:model="email" icon="o-envelope" inline />

                <div class="flex items-center gap-4">
                    <x-button label="Guardar" type="submit" class="btn-primary" spinner="updateProfile" />
                </div>
            </x-form>
        </div>

        {{-- Update Password --}}
        <div class="p-6 bg-base-100 border border-base-300 rounded-lg shadow-sm">
            <h2 class="text-lg font-medium text-base-content">Actualizar Contraseña</h2>
            <p class="mt-1 text-sm text-base-content text-opacity-60">Asegúrate de utilizar una contraseña segura.</p>

            <x-form wire:submit="updatePassword" class="mt-6 space-y-6">
                {{-- Only show Current Password if the user is editing their own profile --}}
                @php $real = real_user(); @endphp
                @if($real && $real->id === $this->user->id && get_class($real) === get_class($this->user))
                    <x-input label="Contraseña Actual" wire:model="current_password" type="password" icon="o-lock-closed" inline />
                @endif
                <x-input label="Nueva Contraseña" wire:model="password" type="password" icon="o-key" inline />
                <x-input label="Confirmar Contraseña" wire:model="password_confirmation" type="password" icon="o-key"
                    inline />

                <div class="flex items-center gap-4">
                    <x-button label="Guardar" type="submit" class="btn-primary" spinner="updatePassword" />
                </div>
            </x-form>
        </div>
    </div>
</div>