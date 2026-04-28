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
        if (Auth::guard('alt')->attempt($credentials, true)) {
            $guest = Auth::guard('alt')->user();

            // Check if this email also exists as a normal user
            $normalUser = \App\Models\User::where('email', $credentials['email'])->first();

            if ($normalUser) {
                // The user exists in both tables. We prioritize the normal user.
                // We log them out of the 'alt' guard and log them in as the normal user.
                Auth::guard('alt')->logout();
                Auth::login($normalUser, true);

                // Now execute the same logic as a successful normal login
                if ($normalUser->role->value == 'none') {
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

            // Normal alternative user login flow
            if ($guest->role->value == 'none') {
                Auth::guard('alt')->logout();
                $this->addError('email', 'La cuenta está en revisión o desactivada.');
                return;
            }

            request()->session()->put('is_alt_login', true);

            if (file_exists($cartFile = storage_path("app/private/" . Auth::guard('alt')->id() . "_cart.json"))) {
                $cart = json_decode(file_get_contents($cartFile), true);
                foreach ($cart as $item) {
                    $prod = \App\Models\ListPrice::where('product_id', $item['product_id'])
                        ->where('list_id', $guest->list_id)
                        ->first();
                    $cart[$item['product_id']]['price'] = $prod->price ?? $item['price'];
                }
                session()->put('cart', $cart);
            }

            return redirect()->intended('/');
        }

        $this->addError('email', 'Login incorrecto. Intentelo de nuevo.');
    }
}; ?>

<div class="min-h-screen flex justify-center items-center">
    <div class="w-11/12 sm:w-3/4 md:w-[420px] mx-auto bg-base-100/70 backdrop-blur-2xl rounded-2xl shadow-2xl p-6 border border-base-content/10">
        
        <div class="flex flex-col items-center mb-4">
            <img src="{{ asset('imgs/brand-logo.webp') }}" class="w-32 mb-2 drop-shadow-xl" alt="Brand Logo">
            <h2 class="text-xl font-black tracking-tighter uppercase">Ingresar</h2>
        </div>

        @if (session('success'))
            <x-alert icon="o-check-circle" class="alert-success mb-4 text-xs font-bold shadow-sm">
                {{ session('success') }}
            </x-alert>
        @endif

        @if (session('error'))
            <x-alert icon="o-exclamation-triangle" class="alert-error mb-4 text-xs font-bold shadow-sm">
                {{ session('error') }}
            </x-alert>
        @endif

        <x-form wire:submit="login" no-separator class="space-y-4">
            <x-input label="E-mail" wire:model="email" icon="o-envelope" />
            
            <x-password label="Contraseña" wire:model="password" icon="o-lock-closed" right />

            <x-slot:actions>
                <div class="flex gap-3 w-full mt-1">
                    <x-button label="Volver" @click="window.history.back()" icon="o-arrow-uturn-left"
                        class="btn-ghost btn-sm flex-1" />
                    <x-button label="INGRESAR" type="submit" icon="o-key" class="btn-primary flex-[2] font-bold" spinner="login" />
                </div>
            </x-slot:actions>
        </x-form>
    </div>
</div>