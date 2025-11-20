<?php
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Rule;
use Livewire\Attributes\Title;
use Mary\Traits\Toast;
use App\Helpers\SettingsHelper;
use Illuminate\Support\Facades\Auth;

new #[Layout('components.layouts.empty')]
    #[Title('Iniciar Sesión')]
    class extends Component {
    use Toast;

    public string $email = '';
    public string $password = '';

    public function mount()
    {
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

        // Try normal user login
        if (Auth::attempt($credentials)) {
            $user = Auth::user();
            if ($user->role->value == 'none') {
                Auth::logout();
                $this->addError('email', 'La cuenta está en revisión');
                return;
            }

            request()->session()->regenerate();

            if (file_exists($cartFile = storage_path("app/private/" . Auth::id() . "_cart.json"))) {
                $cart = json_decode(file_get_contents($cartFile), true);
                foreach ($cart as $item) {
                    $prod = \App\Models\ListPrice::where('product_id', $item['product_id'])
                        ->where('list_id', auth()->user()->list_id)
                        ->first();
                    $cart[$item['product_id']]['price'] = $prod->price ?? $item['price'];
                }
                session()->put('cart', $cart);
            }

            return redirect()->intended('/');
        }

        // If normal login fails, try alternative user
        $guest = \App\Models\AltUser::where('email', $credentials['email'])->first();

        if ($guest && \Illuminate\Support\Facades\Hash::check($credentials['password'], $guest->password)) {
            $expiration_days = SettingsHelper::settings('guest_access_ttl_days', 10);
            $expiration_date = $guest->created_at->addDays($expiration_days);

            if (now()->isAfter($expiration_date)) {
                $this->addError('email', 'Su período de invitado ha caducado.');
                return;
            }

            if ($guest->role->value == 'none') {
                $this->addError('email', 'La cuenta está en revisión o desactivada.');
                return;
            }

            Auth::guard('alt')->login($guest, true);
            request()->session()->put('is_alt_login', true);

            return redirect()->intended('/');
        }

        $this->addError('email', 'Login incorrecto. Intentelo de nuevo.');
    }
}; ?>

<div class="min-h-screen flex justify-center items-center">
    <div data-theme="dark"
        class="w-3/4 md:w-1/3 mx-auto bg-slate-900/80 backdrop-blur-xl rounded-lg shadow-lg shadow-black/50 p-4">
        <x-header title="INGRESAR" />
        <x-form wire:submit="login" no-separator>
            <x-input label="E-mail" wire:model="email" icon="o-envelope" inline />
            <div x-data="{ showPassword: false }">
                <div x-show="!showPassword">
                    <x-input label="Password" wire:model="password" type="password" icon="o-lock-closed" inline>
                        <x-slot:append>
                            <x-button @click="showPassword = !showPassword" icon="o-eye-slash" class="join-item" />
                        </x-slot:append>
                    </x-input>
                </div>
                <div x-show="showPassword" style="display: none;">
                    <x-input label="Password" wire:model="password" type="text" icon="o-lock-closed" inline>
                        <x-slot:append>
                            <x-button @click="showPassword = !showPassword" icon="o-eye" class="join-item" />
                        </x-slot:append>
                    </x-input>
                </div>
            </div>

            <x-slot:actions>
                <div class="flex justify-between w-full">
                    <x-button label="Volver" @click="window.history.back()" icon="o-arrow-uturn-left"
                        class="btn-neutral" />
                    <x-button label="INGRESAR" type="submit" icon="o-key" class="btn-primary" spinner="login" />
                </div>
            </x-slot:actions>
        </x-form>
    </div>
</div>