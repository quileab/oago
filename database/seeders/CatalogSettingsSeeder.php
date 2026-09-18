<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class CatalogSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(SettingsSeeder::class);
    }
}
