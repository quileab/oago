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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('barcode', 50)->nullable();
            $table->string('sku', 50)->nullable();
            $table->string('product_type', 30)->nullable();
            $table->string('brand', 30)->nullable();
            $table->string('model', 30)->nullable();
            $table->string('description', 100);
            $table->string('description_html', 250)->nullable();
            $table->boolean('published')->default(1);
            $table->boolean('featured')->default(0);
            $table->string('visibility', 10);
            //offer date start and end
            $table->date('offer_start')->nullable();
            $table->date('offer_end')->nullable();
            $table->string('tax_status', 10);
            $table->boolean('in_stock')->default(1);
            $table->integer('stock')->default(0);
            $table->boolean('allow_reservation')->default(0);
            $table->integer('qtty_package')->default(1);
            $table->decimal('weight',12,3)->default(1);
            $table->decimal('lenght',12,3)->default(1);
            $table->decimal('width',12,3)->default(1);
            $table->decimal('height',12,3)->default(1);
            $table->decimal('price',12,2)->default(0);
            $table->decimal('offer_price',12,2)->default(0);
            $table->string('category', 50)->nullable();
            $table->string('tags', 50)->nullable();
            $table->string('image_url', 250)->nullable();
            $table->timestamps();
            // relationships between product and list
            // $table->foreign('id')->references('product_id')->on('list_prices');//->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
