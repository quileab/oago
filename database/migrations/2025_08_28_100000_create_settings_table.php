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
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 25)->unique();
            $table->text('value')->nullable();
            // Campo para almacenar el tipo de dato
            // 'number', 'string', 'boolean', 'date', 'datetime', 'time', 'json'
            $table->string('type', 10)->nullable();
            $table->string('text', 50)->nullable();
            $table->string('description', 100)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
