<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The `product_tags` setting became inert once tags moved to the
     * `taggables` pivot. Remove it so it does not show up as an editable
     * setting in the admin that would have no effect.
     */
    public function up(): void
    {
        if (! Schema::hasTable('settings')) {
            return;
        }

        DB::table('settings')->where('key', 'product_tags')->delete();
    }

    public function down(): void
    {
        if (! Schema::hasTable('settings')) {
            return;
        }

        DB::table('settings')->updateOrInsert(
            ['key' => 'product_tags'],
            [
                'value' => json_encode(['NUEVO', 'OFERTA', 'REMATE', 'IMPORTADOS', 'DESTACADO', 'LIQUIDACION', 'ULTIMAS_UNIDADES']),
                'type' => 'json',
                'text' => 'Tags de Productos',
                'description' => 'Lista de tags de productos separados por comas (ej. NUEVO,OFERTA,REMATE).',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
};
