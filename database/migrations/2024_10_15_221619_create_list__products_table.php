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
        Schema::create('list__products', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(\App\Models\Products::class)->constrained();
            $table->string('list_id',15);
            // price decima 12,2
            $table->decimal('price',12,2)->default(0);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('list__products');
    }
};
