<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Índice compuesto para el orden por defecto del catálogo:
     * featured DESC, updated_at DESC (ProductSearchService::searchProducts).
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->index(['featured', 'updated_at'], 'products_featured_updated_at_index');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('products_featured_updated_at_index');
        });
    }
};
