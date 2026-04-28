<?php

namespace App\Traits;

use App\Models\ListName;
use App\Models\Product;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait HasPricingList
{
    /**
     * Get the pricing list associated with the user.
     */
    public function list(): BelongsTo
    {
        return $this->belongsTo(ListName::class, 'list_id');
    }

    /**
     * Get the price of a specific product for this user.
     */
    public function getProductPrice(Product $product): ?float
    {
        if (! $this->list) {
            return null;
        }

        return (float) ($this->list->listPrices()
            ->where('product_id', $product->id)
            ->first()
            ->price ?? null);
    }
}
