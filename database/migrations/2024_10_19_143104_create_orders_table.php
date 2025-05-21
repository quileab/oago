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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(\App\Models\User::class)->constrained(); // Relación con users
            $table->decimal('total_price', 12, 2); // Precio total del pedido

            $table->string('sending_method', 25)->nullable();
            $table->string('sending_address', 100)->nullable();
            $table->string('sending_city', 50)->nullable();
            $table->string('contact_name', 40)->nullable();
            $table->string('contact_number', 20)->nullable();
            $table->string('transport_detail', 100)->nullable();
            $table->string('payment_method', 25)->nullable();
            $table->string('payment_detail', 100)->nullable();
            $table->string('information', 255)->nullable();
            $table->string('status', 20)->default('pending'); // Estado del envío (pending, shipped, delivered)

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
