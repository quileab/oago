<?php

use App\Models\User;
use Livewire\Volt\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Rule;
use Mary\Traits\Toast;

new class extends Component {
    use Toast;

    public $user;

    #[Rule('required|string|max:255')]
    public string $name = '';

    #[Rule('nullable|string|max:255')]
    public string $lastname = '';

    #[Rule('nullable|string|max:255')]
    public string $address = '';

    #[Rule('nullable|string|max:255')]
    public string $city = '';

    #[Rule('nullable|string|max:20')]
    public string $postal_code = '';

    #[Rule('nullable|string|max:20')]
    public string $phone = '';

    #[Rule('required|email|max:255')]
    public string $email = '';

    // Optional for admins, required for users updating their own password
    public string $current_password = '';

    #[Rule('nullable|string|min:8|confirmed')]
    public string $password = '';

    public string $password_confirmation = '';

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $this->user = Auth::user();

        $this->name = $this->user->name;
        $this->lastname = $this->user->lastname;
        $this->address = $this->user->address;
        $this->city = $this->user->city;
        $this->postal_code = $this->user->postal_code;
        $this->phone = $this->user->phone;
        $this->email = $this->user->email;
    }

    /**
     * Update the profile information.
     */
    public function updateProfile(): void
    {
        $validated = $this->validate([
            'name' => 'required|string|max:255',
            'lastname' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:20',
            'phone' => 'nullable|string|max:20',
            'email' => 'required|email|max:255|unique:users,email,' . $this->user->id,
        ]);

        $this->user->fill($validated);

        if ($this->user->isDirty('email')) {
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
        // Admin doesn't need current password, user does.
        $is_admin_editing = Auth::user()->is_admin && Auth::id() !== $this->user->id;

        $rules = [
            'password' => 'required|string|min:8|confirmed',
        ];

        if (!$is_admin_editing) {
            $rules['current_password'] = 'required|current_password';
        }

        $validated = $this->validate($rules);

        $this->user->update([
            'password' => Hash::make($validated['password']),
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
                <x-input label="Nombre / Empresa" wire:model="name" icon="o-user" inline />
                <x-input label="Apellido" wire:model="lastname" icon="o-user" inline />
                <x-input label="Dirección" wire:model="address" icon="o-map-pin" inline />
                <x-input label="Ciudad" wire:model="city" icon="o-building-office" inline />
                <x-input label="Código Postal" wire:model="postal_code" icon="o-envelope" inline />
                <x-input label="Teléfono" wire:model="phone" icon="o-phone" inline />
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
                @if(Auth::id() === $this->user->id)
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