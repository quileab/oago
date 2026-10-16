<?php

namespace App\Traits;

use App\Models\Tag;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Str;

trait HasTags
{
    /**
     * Boot the trait: the pivot table is the single source of truth.
     * Tags are only ever written explicitly, never derived from other columns.
     */
    public static function bootHasTags(): void
    {
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
            ->map(fn ($s) => $this->resolveSlugToTagId($s))
            ->all();

        $this->tags()->sync($tagIds);
        Tag::clearCache();

        if ($this->exists) {
            $this->touch();
        }

        return $this;
    }

    /**
     * Resolve a slug to an existing tag id, creating it only when missing.
     */
    protected function resolveSlugToTagId(string $slug): int
    {
        $slug = Str::slug($slug);

        $tag = Tag::query()->where('slug', $slug)->first();

        if ($tag) {
            return $tag->id;
        }

        // Derive a human readable name from the slug so the tag is not shown
        // to customers as a hyphenated identifier.
        return Tag::fromName(ucwords(str_replace('-', ' ', $slug)))->id;
    }

    /**
     * Sync tags from an array of tag IDs.
     * Ignores IDs that no longer exist in the tags table.
     */
    public function syncTagsById(array $tagIds): self
    {
        $tagIds = array_values(array_filter(
            array_map('intval', $tagIds),
            fn ($id) => $id > 0
        ));

        if ($tagIds) {
            $tagIds = Tag::whereIn('id', $tagIds)->pluck('id')->all();
        }

        $this->tags()->sync($tagIds);
        Tag::clearCache();

        return $this;
    }

    /**
     * Accessor: get tag names as array.
     */
    public function getTagsArrayAttribute(): array
    {
        if (! $this->relationLoaded('tags')) {
            $this->load('tags');
        }

        return $this->getRelation('tags')->pluck('name')->all();
    }

    /**
     * Accessor: pipe-delimited tag names, kept for API backward compatibility.
     */
    public function getTagsStringAttribute(): string
    {
        return implode('|', $this->tags_array);
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
