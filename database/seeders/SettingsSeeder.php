<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Setting;

class SettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Guest access TTL: 10 days
        Setting::updateOrCreate(
            ['key' => 'guest_access_ttl_days'],
            [
                'value' => 10, // 10 days
                'type' => 'number',
                'text' => 'Duración de Acceso Invitado (Días)',
                'description' => 'El número de días que un invitado puede acceder al sistema.'
            ]
        );

        // Number format separator
        Setting::updateOrCreate(
            ['key' => 'number_format_separator'],
            [
                'value' => ',',
                'type' => 'string',
                'text' => 'Separador Decimal',
                'description' => 'El caracter usado para separar los decimales en los precios.'
            ]
        );

        // CSV separator
        Setting::updateOrCreate(
            ['key' => 'csv_separator'],
            [
                'value' => ',',
                'type' => 'string',
                'text' => 'Separador CSV',
                'description' => 'El caracter para separar columnas en archivos CSV.'
            ]
        );

        // Product tags
        Setting::updateOrCreate(
            ['key' => 'product_tags'],
            [
                'value' => json_encode(['NUEVO', 'OFERTA', 'REMATE', 'IMPORTADOS', 'DESTACADO', 'LIQUIDACION', 'ULTIMAS_UNIDADES']), // Array, will be json_encoded by update_setting
                'type' => 'json',
                'text' => 'Tags de Productos',
                'description' => 'Lista de tags de productos separados por comas (ej. NUEVO,OFERTA,REMATE).'
            ]
        );
    }
}
