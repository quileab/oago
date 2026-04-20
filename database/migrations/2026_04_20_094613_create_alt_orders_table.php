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
        Schema::create('alt_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('alt_user_id')->constrained('alt_users');
            $table->decimal('total_price', 12, 2);

            $table->string('sending_method', 25)->nullable();
            $table->string('sending_address', 100)->nullable();
            $table->string('sending_city', 50)->nullable();
            $table->string('contact_name', 40)->nullable();
            $table->string('contact_number', 20)->nullable();
            $table->string('transport_detail', 100)->nullable();
            $table->string('payment_method', 25)->nullable();
            $table->string('payment_detail', 100)->nullable();
            $table->string('information', 255)->nullable();
            $table->string('status', 20)->default('pending');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alt_orders');
    }
};
