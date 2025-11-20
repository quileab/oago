<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        if (!User::where('email', 'admin@admin.com')->exists()) {
            User::factory()->create([
                'name' => 'admin',
                'lastname' => 'admin',
                'role' => 'admin',
                'address' => 'admin',
                'city' => 'admin',
                'postal_code' => '9999',
                'phone' => '+5493482111111',
                'email' => 'admin@admin.com',
                'password' => Hash::make('Oagos2025'),
            ]);
        }

        $this->call([
            SettingsSeeder::class,
            AchievementSeeder::class,
        ]);
    }
}
