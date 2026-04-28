<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

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
                'description' => 'El número de días que un invitado puede acceder al sistema.',
            ]
        );

        // Number format separator
        Setting::updateOrCreate(
            ['key' => 'number_format_separator'],
            [
                'value' => ',',
                'type' => 'string',
                'text' => 'Separador Decimal',
                'description' => 'El caracter usado para separar los decimales en los precios.',
            ]
        );

        // CSV separator
        Setting::updateOrCreate(
            ['key' => 'csv_separator'],
            [
                'value' => ',',
                'type' => 'string',
                'text' => 'Separador CSV',
                'description' => 'El caracter para separar columnas en archivos CSV.',
            ]
        );

        // Product tags
        Setting::updateOrCreate(
            ['key' => 'product_tags'],
            [
                'value' => json_encode(['NUEVO', 'OFERTA', 'REMATE', 'IMPORTADOS', 'DESTACADO', 'LIQUIDACION', 'ULTIMAS_UNIDADES']), // Array, will be json_encoded by update_setting
                'type' => 'json',
                'text' => 'Tags de Productos',
                'description' => 'Lista de tags de productos separados por comas (ej. NUEVO,OFERTA,REMATE).',
            ]
        );

        // Copyright
        Setting::updateOrCreate(
            ['key' => 'copyright'],
            [
                'value' => '© InnoDesign - 2025',
                'type' => 'string',
                'text' => 'Texto de Copyright',
                'description' => 'Texto que aparece en el pie de página.',
            ]
        );

        // Marca Blanca: Empresa
        Setting::updateOrCreate(
            ['key' => 'company_name'],
            [
                'value' => 'Distribuidora Agostini',
                'type' => 'string',
                'text' => 'Nombre de la Empresa',
                'description' => 'Nombre visible en pestañas y pie de página.',
            ]
        );
        Setting::updateOrCreate(
            ['key' => 'company_phone'],
            [
                'value' => '+54 9 342 463-8925',
                'type' => 'string',
                'text' => 'Teléfono Principal',
                'description' => 'Teléfono de contacto de la empresa.',
            ]
        );
        Setting::updateOrCreate(
            ['key' => 'company_email'],
            [
                'value' => 'contacto@oagostini.com.ar',
                'type' => 'string',
                'text' => 'Email Principal',
                'description' => 'Email de contacto de la empresa.',
            ]
        );
        Setting::updateOrCreate(
            ['key' => 'company_address'],
            [
                'value' => 'Av. José Gorriti, S3000, Santa Fe',
                'type' => 'string',
                'text' => 'Dirección Física',
                'description' => 'Dirección mostrada en el footer.',
            ]
        );
        Setting::updateOrCreate(
            ['key' => 'company_map_iframe'],
            [
                'value' => '<iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d108759.57602058032!2d-60.77123984714352!3d-31.621213897914803!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x95b5a9adc40888e1%3A0xdcf7760e4d023270!2sSanta%20Fe!5e0!3m2!1sen!2sar!4v1700000000000!5m2!1sen!2sar" width="100%" height="300" style="border:0; border-radius: 0.5rem;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>',
                'type' => 'string',
                'text' => 'Iframe Google Maps',
                'description' => 'Código de inserción del mapa de Google.',
            ]
        );

        // Redes Sociales (JSON)
        Setting::updateOrCreate(
            ['key' => 'social_networks'],
            [
                'value' => json_encode([
                    [
                        'platform' => 'Facebook',
                        'url' => 'https://www.facebook.com/OAgostinidistribuciones/',
                        'icon_svg' => '<svg class="w-6 h-6 fill-current group-hover:scale-110 transition-transform" viewBox="0 0 24 24"><path d="M12,2C6.477,2,2,6.477,2,12c0,5.013,3.693,9.153,8.505,9.876V14.65H8.031v-2.629h2.474v-1.749 c0-2.896,1.411-4.167,3.818-4.167c1.153,0,1.762,0.085,2.051,0.124v2.294h-1.642c-1.022,0-1.379,0.969-1.379,2.061v1.437h2.995 l-0.406,2.629h-2.588v7.247C18.235,21.236,22,17.062,22,12C22,6.477,17.523,2,12,2z" /></svg>',
                    ],
                    [
                        'platform' => 'Instagram',
                        'url' => 'https://www.instagram.com/o.a.distribuciones',
                        'icon_svg' => '<svg class="w-6 h-6 fill-current group-hover:scale-110 transition-transform" viewBox="0 0 24 24"><path d="M 8 3 C 5.239 3 3 5.239 3 8 L 3 16 C 3 18.761 5.239 21 8 21 L 16 21 C 18.761 21 21 18.761 21 16 L 21 8 C 21 5.239 18.761 3 16 3 L 8 3 z M 18 5 C 18.552 5 19 5.448 19 6 C 19 6.552 18.552 7 18 7 C 17.448 7 17 6.552 17 6 C 17 5.448 17.448 5 18 5 z M 12 7 C 14.761 7 17 9.239 17 12 C 17 14.761 14.761 17 12 17 C 9.239 17 7 14.761 7 12 C 7 9.239 9.239 7 12 7 z M 12 9 A 3 3 0 0 0 9 12 A 3 3 0 0 0 12 15 A 3 3 0 0 0 15 12 A 3 3 0 0 0 12 9 z" /></svg>',
                    ],
                    [
                        'platform' => 'WhatsApp',
                        'url' => 'https://api.whatsapp.com/send/?phone=5493424638925&text&type=phone_number&app_absent=0',
                        'icon_svg' => '<svg class="w-8 h-8 fill-current" viewBox="0 0 24 24"><path d="M19.077,4.928C17.191,3.041,14.683,2.001,12.011,2c-5.506,0-9.987,4.479-9.989,9.985 c-0.001,1.76,0.459,3.478,1.333,4.992L2,22l5.233-1.237c1.459,0.796,3.101,1.215,4.773,1.216h0.004 c5.505,0,9.986-4.48,9.989-9.985C22.001,9.325,20.963,6.816,19.077,4.928z M16.898,15.554c-0.208,0.583-1.227,1.145-1.685,1.186 c-0.458,0.042-0.887,0.207-2.995-0.624c-2.537-1-4.139-3.601-4.263-3.767c-0.125-0.167-1.019-1.353-1.019-2.581 S7.581,7.936,7.81,7.687c0.229-0.25,0.499-0.312,0.666-0.312c0.166,0,0.333,0,0.478,0.006c0.178,0.007,0.375,0.016,0.562,0.431 c0.222,0.494,0.707,1.728,0.769,1.853s0.104,0.271,0.021,0.437s-0.125,0.27-0.249,0.416c-0.125,0.146-0.262,0.325-0.374,0.437 c-0.125,0.124-0.255,0.26-0.11,0.509c0.146,0.25,0.646,1.067,1.388,1.728c0.954,0.85,1.757,1.113,2.007,1.239 c0.25,0.125,0.395,0.104,0.541-0.063c0.146-0.166,0.624-0.728,0.79-0.978s0.333-0.208,0.562-0.125s1.456,0.687,1.705,0.812 c0.25,0.125,0.416,0.187,0.478,0.291C17.106,14.471,17.106,14.971,16.898,15.554z" /></svg>',
                    ],
                ]),
                'type' => 'json',
                'text' => 'Redes Sociales (JSON)',
                'description' => 'Arreglo JSON de redes sociales (platform, url, icon_svg).',
            ]
        );
    }
}
