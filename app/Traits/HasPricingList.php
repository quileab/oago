<?php

namespace App\Traits;

use App\Models\ListName;
use App\Models\Product;
use App\Services\PriceListService;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait HasPricingList
{
    public function list(): BelongsTo
    {
        return $this->belongsTo(ListName::class, 'list_id');
    }

    public function getProductPrice(Product $product): ?float
    {
        $priceService = app(PriceListService::class);
        $listId = $priceService->resolveListId($this);
        $price = $priceService->getEffectivePrice((int) ($listId ?? 0), $product->id, false);

        return $price ?? (float) ($product->price ?? 0);
    }
}
