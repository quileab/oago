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
     * Obtiene todas las listas principales.
     */
    public function getPrincipalLists()
    {
        return ListName::all();
    }

    /**
     * Resuelve el list_id efectivo de un usuario (null/0 -> default setting -> null).
     */
    public function resolveListId($user): ?int
    {
        $listId = $user?->list_id;
        if (is_numeric($listId) && (int) $listId > 0) {
            return (int) $listId;
        }

        $default = (int) SettingsHelper::settings('alt_user_default_price', 0);
        if ($default > 0) {
            return $default;
        }

        return null;
    }

    /**
     * Precio base (sin promo) para un producto y una lista.
     * Trata precio 0 como "no configurado" y cae a product->price.
     */
    public function getBasePrice(?int $listId, Product $product): float
    {
        if (! $listId) {
            return (float) ($product->price ?? 0);
        }

        $lp = ListPrice::query()
            ->where('list_id', $listId)
            ->where('product_id', $product->id)
            ->first();

        if ($lp && (float) $lp->price > 0) {
            return (float) $lp->price;
        }

        return (float) ($product->price ?? 0);
    }

    /**
     * Precio promocional solo si es > 0 y menor que el base.
     */
    public function getPromoPrice(?int $listId, Product $product, float $basePrice): ?float
    {
        $promoListId = (int) SettingsHelper::settings('promo_list_id');
        if (! $promoListId || $promoListId === $listId) {
            return null;
        }

        $lp = ListPrice::query()
            ->where('list_id', $promoListId)
            ->where('product_id', $product->id)
            ->first();

        if (! $lp) {
            return null;
        }

        $promo = (float) $lp->price;
        if ($promo > 0 && $promo < $basePrice) {
            return $promo;
        }

        return null;
    }

    /**
     * Obtiene el precio efectivo (bulk o unit) para un producto y una lista dada.
     */
    public function getEffectivePrice(int $listId, int $productId, bool $skipPromo = false): ?float
    {
        $resolved = $listId > 0 ? $listId : null;

        if (! $resolved) {
            return null;
        }

        $lp = ListPrice::query()
            ->where('list_id', $resolved)
            ->where('product_id', $productId)
            ->first();

        if (! $lp || (float) $lp->price <= 0) {
            return null;
        }

        $base = (float) $lp->price;

        if ($skipPromo) {
            return $base;
        }

        $promoListId = (int) SettingsHelper::settings('promo_list_id');
        if ($promoListId && $promoListId !== $resolved) {
            $promoLp = ListPrice::query()
                ->where('list_id', $promoListId)
                ->where('product_id', $productId)
                ->first();
            if ($promoLp && (float) $promoLp->price > 0 && (float) $promoLp->price < $base) {
                return (float) $promoLp->price;
            }
        }

        return $base;
    }

    /**
     * Cantidad a facturar tras aplicar la oferta por cantidad (bonus).
     */
    public function calculateBillableQuantity(?Product $product, int $orderedQuantity): int
    {
        if (! $product || ! $product->hasBonus()) {
            return $orderedQuantity;
        }

        $bonusGroup = $product->bonus_threshold + $product->bonus_amount;
        $freeUnits = intdiv($orderedQuantity, $bonusGroup) * $product->bonus_amount;

        return $orderedQuantity - $freeUnits;
    }

    /**
     * Calcula el precio total de un ítem aplicando cobro mixto (bulto + unidades sueltas).
     */
    public function calculateItemPrice(?int $listId, Product $product, int $quantity): float
    {
        $qttyPackage = max(1, $product->qtty_package);

        // Precio base (lista del usuario o product->price)
        if ($listId) {
            $baseLp = ListPrice::query()
                ->where('list_id', $listId)
                ->where('product_id', $product->id)
                ->first();
            if ($baseLp && (float) $baseLp->price > 0) {
                $bulkBase = (float) $baseLp->price;
                $unitBase = (float) $baseLp->unit_price > 0 ? (float) $baseLp->unit_price : $bulkBase;
            } else {
                $bulkBase = (float) ($product->price ?? 0);
                $unitBase = (float) ($product->price ?? 0);
            }
        } else {
            $bulkBase = (float) ($product->price ?? 0);
            $unitBase = (float) ($product->price ?? 0);
        }

        // Promo: solo si es > 0 y menor que el base
        $promoListId = (int) SettingsHelper::settings('promo_list_id');
        if ($promoListId && $promoListId !== (int) $listId) {
            $promoLp = ListPrice::query()
                ->where('list_id', $promoListId)
                ->where('product_id', $product->id)
                ->first();
            if ($promoLp && (float) $promoLp->price > 0 && (float) $promoLp->price < $bulkBase) {
                $bulk = (float) $promoLp->price;
                $unit = (float) $promoLp->unit_price > 0 ? (float) $promoLp->unit_price : $bulk;
            } else {
                $bulk = $bulkBase;
                $unit = $unitBase;
            }
        } else {
            $bulk = $bulkBase;
            $unit = $unitBase;
        }

        $packagesQuantity = floor($quantity / $qttyPackage) * $qttyPackage;
        $extraQuantity = $quantity % $qttyPackage;

        return ($packagesQuantity * $bulk) + ($extraQuantity * $unit);
    }

    /**
     * Calcula la línea de pedido: unitario efectivo + subtotal redondeado.
     * Sin migración: price se guarda como subtotal/cantidad (unitario promedio).
     */
    public function calculateLine(?int $listId, ?Product $product, int $orderedQuantity): array
    {
        if (! $product) {
            return ['unit_price' => 0.0, 'subtotal' => 0.0, 'billable_quantity' => 0];
        }
        $billable = $this->calculateBillableQuantity($product, $orderedQuantity);
        $trueSubtotal = round($this->calculateItemPrice($listId, $product, $billable), 2);
        $unitPrice = $orderedQuantity > 0 ? round($trueSubtotal / $orderedQuantity, 2) : 0.0;

        return [
            'unit_price' => $unitPrice,
            'subtotal' => $trueSubtotal,
            'billable_quantity' => $billable,
        ];
    }
}
