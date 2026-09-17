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
        Schema::table('products', function (Blueprint $table) {
            $table->index(['published', 'visibility', 'featured', 'updated_at'], 'idx_products_catalog');
            $table->index(['published', 'category'], 'idx_products_category');
            $table->index(['published', 'brand'], 'idx_products_brand');
            $table->index(['published', 'product_type', 'visibility'], 'idx_products_related');
            $table->index('barcode', 'idx_products_barcode');
            $table->index('sku', 'idx_products_sku');
        });

        Schema::table('list_prices', function (Blueprint $table) {
            $table->index(['list_id', 'product_id'], 'idx_list_prices_list_product');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->index(['status', 'created_at'], 'idx_orders_status_created');
            $table->index(['user_id', 'created_at'], 'idx_orders_user_created');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->index(['created_at', 'product_id'], 'idx_order_items_created_product');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropIndex('idx_order_items_created_product');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('idx_orders_status_created');
            $table->dropIndex('idx_orders_user_created');
        });

        Schema::table('list_prices', function (Blueprint $table) {
            $table->dropIndex('idx_list_prices_list_product');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('idx_products_catalog');
            $table->dropIndex('idx_products_category');
            $table->dropIndex('idx_products_brand');
            $table->dropIndex('idx_products_related');
            $table->dropIndex('idx_products_barcode');
            $table->dropIndex('idx_products_sku');
        });
    }
};
