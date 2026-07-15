<?php

namespace App\Services;

use App\Helpers\SettingsHelper;
use App\Models\ListName;
use App\Models\ListPrice;
use App\Models\Product;

class PriceListService
{
    /**
     * Ya no hay listas U. Retornamos los datos tal cual.
     */
    public function normalize(int $listId, array $data): array
    {
        return [
            'list_id' => $listId,
            'data' => $data,
        ];
    }

    /**
     * Ya no hay listas U. Retornamos el mismo listId.
     */
    public function resolveBaseListId(int $listId): int
    {
        return $listId;
    }

    /**
     * Obtiene todas las listas principales (ya no filtramos por "U").
     */
    public function getPrincipalLists()
    {
        return ListName::all();
    }

    /**
     * Obtiene el precio efectivo (bulk o unit) para un producto y una lista dada.
     */
    public function getEffectivePrice(int $listId, int $productId, bool $skipPromo = false): ?float
    {
        $list = ListName::find($listId);
        if (! $list) {
            return null;
        }

        if (! $skipPromo) {
            $promoListId = (int) SettingsHelper::settings('promo_list_id');
            $promoListPrice = $this->resolveListPrice($promoListId, $listId, $productId);

            if ($promoListPrice) {
                return (float) $promoListPrice->price;
            }
        }

        $listPrice = ListPrice::query()
            ->where('list_id', $listId)
            ->where('product_id', $productId)
            ->first();

        if (! $listPrice) {
            return null;
        }

        return (float) $listPrice->price;
    }

    /**
     * Calcula el precio total de un ítem aplicando la lógica de cobro mixto (bulto + unidades sueltas).
     */
    public function calculateItemPrice(int $listId, Product $product, int $quantity): float
    {
        $qttyPackage = max(1, $product->qtty_package);
        $promoListId = (int) SettingsHelper::settings('promo_list_id');

        $listPrice = $this->resolveListPrice($promoListId, $listId, $product->id)
            ?? ListPrice::query()->where('list_id', $listId)->where('product_id', $product->id)->first();

        if ($listPrice) {
            $bulkPrice = (float) $listPrice->price;
            $unitPrice = (float) $listPrice->unit_price;
            if ($unitPrice <= 0) {
                $unitPrice = $bulkPrice;
            }
        } else {
            $bulkPrice = (float) ($product->price ?? 0);
            $unitPrice = (float) ($product->price ?? 0);
        }

        $packagesQuantity = floor($quantity / $qttyPackage) * $qttyPackage;
        $extraQuantity = $quantity % $qttyPackage;

        return ($packagesQuantity * $bulkPrice) + ($extraQuantity * $unitPrice);
    }

    /**
     * Retorna el ListPrice de la lista promo si aplica, o null.
     * La lista promo no aplica si no está configurada o si coincide con la lista del usuario.
     */
    private function resolveListPrice(int $promoListId, int $userListId, int $productId): ?ListPrice
    {
        if (! $promoListId || $promoListId === $userListId) {
            return null;
        }

        return ListPrice::query()
            ->where('list_id', $promoListId)
            ->where('product_id', $productId)
            ->first();
    }
}
