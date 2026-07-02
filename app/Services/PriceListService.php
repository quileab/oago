<?php

namespace App\Services;

use App\Helpers\SettingsHelper;
use App\Models\ListName;
use App\Models\ListPrice;
use App\Models\Product;

class PriceListService
{
    /**
     * Resuelve el ID de la lista base y los datos normalizados.
     * Si la lista termina en "U", redirige a la lista base y mapea 'price' a 'unit_price'.
     */
    public function normalize(int $listId, array $data): array
    {
        $list = ListName::find($listId);

        if ($list && str_ends_with(trim($list->name), 'U')) {
            $baseName = preg_replace('/ U$/', '', trim($list->name));
            // Buscamos la lista base. Usamos LIKE para mayor flexibilidad con espacios legacy.
            $baseList = ListName::where('name', 'LIKE', $baseName.'%')
                ->where('id', '!=', $listId)
                ->first();

            if ($baseList) {
                $listId = $baseList->id;
                // En listas "U", el campo 'price' de la entrada se trata como 'unit_price'
                if (isset($data['price'])) {
                    $data['unit_price'] = $data['price'];
                    unset($data['price']);
                }
            }
        }

        return [
            'list_id' => $listId,
            'data' => $data,
        ];
    }

    /**
     * Resuelve solo el ID de la lista base.
     */
    public function resolveBaseListId(int $listId): int
    {
        return $this->normalize($listId, [])['list_id'];
    }

    /**
     * Obtiene solo las listas principales (excluyendo las de tipo "U").
     */
    public function getPrincipalLists()
    {
        return ListName::all()->filter(function ($list) {
            return ! str_ends_with(trim($list->name), 'U');
        });
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

        $isUnit = str_ends_with(trim($list->name), 'U');
        $baseListId = $this->resolveBaseListId($listId);

        if (! $skipPromo) {
            $promoListId = (int) SettingsHelper::settings('promo_list_id');
            $promoListPrice = $this->resolveListPrice($promoListId, $listId, $productId);

            if ($promoListPrice) {
                return (float) ($isUnit ? ($promoListPrice->unit_price ?: $promoListPrice->price) : $promoListPrice->price);
            }
        }

        $listPrice = ListPrice::query()
            ->where('list_id', $baseListId)
            ->where('product_id', $productId)
            ->first();

        if (! $listPrice) {
            return null;
        }

        return (float) ($isUnit ? ($listPrice->unit_price ?: $listPrice->price) : $listPrice->price);
    }

    /**
     * Calcula el precio total de un ítem aplicando la lógica de cobro mixto (bulto + unidades sueltas).
     */
    public function calculateItemPrice(int $listId, Product $product, int $quantity): float
    {
        $baseListId = $this->resolveBaseListId($listId);
        $qttyPackage = max(1, $product->qtty_package);
        $promoListId = (int) SettingsHelper::settings('promo_list_id');

        $listPrice = $this->resolveListPrice($promoListId, $listId, $product->id)
            ?? ListPrice::query()->where('list_id', $baseListId)->where('product_id', $product->id)->first();

        if ($listPrice) {
            $bulkPrice = (float) $listPrice->price;
            $unitPrice = (float) ($listPrice->unit_price ?: $listPrice->price);
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
