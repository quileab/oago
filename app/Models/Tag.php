<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class Tag extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'sort_order' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function products(): MorphToMany
    {
        return $this->morphedByMany(Product::class, 'taggable');
    }

    /**
     * Cached collection of all tags, ordered by sort_order.
     */
    public static function allCached(): Collection
    {
        return Cache::remember('tags.all', 3600, function () {
            return self::orderBy('sort_order')->get();
        });
    }

    /**
     * Find or create a tag from a raw name string.
     * Normalizes name to uppercase, slug from the name.
     */
    public static function fromName(string $name): self
    {
        $slug = Str::slug($name);

        return self::firstOrCreate(
            ['slug' => $slug],
            ['name' => strtoupper($name), 'slug' => $slug, 'sort_order' => 0]
        );
    }

    /**
     * Resolve a single tag slug to its name.
     */
    public static function resolveName(string $slug): ?string
    {
        return Cache::remember('tags.name.'.$slug, 3600, function () use ($slug) {
            return self::where('slug', $slug)->value('name');
        });
    }

    /**
     * Clear all tag-related caches.
     */
    public static function clearCache(): void
    {
        Cache::forget('tags.all');
    }
}
