<?php

namespace App\Console\Commands;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

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
        $this->info('Limpiando base de datos y ejecutando migraciones...');

        // Wipe and re-migrate
        $this->call('migrate:fresh', ['--force' => true]);

        $this->info('Creando usuario administrador...');

        $email = 'admin@admin.com';
        $password = 'Webstore18743';

        User::create([
            'id' => 1,
            'name' => 'admin',
            'lastname' => 'admin',
            'role' => Role::ADMIN,
            'address' => 'admin',
            'city' => 'admin',
            'postal_code' => '9999',
            'phone' => '+5493482111111',
            'email' => $email,
            'password' => Hash::make($password),
        ]);

        $this->info("Usuario admin creado con ID 1: {$email} / {$password}");

        $this->info('Ejecutando seeders adicionales...');
        $this->call('db:seed', ['--class' => 'SettingsSeeder']);
        $this->call('db:seed', ['--class' => 'AchievementSeeder']);

        $this->info('¡Proceso de inicialización completado con éxito!');
    }
}
