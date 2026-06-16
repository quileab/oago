<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Setting::updateOrCreate(
            ['key' => 'show_prices_to_guests'],
            [
                'value' => '0',
                'type' => 'boolean',
                'text' => 'Mostrar Precios a Invitados',
                'description' => 'Si se activa, los visitantes que no hayan iniciado sesión podrán ver los precios.',
            ]
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Setting::where('key', 'show_prices_to_guests')->delete();
    }
};
