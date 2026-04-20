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

<div class="flex justify-center items-center">
    <div data-theme="dark"
        class="w-11/12 sm:w-3/4 md:w-[450px] mx-auto bg-slate-900/90 backdrop-blur-2xl rounded-2xl shadow-2xl shadow-black/60 p-8 border border-white/10 mt-10 md:mt-20">
        
        <div class="flex flex-col items-center mb-8">
            <img src="{{ asset('imgs/brand.webp') }}" class="w-48 mb-4 drop-shadow-xl" alt="Brand Logo">
            <h2 class="text-2xl font-black tracking-tighter text-white uppercase">Ingresar</h2>
            <p class="text-[10px] text-slate-400 mt-1 uppercase tracking-[0.2em] font-bold">Acceso Clientes</p>
        </div>

        <x-form wire:submit="login" no-separator class="space-y-5">
            <x-input label="E-mail" wire:model="email" icon="o-envelope" 
                class="bg-slate-800/40 border-white/5 focus:border-primary" />
            
            <div x-data="{ showPassword: false }" class="relative">
                <div x-show="!showPassword">
                    <x-input label="Contraseña" wire:model="password" type="password" icon="o-lock-closed" 
                        class="bg-slate-800/40 border-white/5 focus:border-primary">
                        <x-slot:append>
                            <x-button @click="showPassword = !showPassword" icon="o-eye-slash" 
                                class="btn-ghost btn-sm text-slate-400 hover:text-white" />
                        </x-slot:append>
                    </x-input>
                </div>
                <div x-show="showPassword" style="display: none;">
                    <x-input label="Contraseña" wire:model="password" type="text" icon="o-lock-closed" 
                        class="bg-slate-800/40 border-white/5 focus:border-primary">
                        <x-slot:append>
                            <x-button @click="showPassword = !showPassword" icon="o-eye" 
                                class="btn-ghost btn-sm text-primary" />
                        </x-slot:append>
                    </x-input>
                </div>
            </div>

            <x-slot:actions>
                <div class="flex gap-3 w-full mt-2">
                    <x-button label="Volver" @click="window.history.back()" icon="o-arrow-uturn-left"
                        class="btn-ghost btn-sm flex-1 text-slate-400 border border-white/5 hover:bg-white/5" />
                    <x-button label="INGRESAR" type="submit" icon="o-key" class="btn-primary flex-[2] font-bold shadow-lg shadow-primary/20" spinner="login" />
                </div>
            </x-slot:actions>
        </x-form>
    </div>
</div>