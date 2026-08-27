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
        try {
            $admin = User::where('role', Role::ADMIN)->first();
        } catch (\Exception $e) {
            $admin = null;
        }

        if ($admin) {
            $this->warn("Ya existe un usuario administrador: {$admin->email}");

            if ($this->confirm('¿Deseas restablecer su contraseña a la de por defecto?')) {
                $password = 'Webstore18743';
                $admin->password = Hash::make($password);
                $admin->save();
                $this->info("Contraseña restablecida exitosamente a: {$password}");
            } else {
                $this->info('Operación cancelada. No se han hecho cambios.');
            }

            return;
        }

        if (! $this->confirm('Esto inicializará la base de datos (WIPE). ¿Deseas continuar?')) {
            $this->info('Operación cancelada.');

            return;
        }

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
