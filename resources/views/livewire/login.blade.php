<?php
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Rule;
use Livewire\Attributes\Title;
use Mary\Traits\Toast;

new #[Layout('components.layouts.empty')]       // <-- Here is the `empty` layout
    #[Title('Login')]
    class extends Component {
    use Toast;

    public string $email = '';
    // public string $email_guest = '';

    public string $password = '';
    // public string $password_guest = '';

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

    public string $selectedTab = 'users-tab';

    public function mount()
    {
        // It is logged in
        if (auth()->user()) {
            return redirect('/');
        }
    }

    public function login()
    {
        $credentials = $this->validate(
            [
                'email' => ['required', 'email'],
                'password' => ['required'],
            ],
            [
                'email.required' => 'El e-mail es requerido.',
                'email.email' => 'El e-mail no es válido.',
                'password.required' => 'La contraseña es requerida.',
            ]
        );
        if (auth()->attempt($credentials)) {
            request()->session()->regenerate();
            // check if user has a temporarily cart JSON and load it
            if (file_exists($cartFile = storage_path("app/private/" . Auth::id() . "_cart.json"))) {
                $cart = json_decode(file_get_contents($cartFile), true);
                // actualizar el precio actualizado del producto según el usuario
                foreach ($cart as $item) {
                    $prod = \App\Models\ListPrice::where('product_id', $item['product_id'])
                        ->where('list_id', auth()->user()->list_id)
                        ->first();
                    // si el producto no tiene precio de lista, usar el precio del pedido
                    $cart[$item['product_id']]['price'] = $prod->price ?? $item['price'];
                }
                //save cart to cart session
                session()->put('cart', $cart);
            }

            return redirect()->intended('/');
        }
        $this->addError('email', 'Login incorrecto. Intentelo de nuevo.');
    }

    public function login_guest()
    {
        $credentials = $this->validate(
            [
                'email' => ['required', 'email'],
                'password' => ['required'],
            ],
            [
                'email.required' => 'El e-mail es requerido.',
                'email.email' => 'El e-mail no es válido.',
                'password.required' => 'La contraseña es requerida.',
            ]
        );
        if (Auth::guard('guest_user')->attempt($credentials)) {
            request()->session()->regenerate();
            return redirect()->intended('/');
        }
        $this->addError('email', 'Login incorrecto. Intentelo de nuevo.');
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

<div class="min-h-screen flex justify-center items-center">
    <div data-theme="dark"
        class="w-3/4 md:w-1/3 mx-auto bg-slate-900/80 backdrop-blur-xl rounded-lg shadow-lg shadow-black/50 p-4">
        <x-tabs wire:model="selectedTab">
            <x-tab name="users-tab" label="Usuarios" icon="o-users">
                <x-header title="LOGIN" />
                <x-form wire:submit="login" no-separator>
                    <x-input label="E-mail" wire:model="email" icon="o-envelope" inline />
                    <x-input label="Password" wire:model="password" type="password" icon="o-lock-closed" inline />

                    <x-slot:actions>
                        {{-- <x-button label="Create an account" class="btn-ghost" link="/register" /> --}}
                        <x-button label="Login" type="submit" icon="o-key" class="btn-primary" spinner="login" />
                    </x-slot:actions>
                </x-form>
            </x-tab>
            <x-tab name="guests-tab" label="Invitados" icon="o-user-group">
                <x-header title="LOGIN INVITADOS" />
                <x-form wire:submit="login_guest" no-separator>
                    <x-input label="E-mail" wire:model="email" icon="o-envelope" inline />
                    <x-input label="Password" wire:model="password" type="password" icon="o-lock-closed" inline />

                    <x-slot:actions>
                        <x-button label="Crear cuenta Invitado!" class="btn-ghost"
                            wire:click="$set('selectedTab', 'register-tab')" />
                        <x-button label="Login" type="submit" icon="o-key" class="btn-primary" spinner="login_guest" />
                    </x-slot:actions>
                </x-form>
            </x-tab>
            <x-tab name="register-tab" label="Registrarse" icon="o-user-plus" hidden>
                <x-header title="REGISTRARSE"
                    subtitle="Nos pondremos en contacto a la brevedad con los datos de ingreso." />
                @if($message)
                    <x-alert title="Se ha registrado correctamente."
                        description="Nos pondremos en contacto a la brevedad con los datos de ingreso."
                        icon="o-information-circle" class="alert-info" />
                @else
                    <x-form wire:submit="register" no-separator>
                        <x-input label="Apellido" placeholder="Apellido" wire:model="data.lastname" icon="o-user" inline />
                        <x-input label="Nombre/s" placeholder="Nombre/s" wire:model="data.name" icon="o-user" inline />
                        <x-input label="Dirección" placeholder="Dirección" wire:model="data.address" icon="o-map-pin"
                            inline />
                        <x-input label="Ciudad" placeholder="Ciudad" wire:model="data.city" icon="o-map-pin" inline />
                        <x-input label="Código Postal" placeholder="Código Postal" wire:model="data.postal_code"
                            icon="o-hashtag" inline />
                        <x-input label="Teléfono" placeholder="Teléfono" wire:model="data.phone" icon="o-phone" inline />
                        <x-input label="E-mail" placeholder="E-mail" wire:model="data.email" type="email" icon="o-envelope"
                            inline />
                        <x-slot:actions>
                            <x-button label="Cancel" class="btn-error" wire:click="$set('selectedTab', 'users-tab')" />
                            <x-button label="Registrarme" class="btn-primary" type="submit" spinner="register" />
                        </x-slot:actions>
                    </x-form>
                @endif
            </x-tab>
        </x-tabs>
    </div>
</div>