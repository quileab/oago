<?php

namespace App\Traits;

use App\Models\Achievement;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

trait HasAchievements
{
    /**
     * Get all achievements for the user.
     */
    public function achievements(): MorphToMany
    {
        return $this->morphToMany(Achievement::class, 'achievable');
    }

    /**
     * Calculate total points from achievements.
     */
    public function getTotalPointsAttribute(): int
    {
        return (int) $this->achievements()
            ->where('type', 'points')
            ->get()
            ->sum('data.amount');
    }
}
