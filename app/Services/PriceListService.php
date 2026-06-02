<?php

namespace App\Services;

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
    public function getEffectivePrice(int $listId, int $productId): ?float
    {
        $list = ListName::find($listId);
        if (! $list) {
            return null;
        }

        $isUnit = str_ends_with(trim($list->name), 'U');
        $baseListId = $this->resolveBaseListId($listId);

        $listPrice = ListPrice::where('list_id', $baseListId)
            ->where('product_id', $productId)
            ->first();

        if (! $listPrice) {
            return null;
        }

        if ($isUnit) {
            // Si la lista es de tipo "U", preferimos unit_price.
            // Si es 0 o null, podemos considerar fallback a price si así se requiere,
            // pero la lógica de normalización lo mueve a unit_price.
            return (float) ($listPrice->unit_price ?: $listPrice->price);
        }

        return (float) $listPrice->price;
    }

    /**
     * Calcula el precio total de un ítem aplicando la lógica de cobro mixto (bulto + unidades sueltas).
     */
    public function calculateItemPrice(int $listId, Product $product, int $quantity): float
    {
        $baseListId = $this->resolveBaseListId($listId);
        $qttyPackage = max(1, $product->qtty_package);

        $listPrice = ListPrice::where('list_id', $baseListId)
            ->where('product_id', $product->id)
            ->first();

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
}
