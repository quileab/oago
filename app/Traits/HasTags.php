<?php

namespace App\Traits;

use App\Models\Tag;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

trait HasTags
{
    /**
     * Boot the trait: register write-through sync on saved event.
     */
    public static function bootHasTags(): void
    {
        static::saved(function (self $model) {
            if ($model->wasRecentlyCreated || $model->wasChanged('tags')) {
                $model->syncTagsFromStringColumn();
            }
        });

        static::deleted(function (self $model) {
            $model->tags()->detach();
        });
    }

    /**
     * Polymorphic relationship to tags.
     */
    public function tags(): MorphToMany
    {
        return $this->morphToMany(Tag::class, 'taggable')
            ->orderBy('tags.sort_order', 'asc');
    }

    /**
     * Scope: filter models that have a given tag slug.
     */
    public function scopeWithTag(Builder $query, string $slug): Builder
    {
        return $query->whereHas('tags', fn ($q) => $q->where('slug', $slug));
    }

    /**
     * Scope: filter models that have any of the given tag slugs.
     */
    public function scopeWithAnyTag(Builder $query, array $slugs): Builder
    {
        return $query->whereHas('tags', fn ($q) => $q->whereIn('slug', $slugs));
    }

    /**
     * Sync tags by slug array, normalizing via Tag::fromName().
     */
    public function syncTagsBySlug(array $slugs): self
    {
        $tagIds = collect($slugs)
            ->filter()
            ->map(fn ($s) => Tag::fromName($s)->id)
            ->all();

        $this->tags()->sync($tagIds);
        Tag::clearCache();

        return $this;
    }

    /**
     * Sync the pivot from the legacy pipe-delimited string column.
     * Called automatically on save when the "tags" column changes.
     */
    /**
     * Public entry point for syncing tags from the string column.
     * Used by DataImport and data migrations that bypass Eloquent events.
     */
    public function syncTagsFromStringColumnPublic(): void
    {
        $this->syncTagsFromStringColumn();
    }

    protected function syncTagsFromStringColumn(): void
    {
        $tagString = $this->getAttributeValue('tags');

        if (! $tagString || $tagString === '') {
            $this->tags()->sync([]);

            // Normalize the string column to empty (dedup, sort)
            return;
        }

        $tagNames = array_values(array_filter(explode('|', $tagString)));
        if (empty($tagNames)) {
            $this->tags()->sync([]);

            return;
        }

        $normalizedNames = array_map('strtoupper', $tagNames);
        $normalizedNames = array_values(array_unique($normalizedNames));
        sort($normalizedNames);

        $tagIds = array_map(fn ($name) => Tag::fromName($name)->id, $normalizedNames);
        $this->tags()->sync($tagIds);
        Tag::clearCache();
    }

    /**
     * Accessor: get tag names as array.
     * Uses the loaded relationship if available, falls back to parsing the string column.
     */
    public function getTagsArrayAttribute(): array
    {
        if ($this->relationLoaded('tags')) {
            $names = $this->getRelation('tags')->pluck('name')->all();
            if (! empty($names)) {
                return $names;
            }
            // Fallback: relation loaded but empty — check string column (transition period)
        }

        return array_values(array_filter(explode('|', $this->attributes['tags'] ?? '')));
    }

    /**
     * Check if the model has a given tag (by slug or name).
     */
    public function hasTag(string $tag): bool
    {
        return $this->tags->contains(fn ($t) => $t->slug === $tag || strtoupper($t->name) === strtoupper($tag)
        );
    }
}
