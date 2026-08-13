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

new #[Layout('components.layouts.clean')] #[Title('Registrarse - Encendido Reconquista & CIBAT')] class extends Component {
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

    private function generateCaptchaImage(string $text): string
    {
        $width = 180;
        $height = 60;
        $image = imagecreatetruecolor($width, $height);
        $bg = imagecolorallocate($image, 255, 255, 255);
        $textColor = imagecolorallocate($image, 20, 20, 20);
        imagefilledrectangle($image, 0, 0, $width, $height, $bg);

        $fontPath = public_path('fonts/captcha/DejaVuSansMono.ttf');
        $fontSize = 22;
        $angle = rand(-6, 6);
        $box = imagettfbbox($fontSize, $angle, $fontPath, $text);
        $textWidth = $box[2] - $box[0];
        $textHeight = $box[1] - $box[7];
        $x = (int) (($width - $textWidth) / 2);
        $y = (int) (($height - $textHeight) / 2 + $textHeight);
        imagettftext($image, $fontSize, $angle, $x + 1, $y + 1, $textColor, $fontPath, $text);
        imagettftext($image, $fontSize, $angle, $x, $y, $textColor, $fontPath, $text);

        for ($i = 0; $i < 15; $i++) {
            $randomColor = imagecolorallocate($image, rand(100, 255), rand(100, 255), rand(100, 255));
            imageline($image, rand(0, $width), rand(0, $height), rand(0, $width), rand(0, $height), $randomColor);
        }

        for ($i = 0; $i < 400; $i++) {
            $dotColor = imagecolorallocate($image, rand(150, 230), rand(150, 230), rand(150, 230));
            imagesetpixel($image, rand(0, $width), rand(0, $height), $dotColor);
        }

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
<div class="bg-neutral min-h-screen text-neutral-content antialiased relative overflow-hidden">
    <div class="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/carbon-fibre.png')] opacity-10 mix-blend-overlay pointer-events-none"></div>
    <div class="absolute top-[10%] left-[-10%] w-[50%] h-[50%] rounded-full bg-primary/20 blur-[120px] animate-pulse pointer-events-none"></div>
    
    {{-- Navbar Link (Volver) --}}
    <div class="fixed top-0 left-0 w-full z-50 p-6 flex justify-between items-center pointer-events-none">
        <a href="/" class="btn btn-sm btn-circle btn-ghost pointer-events-auto bg-base-100/50 backdrop-blur-md">
            <x-icon name="o-arrow-left" class="w-5 h-5 text-white" />
        </a>
    </div>

    {{-- Hero Section --}}
    <div class="relative h-64 md:h-80 overflow-hidden flex items-center justify-center border-b border-white/10 z-10 bg-base-300/30 backdrop-blur-xl shadow-lg">
        <div class="relative text-center px-4 mt-8">
            <img src="{{ asset('imgs/Logos/ER50.png') }}" class="h-20 md:h-24 mx-auto mb-6 logo-glow" alt="Encendido Reconquista">
            <h1 class="text-3xl md:text-5xl font-black uppercase tracking-tight text-white drop-shadow-[0_2px_10px_rgba(0,0,0,0.5)]">
                Regístrate
            </h1>
            <p class="text-primary font-bold mt-2 drop-shadow-md tracking-wider">
                Unite a nuestra red y potenciá tu negocio
            </p>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-6 py-12 relative z-10">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-12">
            {{-- Info --}}
            <div class="lg:col-span-1 space-y-6">
                <h2 class="text-3xl font-extrabold tracking-tight text-white">¿Por qué elegirnos?</h2>
                <div class="space-y-4">
                    @foreach([
                        ['icon' => 'o-cube', 't' => 'Variedad de Stock', 's' => 'Catálogo completo de repuestos.'],
                        ['icon' => 'o-currency-dollar', 't' => 'Precios Competitivos', 's' => 'Escalas mayoristas exclusivas.'],
                        ['icon' => 'o-truck', 't' => 'Logística Propia', 's' => 'Entregas rápidas a toda la región.']
                    ] as $item)
                    <div class="flex items-start gap-4 p-5 bg-base-300/30 backdrop-blur-sm rounded-2xl border border-white/5 shadow-lg hover:border-primary/30 hover:-translate-y-1 transition-all">
                        <div class="p-2 bg-primary/20 rounded-lg text-primary"><x-icon :name="$item['icon']" class="w-6 h-6" /></div>
                        <div><p class="font-bold text-sm text-white">{{ $item['t'] }}</p><p class="text-xs text-neutral-content/70 mt-1">{{ $item['s'] }}</p></div>
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- Form --}}
            <div class="lg:col-span-2 bg-base-300/30 backdrop-blur-xl p-8 md:p-12 rounded-[40px] shadow-[0_20px_50px_rgba(0,0,0,0.5)] border border-white/10 transition-all duration-500 hover:border-primary/20">
                @if($submitted)
                    <div class="text-center py-12 space-y-6">
                        <div class="inline-flex items-center justify-center w-24 h-24 bg-primary/20 text-primary rounded-full mb-4 shadow-[0_0_30px_rgba(220,38,38,0.4)]">
                            <x-icon name="o-check-circle" class="w-16 h-16" />
                        </div>
                        <h3 class="text-4xl font-black tracking-tight text-white">¡Solicitud Enviada!</h3>
                        <div class="max-w-md mx-auto space-y-4">
                            <p class="text-lg text-neutral-content/80">
                                Hemos recibido tus datos correctamente. En unos minutos recibirás un correo electrónico en <span class="font-bold text-white">{{ $email }}</span> con tus credenciales y el enlace de activación.
                            </p>
                            <div class="p-4 bg-primary/10 rounded-2xl border border-primary/20 flex items-start gap-3 text-left">
                                <x-icon name="o-information-circle" class="w-6 h-6 text-primary mt-0.5 shrink-0" />
                                <p class="text-sm text-neutral-content/80">
                                    <strong>¿No recibiste el mail?</strong> Revisá tu carpeta de Correo No Deseado o Spam. Si el problema persiste, contactanos por WhatsApp.
                                </p>
                            </div>
                        </div>
                        <div class="pt-6">
                            <x-button label="Volver al Inicio" link="/" class="btn-outline text-white hover:bg-white hover:text-black rounded-2xl px-8 tracking-widest font-bold" />
                        </div>
                    </div>
                @else
                    <h3 class="text-3xl font-black mb-2 tracking-tight text-white">Solicitud de Cuenta</h3>
                    <p class="text-neutral-content/60 mb-8 text-sm font-medium">Recibirás tus credenciales por email inmediatamente.</p>

                    <form wire:submit="submit" class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <x-input label="Nombre" wire:model="name" class="bg-base-100/50 text-white border-white/10 focus:border-primary focus:ring-1 focus:ring-primary transition-all" />
                            <x-input label="Apellido" wire:model="lastname" class="bg-base-100/50 text-white border-white/10 focus:border-primary focus:ring-1 focus:ring-primary transition-all" />
                            <x-input label="Email" wire:model="email" class="bg-base-100/50 text-white border-white/10 focus:border-primary focus:ring-1 focus:ring-primary transition-all" />
                            <x-input label="Teléfono" wire:model="phone" class="bg-base-100/50 text-white border-white/10 focus:border-primary focus:ring-1 focus:ring-primary transition-all" />
                        </div>
                        <x-input label="Dirección Comercial" wire:model="address" class="bg-base-100/50 text-white border-white/10 focus:border-primary focus:ring-1 focus:ring-primary transition-all" />
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <x-input label="Localidad" wire:model="city" class="bg-base-100/50 text-white border-white/10 focus:border-primary focus:ring-1 focus:ring-primary transition-all" />
                            <x-input label="Código Postal" wire:model="postal_code" class="bg-base-100/50 text-white border-white/10 focus:border-primary focus:ring-1 focus:ring-primary transition-all" />
                        </div>

                        <div class="bg-base-100/30 p-6 rounded-3xl flex flex-col md:flex-row items-center gap-6 border border-white/10 mt-8">
                            <div class="flex-1 text-center md:text-left">
                                <p class="text-xs font-bold uppercase tracking-widest text-primary mb-3">Seguridad</p>
                                <div class="flex items-center gap-4 justify-center md:justify-start">
                                    <div class="bg-white p-2 rounded-2xl border border-white shadow-inner">
                                        <img src="{{ $captcha_image }}" class="h-16 w-auto md:h-16 rounded-xl">
                                    </div>
                                    <x-button icon="o-arrow-path" wire:click="generateCaptcha" class="btn-ghost btn-circle text-neutral-content hover:text-white hover:bg-white/10 transition-all" />
                                </div>
                            </div>
                            <div class="w-full md:w-32">
                                <x-input wire:model="captcha_answer" label="Resultado" type="number" class="text-center font-black text-2xl border-white/10 bg-base-100/50 text-white focus:border-primary" />
                            </div>
                        </div>
                        
                        <div class="pt-8 space-y-4">
                            <x-button label="Solicitar Mi Cuenta Ahora" type="submit" icon="o-rocket-launch" class="w-full btn-lg bg-primary text-white font-black tracking-widest rounded-3xl shadow-[0_0_20px_rgba(220,38,38,0.4)] hover:shadow-[0_0_30px_rgba(220,38,38,0.6)] border-none transition-all hover:scale-[1.02]" spinner="submit" />
                            <div class="flex items-center justify-center gap-3 mt-4 opacity-70">
                                <x-icon name="o-shield-check" class="w-4 h-4 text-primary" />
                                <p class="text-xs text-neutral-content font-medium">Tus datos están seguros. Te enviaremos un email de activación.</p>
                            </div>
                        </div>
                    </form>
                @endif
            </div>
        </div>
    </div>
</div>
