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

            if (file_exists($cartFile = storage_path("app/private/" . current_user_cart_id() . "_cart.json"))) {
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

                if (file_exists($cartFile = storage_path("app/private/" . current_user_cart_id() . "_cart.json"))) {
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

            if (file_exists($cartFile = storage_path("app/private/" . current_user_cart_id() . "_cart.json"))) {
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
<div class="min-h-screen flex justify-center items-center bg-neutral relative overflow-hidden text-neutral-content">
    <div class="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/carbon-fibre.png')] opacity-10 mix-blend-overlay"></div>
    <div class="absolute top-[-20%] left-[-10%] w-[50%] h-[50%] rounded-full bg-primary/20 blur-[120px] animate-pulse pointer-events-none"></div>
    <div class="absolute bottom-[-20%] right-[-10%] w-[40%] h-[40%] rounded-full bg-error/20 blur-[100px] pointer-events-none" style="animation: pulse 4s cubic-bezier(0.4, 0, 0.6, 1) infinite 1s;"></div>
    
    <div class="w-11/12 sm:w-3/4 md:w-[420px] mx-auto bg-base-300/30 backdrop-blur-xl rounded-3xl shadow-[0_20px_50px_rgba(0,0,0,0.5)] p-8 border border-white/10 z-10 transition-all duration-500 hover:border-primary/30">
        
        <div class="flex flex-col items-center mb-6">
            <img src="{{ asset('imgs/Logos/ER50.png') }}" class="h-20 mb-4 logo-glow" alt="Encendido Reconquista">
            <h2 class="text-2xl font-black tracking-tight uppercase text-white">Ingresar</h2>
        </div>

        @if (session('success'))
            <x-alert icon="o-check-circle" class="alert-success mb-6 text-xs font-bold shadow-sm rounded-xl">
                {{ session('success') }}
            </x-alert>
        @endif

        @if (session('error'))
            <x-alert icon="o-exclamation-triangle" class="alert-error mb-6 text-xs font-bold shadow-sm rounded-xl">
                {{ session('error') }}
            </x-alert>
        @endif

        <x-form wire:submit="login" no-separator class="space-y-4">
            <x-input label="E-mail" wire:model="email" icon="o-envelope" class="bg-base-100/50 text-white border-white/10 focus:border-primary focus:ring-1 focus:ring-primary transition-all" />
            
            <x-password label="Contraseña" wire:model="password" icon="o-lock-closed" right class="bg-base-100/50 text-white border-white/10 focus:border-primary focus:ring-1 focus:ring-primary transition-all" />

            <x-slot:actions>
                <div class="flex gap-3 w-full mt-4">
                    <x-button label="Volver" @click="window.history.back()" icon="o-arrow-uturn-left"
                        class="btn-ghost btn-sm flex-1 hover:bg-white/10" />
                    <x-button label="INGRESAR" type="submit" icon="o-key" class="btn-primary flex-[2] font-black tracking-wider shadow-[0_0_15px_rgba(220,38,38,0.3)] hover:shadow-[0_0_25px_rgba(220,38,38,0.5)] transition-all" spinner="login" />
                </div>
            </x-slot:actions>
        </x-form>
    </div>
</div>
