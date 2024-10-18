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
            $table->unsignedBigInteger('product_id');
            $table->string('list_id',15);
            $table->decimal('price',12,2);
            // create composite key
            // $table->unique(['product_id', 'list_id']);
            $table->timestamps();

            $table->foreign('product_id')->references('id')->on('products');//->onDelete('set null');
            $table->foreign('list_id')->references('list_id')->on('users');//->onDelete('set null');
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
