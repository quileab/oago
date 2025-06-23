<?php
use Livewire\Volt\Component;
use Mary\Traits\Toast;

new class extends Component {
    use Toast;

    public string $email = '';

    public string $password = '';

    public $message = false;

    // guest user data registration
    public $data = [
        'name' => '',
        'lastname' => '',
        'address' => '',
        'city' => '',
        'postal_code' => '',
        'phone' => '',
        'email' => '',
        'password' => '',
    ];

    public function mount()
    {
        // It is logged in
        if (auth()->user()) {
            return redirect('/');
        }
    }

    public function register()
    {
        $this->data['password'] = Hash::make(strtolower($this->data['lastname']));
        $validated = $this->validate([
            'data.name' => 'required',
            'data.lastname' => 'required',
            'data.address' => 'required',
            'data.city' => 'required',
            'data.postal_code' => 'required',
            'data.phone' => 'required',
            'data.email' => 'required|email|unique:guest_users,email',
            'data.password' => 'required',
        ], [
            'data.name.required' => 'El nombre es requerido.',
            'data.lastname.required' => 'El apellido es requerido.',
            'data.address.required' => 'La dirección es requerida.',
            'data.city.required' => 'La ciudad es requerida.',
            'data.postal_code.required' => 'El código postal es requerido.',
            'data.phone.required' => 'El teléfono es requerido.',
            'data.email.required' => 'El e-mail es requerido.',
            'data.email.email' => 'El e-mail no es válido.',
            'data.email.unique' => 'El e-mail ya está registrado.',
            'data.password.required' => 'La contraseña es requerida.',
        ]);
        // toast messages
        if (\App\Models\GuestUser::create($validated['data'])) {
            $this->reset('data');
            $this->message = true;
        } else {
            $this->error('Error al crear el usuario. Intente nuevamente.');
        }
    }
}; ?>

<div data-theme="light" class="min-h-screen flex flex-col justify-center items-center bg-gray-200">
    <x-header title="REGISTRARSE" subtitle="Nos pondremos en contacto a la brevedad con los datos de ingreso." />
    @if($message)
        <x-alert title="Se ha registrado correctamente."
            description="Nos pondremos en contacto a la brevedad con los datos de ingreso." icon="o-information-circle"
            class="alert-info" />
    @else
        <x-form wire:submit="register" no-separator>
            <div class="grid grid-cols-1 gap-2 md:grid-cols-2">
                <x-input label="Apellido" placeholder="Apellido" wire:model="data.lastname" icon="o-user"
                    class="text-gray-900" />
                <x-input label="Nombre/s" placeholder="Nombre/s" wire:model="data.name" icon="o-user" />
            </div>
            <x-input label="Dirección" placeholder="Dirección" wire:model="data.address" icon="o-map-pin" />
            <div class="grid grid-cols-1 gap-2 md:grid-cols-2">
                <x-input label="Ciudad" placeholder="Ciudad" wire:model="data.city" icon="o-map-pin" />
                <x-input label="Código Postal" placeholder="Código Postal" wire:model="data.postal_code" icon="o-hashtag" />
            </div>
            <div class="grid grid-cols-1 gap-2 md:grid-cols-2">
                <x-input label="Teléfono" placeholder="Teléfono" wire:model="data.phone" icon="o-phone" />
                <x-input label="E-mail" placeholder="E-mail" wire:model="data.email" type="email" icon="o-envelope" />
            </div>
            <x-slot:actions>
                <x-button label="Registrarme" class="btn-primary" type="submit" spinner="register" />
            </x-slot:actions>
        </x-form>
    @endif
</div>