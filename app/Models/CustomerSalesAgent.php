<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class CustomerSalesAgent extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'is_admin_assigned' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function salesAgent(): MorphTo
    {
        return $this->morphTo();
    }
}
