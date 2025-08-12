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
        Schema::create('guest_users', function (Blueprint $table) {
            $table->id();
            $table->string('name', 30);
            $table->string('lastname', 30)->nullable();
            $table->string('address', 100)->nullable();
            $table->string('city', 30)->nullable();
            $table->string('postal_code', 10)->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('email')->unique();
            $table->string('password');
            $table->string('role', 8)->default('none');
            $table->foreignId('list_id')->nullable()
                ->constrained('list_names')
                ->onDelete('set null');
            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('guest_users');
    }
};
