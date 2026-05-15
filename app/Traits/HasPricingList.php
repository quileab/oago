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
        if (! $this->list_id) {
            return (float) $product->price;
        }

        $priceService = app(\App\Services\PriceListService::class);
        $price = $priceService->getEffectivePrice($this->list_id, $product->id);

        return $price ?? (float) ($product->price ?? 0);
    }
}
