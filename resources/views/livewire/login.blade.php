<?php
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Rule;
use Livewire\Attributes\Title;

new #[Layout('components.layouts.empty')]       // <-- Here is the `empty` layout
    #[Title('Login')]
    class extends Component {

    #[Rule('required|email')]
    public string $email = '';

    #[Rule('required')]
    public string $password = '';

    public function mount()
    {
        // It is logged in
        if (auth()->user()) {
            return redirect('/');
        }
    }

    public function login()
    {
        $credentials = $this->validate();

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
}; ?>

<div class="min-h-screen flex justify-center items-center">
    <div
        class="w-3/4 md:w-1/3 mx-auto bg-slate-900 bg-opacity-40 backdrop-blur-xl rounded-lg shadow-lg shadow-black/50 p-4">
        <x-header title="LOGIN" />
        <x-form wire:submit="login" no-separator>
            <x-input label="E-mail" wire:model="email" icon="o-envelope" inline />
            <x-input label="Password" wire:model="password" type="password" icon="o-key" inline />

            <x-slot:actions>
                {{-- <x-button label="Create an account" class="btn-ghost" link="/register" /> --}}
                <x-button label="Login" type="submit" icon="o-paper-airplane" class="btn-primary" spinner="login" />
            </x-slot:actions>
        </x-form>
    </div>
</div>