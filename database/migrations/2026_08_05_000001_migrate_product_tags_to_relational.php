<?php

use App\Models\Product;
use App\Models\Tag;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        // Only migrate data if the tags column still exists (Fase 1 compatibility)
        if (! Schema::hasColumn('products', 'tags')) {
            return;
        }

        // Process in chunks of 100 to handle large datasets
        Product::whereNotNull('tags')
            ->where('tags', '!=', '')
            ->orderBy('id')
            ->chunkById(100, function ($products) {
                DB::transaction(function () use ($products) {
                    Product::withoutEvents(function () use ($products) {
                        foreach ($products as $product) {
                            $tagNames = array_filter(explode('|', $product->tags));
                            if (empty($tagNames)) {
                                continue;
                            }

                            $tagIds = [];
                            foreach (array_unique($tagNames) as $tagName) {
                                $slug = Str::slug($tagName);
                                $tag = Tag::firstOrCreate(
                                    ['slug' => $slug],
                                    ['name' => strtoupper($tagName), 'slug' => $slug, 'sort_order' => 0]
                                );
                                $tagIds[] = $tag->id;
                            }

                            // Sync to pivot table
                            $product->tags()->sync($tagIds);

                            // Normalize the legacy string column (dedup, sort, uppercase)
                            $normalizedTags = Tag::whereIn('id', $tagIds)
                                ->pluck('name')
                                ->sort()
                                ->values()
                                ->all();
                            $product->setRawAttribute('tags', implode('|', $normalizedTags));
                            $product->save();
                        }
                    });
                });
            });
    }

    public function down(): void
    {
        // Detach all taggables — the tags table itself is dropped by the create_tags_table migration rollback
        DB::table('taggables')->truncate();
    }
};
