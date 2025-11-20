<?php

namespace Database\Seeders;

use App\Models\Achievement;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AchievementSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Achievement::create([
            'type' => 'points',
            'name' => 'Puntos de Bienvenida',
            'description' => 'Puntos otorgados al registrarse.',
            'data' => ['amount' => 100],
        ]);

        Achievement::create([
            'type' => 'medal',
            'name' => 'Medalla Primera Compra',
            'description' => 'Medalla por realizar la primera compra.',
            'data' => ['icon' => 'medal_first_purchase.png'],
        ]);

        Achievement::create([
            'type' => 'badge',
            'name' => 'Insignia Adoptador Temprano',
            'description' => 'Insignia por ser uno de los primeros usuarios.',
            'data' => ['color' => 'bronze'],
        ]);
    }
}
