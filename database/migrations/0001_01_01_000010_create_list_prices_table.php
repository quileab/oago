<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('list_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')
                  ->constrained('products')
                  ->onDelete('cascade'); // Eliminar precios si se elimina el producto
        
            $table->foreignId('list_id')
                  ->constrained('list_names') // Asegúrate de que exista una tabla `lists` o ajustar el nombre
                  ->onDelete('cascade'); // Eliminar precios si se elimina la lista de precios

            // only one Product_ID and List_ID per list
            $table->unique(['product_id', 'list_id']);
       
            $table->decimal('price', 12, 2);
            $table->decimal('unit_price', 12, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('list_prices');
    }
};
