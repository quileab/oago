<?php

use App\Models\Product;
use App\Models\Tag;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('products', 'tags')) {
            return;
        }

        if (Schema::hasTable('tags') && Schema::hasTable('taggables')) {
            Product::query()
                ->select('id', 'tags')
                ->whereNotNull('tags')
                ->where('tags', '!=', '')
                ->orderBy('id')
                ->chunkById(200, function ($products) {
                    foreach ($products as $product) {
                        $names = array_values(array_unique(array_filter(array_map('trim', explode('|', (string) $product->tags)))));
                        if ($names === []) {
                            continue;
                        }

                        $tagIds = array_map(fn (string $name) => Tag::fromName($name)->id, $names);
                        $product->tags()->syncWithoutDetaching($tagIds);
                    }
                });

            Tag::clearCache();
        }

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('tags');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('products', 'tags')) {
            return;
        }

        Schema::table('products', function (Blueprint $table) {
            $table->string('tags', 50)->default('');
        });
    }
};
