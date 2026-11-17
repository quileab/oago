<?php

namespace App\Services;

use App\Helpers\SettingsHelper;
use App\Models\AltUser;
use App\Models\Product;
use App\Models\User;

class FreightService
{
    public const TYPE_PERCENTAGE = 'percentage';

    public const TYPE_FIXED = 'fixed';

    public const FREIGHT_SKU = 'FLETE-INTERNO';

    /**
     * @var array<int, string>
     */
    public const COMPANY_SHIPPING_METHODS = [
        'Envío a cargo de la Empresa a Dirección Registrada',
        'Envío a cargo de la Empresa (Dirección Alternativa)',
    ];

    /**
     * Determina si el método de envío corresponde a traslado a cargo de la empresa.
     */
    public function isCompanyShipping(?string $sendingMethod): bool
    {
        if ($sendingMethod === null || $sendingMethod === '') {
            return false;
        }

        if (in_array($sendingMethod, self::COMPANY_SHIPPING_METHODS, true)) {
            return true;
        }

        return str_starts_with($sendingMethod, 'Envío a cargo de la Empresa');
    }

    /**
     * Resuelve el tipo y valor de flete efectivo para un usuario (cliente o fallback global).
     *
     * @return array{type: string, value: float}
     */
    public function resolveFreightConfig(User|AltUser|null $user): array
    {
        if (
            $user !== null
            && in_array($user->freight_type, [self::TYPE_PERCENTAGE, self::TYPE_FIXED], true)
            && $user->freight_value !== null
        ) {
            return [
                'type' => $user->freight_type,
                'value' => max(0.0, (float) $user->freight_value),
            ];
        }

        $defaultType = SettingsHelper::settings('default_freight_type', self::TYPE_PERCENTAGE);
        if (! in_array($defaultType, [self::TYPE_PERCENTAGE, self::TYPE_FIXED], true)) {
            $defaultType = self::TYPE_PERCENTAGE;
        }

        $defaultValue = max(0.0, (float) SettingsHelper::settings('default_freight_value', 0.0));

        return [
            'type' => $defaultType,
            'value' => $defaultValue,
        ];
    }

    /**
     * Calcula el monto de flete en pesos según el subtotal de productos y el método de envío.
     */
    public function calculateFreightAmount(float $itemsSubtotal, User|AltUser|null $user, ?string $sendingMethod): float
    {
        if (! $this->isCompanyShipping($sendingMethod) || $itemsSubtotal <= 0) {
            return 0.0;
        }

        $config = $this->resolveFreightConfig($user);
        if ($config['value'] <= 0) {
            return 0.0;
        }

        $freeThreshold = max(0.0, (float) SettingsHelper::settings('free_freight_threshold', 0));
        if ($freeThreshold > 0 && $itemsSubtotal >= $freeThreshold) {
            return 0.0;
        }

        if ($config['type'] === self::TYPE_FIXED) {
            $amount = round($config['value'], 2);
        } else {
            $amount = round($itemsSubtotal * $config['value'], 2);
        }

        $minAmount = max(0.0, (float) SettingsHelper::settings('freight_min_amount', 0));
        if ($minAmount > 0 && $amount < $minAmount) {
            return round($minAmount, 2);
        }

        return $amount;
    }

    /**
     * Devuelve la etiqueta descriptiva para mostrar el cargo de flete en el checkout.
     */
    public function formatFreightLabel(User|AltUser|null $user, ?float $itemsSubtotal = null): string
    {
        $config = $this->resolveFreightConfig($user);

        if ($config['type'] === self::TYPE_FIXED) {
            return 'Flete (Monto fijo)';
        }

        $pct = rtrim(rtrim(number_format($config['value'] * 100, 2, ',', '.'), '0'), ',');
        $minAmount = max(0.0, (float) SettingsHelper::settings('freight_min_amount', 0));

        if ($itemsSubtotal !== null && $itemsSubtotal > 0 && $config['value'] > 0 && $minAmount > 0) {
            $calculated = round($itemsSubtotal * $config['value'], 2);
            if ($calculated < $minAmount) {
                return "Flete ({$pct}% - Mínimo)";
            }
        }

        return "Flete ({$pct}%)";
    }

    /**
     * @return array{type: string, value: float, min_amount: float, free_threshold: float, label: string, company_shipping_methods: array<int, string>}
     */
    public function getEffectiveFreightMetadata(User|AltUser|null $user): array
    {
        $config = $this->resolveFreightConfig($user);

        return [
            'type' => $config['type'],
            'value' => $config['value'],
            'min_amount' => max(0.0, (float) SettingsHelper::settings('freight_min_amount', 0)),
            'free_threshold' => max(0.0, (float) SettingsHelper::settings('free_freight_threshold', 0)),
            'label' => $this->formatFreightLabel($user),
            'company_shipping_methods' => self::COMPANY_SHIPPING_METHODS,
        ];
    }

    /**
     * Obtiene o crea el producto interno oculto asociado al renglón de flete.
     */
    public function getOrCreateFreightProduct(): Product
    {
        $productId = (int) SettingsHelper::settings('freight_product_id', 0);
        if ($productId > 0) {
            $configured = Product::find($productId);
            if ($configured) {
                $this->ensureFreightProductFlags($configured);

                return $configured;
            }
        }

        $product = Product::query()
            ->where('model', 'consumo interno')
            ->where('sku', self::FREIGHT_SKU)
            ->first();

        if (! $product) {
            $product = Product::create([
                'barcode' => '',
                'sku' => self::FREIGHT_SKU,
                'product_type' => 'service',
                'brand' => '',
                'model' => 'consumo interno',
                'category' => '',
                'description' => 'Flete - Envío a cargo de la Empresa',
                'description_html' => 'Cargo por traslado a cargo de la empresa',
                'published' => false,
                'featured' => false,
                'visibility' => 'hidden',
                'tax_status' => 'taxable',
                'in_stock' => true,
                'deduct_stock' => false,
                'stock' => 0,
                'allow_reservation' => false,
                'qtty_package' => 1,
                'qtty_unit' => 1,
                'by_bulk' => false,
                'price' => 0,
                'offer_price' => 0,
            ]);
        } else {
            $this->ensureFreightProductFlags($product);
        }

        SettingsHelper::update_setting('freight_product_id', $product->id);

        return $product;
    }

    protected function ensureFreightProductFlags(Product $product): void
    {
        if ($product->deduct_stock || (int) $product->stock !== 0 || $product->allow_reservation) {
            $product->update([
                'deduct_stock' => false,
                'stock' => 0,
                'allow_reservation' => false,
            ]);
        }
    }

    /**
     * Verifica si un product_id corresponde al producto interno de flete.
     */
    public function isFreightProduct(int|string|null $productId): bool
    {
        if (! $productId) {
            return false;
        }

        $configuredId = (int) SettingsHelper::settings('freight_product_id', 0);
        if ($configuredId > 0 && (int) $productId === $configuredId) {
            return true;
        }

        return Product::query()
            ->whereKey((int) $productId)
            ->where('sku', self::FREIGHT_SKU)
            ->exists();
    }
}
