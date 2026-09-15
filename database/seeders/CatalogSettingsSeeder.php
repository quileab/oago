<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class CatalogSettingsSeeder extends Seeder
{
    public function run(): void
    {
        Setting::firstOrCreate(
            ['key' => 'catalog_display_mode'],
            [
                'value' => 'infinite_scroll',
                'type' => 'string',
                'text' => 'Modo de catálogo',
                'description' => 'Modo de visualización del catálogo de productos: infinite_scroll o paginated',
            ]
        );

        Setting::firstOrCreate(
            ['key' => 'catalog_items_per_page'],
            [
                'value' => '30',
                'type' => 'number',
                'text' => 'Productos por página / carga',
                'description' => 'Cantidad de productos a mostrar por página (paginado) o en la carga inicial (scroll infinito)',
            ]
        );
    }
}
