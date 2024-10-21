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
        Schema::create('shipping_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade'); // Relación con orders
            $table->string('address', 100); // Dirección de envío
            $table->string('city', 50); // Ciudad de envío
            $table->string('postal_code', 10); // Código postal
            $table->string('phone', 15); // Teléfono de contacto
            $table->string('shipping_status', 20)->default('pending'); // Estado del envío (pending, shipped, delivered)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipping_details');
    }
};
