<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;
use Mary\Traits\Toast;
use App\Models\AltUser;
use App\Enums\Role;
use App\Mail\AltUserWelcomeMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

new #[Layout('components.layouts.clean')] #[Title('Quiero ser Cliente - Agostini Distribuidor')] class extends Component {
    use Toast;

    public string $name = '';
    public string $lastname = '';
    public string $email = '';
    public string $phone = '';
    public string $address = '';
    public string $city = '';
    public string $postal_code = '';
    public bool $submitted = false;

    // Captcha logic
    public int $num1;
    public int $num2;
    public ?int $captcha_answer = null;
    public string $captcha_image = '';

    public function mount()
    {
        $this->generateCaptcha();
    }

    public function generateCaptcha()
    {
        $this->num1 = rand(1, 9);
        $this->num2 = rand(1, 9);
        $this->captcha_image = $this->generateCaptchaImage($this->num1 . ' + ' . $this->num2);
    }

    public function toJSON()
    {
        return [];
    }

    private function generateCaptchaImage(string $text): string
    {
        $width = 180;
        $height = 60;
        $image = imagecreatetruecolor($width, $height);
        $bg = imagecolorallocate($image, 255, 255, 255);
        $textColor = imagecolorallocate($image, 20, 20, 20);
        imagefilledrectangle($image, 0, 0, $width, $height, $bg);

        for ($i = 0; $i < 10; $i++) {
            $randomColor = imagecolorallocate($image, rand(100, 255), rand(100, 255), rand(100, 255));
            imageline($image, rand(0, $width), rand(0, $height), rand(0, $width), rand(0, $height), $randomColor);
        }

        for ($i = 0; $i < 400; $i++) {
            $dotColor = imagecolorallocate($image, rand(150, 230), rand(150, 230), rand(150, 230));
            imagesetpixel($image, rand(0, $width), rand(0, $height), $dotColor);
        }

        $font = 5;
        $x = ($width - (strlen($text) * imagefontwidth($font))) / 2;
        $y = ($height - imagefontheight($font)) / 2;
        imagestring($image, $font, (int)$x, (int)$y, $text, $textColor);
        imagestring($image, $font, (int)$x + 1, (int)$y, $text, $textColor);

        ob_start();
        imagepng($image);
        $imageData = ob_get_clean();
        imagedestroy($image);
        return 'data:image/png;base64,' . base64_encode($imageData);
    }

    public function submit()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'lastname' => 'required|string|max:255',
            'email' => 'required|email',
            'phone' => 'required|string|max:20',
            'address' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'postal_code' => 'required|string|max:10',
            'captcha_answer' => 'required|integer',
        ]);

        $existingUser = AltUser::where('email', $this->email)->first();
        if ($existingUser) {
            $ttl = \App\Helpers\SettingsHelper::settings('guest_access_ttl_days', 10);
            if ($existingUser->created_at->addDays($ttl)->isPast()) {
                $this->addError('email', "Tu período de prueba de {$ttl} días ha finalizado. Contacta ventas.");
            } else {
                $this->addError('email', "Ya posees una cuenta activa. Revisa tu correo.");
            }
            $this->generateCaptcha();
            return;
        }

        if ($this->captcha_answer !== ($this->num1 + $this->num2)) {
            $this->addError('captcha_answer', 'Respuesta incorrecta.');
            $this->generateCaptcha();
            return;
        }

        $tempPassword = Str::random(8);
        $activationToken = Str::random(60);
        $defaultList = \App\Helpers\SettingsHelper::settings('alt_user_default_price', 2);

        $user = AltUser::create([
            'name' => $this->name, 'lastname' => $this->lastname, 'email' => $this->email,
            'phone' => $this->phone, 'address' => $this->address, 'city' => $this->city,
            'postal_code' => $this->postal_code, 'password' => $tempPassword,
            'role' => Role::NONE, 'list_id' => $defaultList, 'activation_token' => $activationToken,
        ]);

        if ($user) {
            try {
                Mail::to($user->email)->send(new AltUserWelcomeMail($user, $tempPassword, $activationToken));
            } catch (\Exception $e) {
                logger()->error("Error Mail: " . $e->getMessage());
            }
            $this->submitted = true;
            $this->success('¡Registro exitoso!', position: 'toast-bottom toast-end');
        }
    }
}; ?>

<div data-theme="light" class="bg-white min-h-screen text-gray-900 antialiased">
    {{-- Hero --}}
    <div class="relative h-48 md:h-72 overflow-hidden flex items-center justify-center bg-gray-900">
        <img src="{{ asset('imgs/brand.webp') }}" class="absolute inset-0 w-full h-full object-cover opacity-40 blur-xs">
        <div class="absolute inset-0 bg-linear-to-t from-gray-900/80 to-transparent"></div>
        <div class="relative text-center px-4">
            <x-header title="Quiero ser Cliente" subtitle="Unite a nuestra red y potenciá tu negocio" size="text-4xl md:text-5xl" class="text-white font-black" />
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-6 py-12">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-12">
            {{-- Info --}}
            <div class="lg:col-span-1 space-y-6">
                <h2 class="text-3xl font-extrabold italic text-gray-900">¿Por qué elegirnos?</h2>
                <div class="space-y-4">
                    @foreach([
                        ['icon' => 'o-check-badge', 't' => 'Variedad de Stock', 's' => 'Catálogo completo.'],
                        ['icon' => 'o-currency-dollar', 't' => 'Precios Competitivos', 's' => 'Escalas mayoristas.'],
                        ['icon' => 'o-truck', 't' => 'Logística Propia', 's' => 'Entregas rápidas.']
                    ] as $item)
                    <div class="flex items-start gap-4 p-4 bg-gray-50 rounded-2xl border border-gray-100">
                        <div class="p-2 bg-red-700 rounded-lg text-white"><x-icon :name="$item['icon']" class="w-5 h-5" /></div>
                        <div><p class="font-bold text-sm text-gray-900">{{ $item['t'] }}</p><p class="text-xs text-gray-500">{{ $item['s'] }}</p></div>
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- Form --}}
            <div class="lg:col-span-2 bg-white p-8 md:p-12 rounded-[40px] shadow-2xl border border-gray-200">
                @if($submitted)
                    <div class="text-center py-12 space-y-6">
                        <div class="inline-flex items-center justify-center w-24 h-24 bg-green-100 text-green-600 rounded-full mb-4">
                            <x-icon name="o-check-circle" class="w-16 h-16" />
                        </div>
                        <h3 class="text-4xl font-black italic text-gray-900">¡Solicitud Enviada!</h3>
                        <div class="max-w-md mx-auto space-y-4">
                            <p class="text-lg text-gray-600">
                                Hemos recibido tus datos correctamente. En unos minutos recibirás un correo electrónico en <span class="font-bold text-gray-900">{{ $email }}</span> con tus credenciales y el enlace de activación.
                            </p>
                            <div class="p-4 bg-blue-50 rounded-2xl border border-blue-100 flex items-start gap-3 text-left">
                                <x-icon name="o-information-circle" class="w-5 h-5 text-blue-600 mt-0.5" />
                                <p class="text-sm text-blue-800">
                                    <strong>¿No recibiste el mail?</strong> Revisá tu carpeta de Correo No Deseado o Spam. Si el problema persiste, contactanos por WhatsApp.
                                </p>
                            </div>
                        </div>
                        <div class="pt-6">
                            <x-button label="Volver al Inicio" link="/" class="btn-outline border-gray-300 rounded-2xl px-8" />
                        </div>
                    </div>
                @else
                    <h3 class="text-3xl font-black mb-2 italic text-gray-900">Solicitud de Cuenta</h3>
                    <p class="text-gray-500 mb-6 text-sm">Recibirás tus credenciales por email inmediatamente.</p>

                    <form wire:submit="submit" class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <x-input label="Nombre" wire:model="name" class="bg-white border-gray-200 text-gray-900" />
                            <x-input label="Apellido" wire:model="lastname" class="bg-white border-gray-200 text-gray-900" />
                            <x-input label="Email" wire:model="email" class="bg-white border-gray-200 text-gray-900" />
                            <x-input label="Teléfono" wire:model="phone" class="bg-white border-gray-200 text-gray-900" />
                        </div>
                        <x-input label="Dirección Comercial" wire:model="address" class="bg-white border-gray-200 text-gray-900" />
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <x-input label="Localidad" wire:model="city" class="bg-white border-gray-200 text-gray-900" />
                            <x-input label="Código Postal" wire:model="postal_code" class="bg-white border-gray-200 text-gray-900" />
                        </div>

                        <div class="bg-gray-50 p-6 rounded-2xl flex flex-col md:flex-row items-center gap-6 border border-gray-200">
                            <div class="flex-1 text-center md:text-left">
                                <p class="text-xs font-bold uppercase text-gray-500 mb-2">Seguridad</p>
                                <div class="flex items-center gap-4">
                                    <div class="bg-white p-2 rounded-xl border border-gray-200 shadow-inner">
                                        <img src="{{ $captcha_image }}" class="h-10">
                                    </div>
                                    <x-button icon="o-arrow-path" wire:click="generateCaptcha" class="btn-ghost btn-circle btn-sm text-gray-400" />
                                </div>
                            </div>
                            <div class="w-full md:w-32">
                                <x-input wire:model="captcha_answer" label="Resultado" type="number" class="text-center font-bold text-2xl border-red-700/40 bg-white text-gray-900" />
                            </div>
                        </div>
                        
                        <div class="pt-4 space-y-4">
                            <x-button label="Solicitar Mi Cuenta Ahora" type="submit" icon="o-rocket-launch" class="w-full btn-lg bg-red-700 text-white font-black rounded-2xl shadow-xl shadow-red-700/20 border-none" spinner="submit" />
                            <div class="flex items-center gap-3 p-4 bg-gray-50 rounded-2xl border border-gray-100">
                                <x-icon name="o-information-circle" class="w-5 h-5 text-red-700" />
                                <p class="text-xs text-gray-600 font-medium">Validaremos tus datos y te enviaremos el email de activación.</p>
                            </div>
                        </div>
                    </form>
                @endif
            </div>
        </div>
    </div>
</div>