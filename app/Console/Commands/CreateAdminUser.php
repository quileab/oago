<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CreateAdminUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:create-admin-user';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Crea un usuario administrador por defecto e inicializa la base de datos.';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $this->info('Inicializando base de datos...');

        // Asegurarse de que el usuario admin no existe
        $email = 'admin@admin.com';
        $password = 'Webstore18743';

        if (\App\Models\User::where('email', $email)->exists()) {
            $this->warn("El usuario {$email} ya existe.");
        } else {
            \App\Models\User::create([
                'name' => 'admin',
                'lastname' => 'admin',
                'role' => \App\Enums\Role::ADMIN,
                'address' => 'admin',
                'city' => 'admin',
                'postal_code' => '9999',
                'phone' => '+5493482111111',
                'email' => $email,
                'password' => \Illuminate\Support\Facades\Hash::make($password),
            ]);
            $this->info("Usuario admin creado: {$email} / {$password}");
        }

        $this->info('Ejecutando seeders adicionales...');
        $this->call('db:seed', ['--class' => 'SettingsSeeder']);
        $this->call('db:seed', ['--class' => 'AchievementSeeder']);

        $this->info('¡Proceso completado con éxito!');
    }
}
