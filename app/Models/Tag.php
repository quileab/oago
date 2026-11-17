<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class Tag extends Model
{
    use HasFactory;

    public const TYPE_COMMERCIAL = 'commercial';

    public const TYPE_DIVISION = 'division';

    protected $guarded = [];

    protected $casts = [
        'sort_order' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::deleting(function (self $tag) {
            // taggables has no foreign keys, so pivot rows would be orphaned.
            DB::table('taggables')->where('tag_id', $tag->getKey())->delete();
        });
    }

    public function products(): MorphToMany
    {
        return $this->morphedByMany(Product::class, 'taggable');
    }

    public function isDivision(): bool
    {
        return ($this->type ?? self::TYPE_COMMERCIAL) === self::TYPE_DIVISION;
    }

    /**
     * Parse comma/pipe separated aliases or fallback keywords configured in description.
     *
     * @return array<int, string>
     */
    public function keywordsList(): array
    {
        $raw = trim((string) ($this->description ?? ''));
        if ($raw === '') {
            return [];
        }

        $parts = preg_split('/[,|]+/', $raw) ?: [];

        return array_values(array_unique(array_filter(array_map(
            fn (string $term): string => mb_strtolower(trim($term)),
            $parts
        ))));
    }

    /**
     * Keywords suitable for substring fallback matching on product brand/description (>= 3 chars).
     *
     * @return array<int, string>
     */
    public function fallbackMatchKeywords(): array
    {
        return array_values(array_filter(
            $this->keywordsList(),
            fn (string $term): bool => mb_strlen($term) >= 3
        ));
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
     * Cached collection of commercial/promotional tags only (excludes business divisions).
     */
    public static function commercialCached(): Collection
    {
        return self::allCached()
            ->filter(fn (self $tag): bool => ! $tag->isDivision())
            ->values();
    }

    /**
     * Cached collection of business division tags keyed by slug.
     */
    public static function divisionsCached(): Collection
    {
        return self::allCached()
            ->filter(fn (self $tag): bool => $tag->isDivision())
            ->keyBy('slug');
    }

    /**
     * Normalize a division query parameter or alias (via division tag keywords) to its canonical slug.
     */
    public static function normalizeDivisionSlug(?string $division): ?string
    {
        $clean = mb_strtolower(trim((string) $division));
        if ($clean === '') {
            return null;
        }

        $slug = Str::slug($clean);
        $divisions = self::divisionsCached();

        if ($slug !== '' && $divisions->has($slug)) {
            return $slug;
        }

        foreach ($divisions as $divisionTag) {
            if (in_array($clean, $divisionTag->keywordsList(), true)) {
                return $divisionTag->slug;
            }
        }

        return $slug !== '' ? $slug : null;
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
            [
                'name' => strtoupper($name),
                'slug' => $slug,
                'type' => self::TYPE_COMMERCIAL,
                'sort_order' => 0,
            ]
        );
    }

    /**
     * Filter an array of tag names to exclude business division names.
     *
     * @param  array<int, string>  $tagNames
     * @return array<int, string>
     */
    public static function filterCommercialNames(array $tagNames): array
    {
        $divisionKeys = [];
        foreach (self::divisionsCached() as $tag) {
            $divisionKeys[mb_strtoupper($tag->name)] = true;
            $divisionKeys[mb_strtoupper($tag->slug)] = true;
        }

        return array_values(array_filter(
            $tagNames,
            fn (string $name): bool => ! isset($divisionKeys[mb_strtoupper(trim($name))])
        ));
    }

    /**
     * Resolve division appearance/metadata from an optional division Tag plus configured keyword fallbacks.
     *
     * @param  array<int, string>  $tagsArray
     * @return array{slug: string, store_key: string, name: string, color: string, fallback_image: string}
     */
    public static function resolveProductDivision(
        ?self $divisionTag,
        array $tagsArray = [],
        ?string $brand = null,
        ?string $description = null
    ): array {
        $divisions = self::divisionsCached();

        if ($divisionTag === null && $tagsArray !== []) {
            foreach ($tagsArray as $tagName) {
                $slugCandidate = Str::slug((string) $tagName);
                if ($slugCandidate !== '' && $divisions->has($slugCandidate)) {
                    $divisionTag = $divisions->get($slugCandidate);

                    break;
                }
            }
        }

        if ($divisionTag === null && $divisions->isNotEmpty()) {
            $defaultCandidate = null;
            $brandText = (string) $brand;
            $descText = (string) $description;

            foreach ($divisions as $candidate) {
                $keywords = $candidate->fallbackMatchKeywords();
                if ($keywords === []) {
                    $defaultCandidate ??= $candidate;

                    continue;
                }

                foreach ($keywords as $keyword) {
                    if (stripos($brandText, $keyword) !== false || stripos($descText, $keyword) !== false) {
                        $divisionTag = $candidate;

                        break 2;
                    }
                }
            }

            $divisionTag ??= $defaultCandidate ?? $divisions->first();
        }

        $slug = $divisionTag?->slug ?? 'general';

        return [
            'slug' => $slug,
            'store_key' => $slug,
            'name' => $divisionTag?->name ?? strtoupper($slug),
            'color' => $divisionTag?->color ?: '#1b365d',
            'fallback_image' => $divisionTag?->fallback_image ?: 'fallback.webp',
        ];
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
