<?php
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Rule;
use Livewire\Attributes\Title;
use Mary\Traits\Toast;

new #[Layout('components.layouts.empty')]
    #[Title('Iniciar Sesión')]
    class extends Component {
    use Toast;

    public string $email = '';
    // public string $email_guest = '';

    public string $password = '';
    // public string $password_guest = '';

    public $message = false;

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
            ]
        );

        $user = \App\Models\User::where('email', $credentials['email'])->first();

        if ($user) {
            $deniedRoles = ['none', null, ''];
            if (in_array($user->role, $deniedRoles, true)) {
                $this->addError('email', 'Usuario no activo');
                return;
            }

            if ($user->role === 'other') {
                $this->addError('email', 'Usuario no definido');
                return;
            }
        }

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

        $guest = \App\Models\GuestUser::where('email', $credentials['email'])->first();

        if ($guest && \Illuminate\Support\Facades\Hash::check($credentials['password'], $guest->password)) {
            // if 10 days passed, reject login and inform user
            $expiration_date = $guest->created_at->addDays(10);
            if (now()->isAfter($expiration_date)) {
                $this->addError('email', 'Su período de invitado ha caducado.');
                return;
            }
            // if guest user is not active, reject login and inform user
            if ($guest->role == 'none') {
                $this->addError('email', 'Su cuenta de invitado esta desactivada.');
                return;
            }

            // Iniciar sesión con el usuario invitado usando el guard 'guest'
            Auth::guard('guest')->login($guest, true);

            // Establecer la bandera de sesión para el AuthServiceProvider
            request()->session()->put('is_guest_login', true);

            return redirect()->intended('/');
        }

        $this->addError('email', 'Ingreso incorrecto. Intentelo de nuevo.');
    }

}; ?>

<div class="min-h-screen flex justify-center items-center">
    <div data-theme="dark"
        class="w-3/4 md:w-1/3 mx-auto bg-slate-900/80 backdrop-blur-xl rounded-lg shadow-lg shadow-black/50 p-4">
        <x-tabs wire:model="selectedTab">
            <x-tab name="users-tab" label="Usuarios" icon="o-users">
                <x-header title="INGRESAR" />
                <x-form wire:submit="login" no-separator>
                    <x-input label="E-mail" wire:model="email" icon="o-envelope" inline />
                    <x-input label="Password" wire:model="password" type="password" icon="o-lock-closed" inline />

                    <x-slot:actions>
                        <div class="flex justify-between w-full">
                            <x-button label="Volver" @click="window.history.back()" icon="o-arrow-uturn-left"
                                class="btn-neutral" />
                            <x-button label="INGRESAR" type="submit" icon="o-key" class="btn-primary" spinner="login" />
                        </div>
                    </x-slot:actions>
                </x-form>
            </x-tab>
            <x-tab name="guests-tab" label="Invitados" icon="o-user-group">
                <x-header title="INGRESO INVITADOS" />
                <x-form wire:submit="login_guest" no-separator>
                    <x-input label="E-mail" wire:model="email" icon="o-envelope" inline />
                    <x-input label="Password" wire:model="password" type="password" icon="o-lock-closed" inline />

                    <x-slot:actions>
                        <x-button label="Crear cuenta Invitado!" class="btn-ghost" link="/register" />
                        <x-button label="INGRESAR" type="submit" icon="o-key" class="btn-primary"
                            spinner="login_guest" />
                    </x-slot:actions>
                </x-form>
            </x-tab>
        </x-tabs>
    </div>
</div>