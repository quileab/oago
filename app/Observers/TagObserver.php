<?php

namespace App\Observers;

use App\Models\Tag;
use Illuminate\Support\Facades\Cache;

class TagObserver
{
    public function created(Tag $tag): void
    {
        $this->clearCaches();
    }

    public function updated(Tag $tag): void
    {
        // Invalidate cache for both old and new slug
        Cache::forget('tags.name.'.$tag->getRawOriginal('slug'));
        Cache::forget('tags.name.'.$tag->slug);
        $this->clearCaches();
    }

    public function deleted(Tag $tag): void
    {
        Cache::forget('tags.name.'.$tag->slug);
        $this->clearCaches();
    }

    protected function clearCaches(): void
    {
        Cache::forget('tags.all');
    }
}
