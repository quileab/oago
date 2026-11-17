<?php

namespace App\View\Components;

use App\Helpers\SettingsHelper;
use App\Models\Product;
use App\Models\Tag;
use App\Services\PriceListService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Session;
use Illuminate\View\Component;
use Illuminate\View\View;

class ProductCard extends Component
{
    /**
     * Normalized card view data.
     *
     * @var array<string, mixed>
     */
    public array $cardData;

    public function __construct(mixed $product)
    {
        $this->cardData = self::resolveViewData($product);
    }

    public function render(): View
    {
        return view('livewire.web-product-card', $this->cardData);
    }

    /**
     * Resolve display, stock policy, and cart metadata for a product card without Livewire overhead.
     *
     * @return array<string, mixed>
     */
    public static function resolveViewData(mixed $product): array
    {
        $raw = self::extractRawAttributes($product);
        $productObj = (object) $raw;

        if (! isset($productObj->id)) {
            $productObj->id = 0;
        }

        if (! isset($productObj->tags_array) || ! is_array($productObj->tags_array)) {
            $productObj->tags_array = [];
        }

        if (! isset($productObj->commercial_tags_array) || ! is_array($productObj->commercial_tags_array)) {
            $productObj->commercial_tags_array = Tag::filterCommercialNames($productObj->tags_array);
        }

        if (! isset($productObj->division) || ! is_array($productObj->division)) {
            $productObj->division = Product::resolveDivisionFromRaw($productObj);
        }

        $productObj->description_html = str_replace('\n', '', (string) ($productObj->description_html ?? ''));

        // Optional fields the card views read directly. A freshly created model only carries
        // the attributes that were assigned, so DB defaults (featured, qtty_unit, ...) are
        // absent from toArray() until the model is refreshed.
        $productObj->featured = (bool) ($productObj->featured ?? false);
        $productObj->brand = (string) ($productObj->brand ?? '');
        $productObj->model = (string) ($productObj->model ?? '');
        $productObj->description = (string) ($productObj->description ?? '');
        $productObj->image_url = $productObj->image_url ?? null;
        $productObj->price = (float) ($productObj->price ?? 0);
        $productObj->qtty_unit = max(1, (int) ($productObj->qtty_unit ?? 1));
        $productObj->qtty_package = max(1, (int) ($productObj->qtty_package ?? 1));

        [$displayPrice, $displayOffer] = self::resolvePrices($raw, $product instanceof Model ? $product : null);

        $stockPolicy = (new Product)->forceFill([
            'stock' => (int) ($raw['stock'] ?? 0),
            'in_stock' => (bool) ($raw['in_stock'] ?? true),
            'deduct_stock' => (bool) ($raw['deduct_stock'] ?? true),
            'allow_reservation' => (bool) ($raw['allow_reservation'] ?? false),
        ]);

        $currentUser = current_user();
        $isGuest = $currentUser === null;
        $showPricesToGuests = (bool) SettingsHelper::settings('show_prices_to_guests', false);
        $package = max(1, (int) ($raw['qtty_package'] ?? 1));

        return [
            'product' => $productObj,
            'display_price' => $displayPrice,
            'display_offer' => $displayOffer,
            'cart' => Session::get('cart', []),
            'isGuest' => $isGuest,
            'showPrices' => ! $isGuest || $showPricesToGuests,
            'guestMessage' => $showPricesToGuests ? 'Regístrese para comprar' : 'Regístrese para ver precios',
            'isOutOfStock' => $stockPolicy->isOutOfStock(),
            'hasAvailableStock' => $stockPolicy->hasAvailableStock(),
            'stockBadge' => $stockPolicy->stockBadge(),
            'cartPayload' => [
                'id' => (int) ($raw['id'] ?? 0),
                'description' => (string) ($raw['description'] ?? ''),
                'user_price' => $displayOffer > 0 ? $displayOffer : $displayPrice,
                'qtty_package' => $package,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function extractRawAttributes(mixed $product): array
    {
        if ($product instanceof Model) {
            $raw = $product->toArray();
            $raw['tags_array'] = $product->tags_array;
            $raw['commercial_tags_array'] = $product->commercial_tags_array ?? $product->tags_array;
            $raw['division'] = method_exists($product, 'resolveDivision')
                ? $product->resolveDivision()
                : Product::resolveDivisionFromRaw($product);
            $attributes = $product->getAttributes();

            if (isset($product->description_html)) {
                $raw['description_html'] = $product->description_html;
            }
            if (array_key_exists('base_price', $attributes)) {
                $raw['base_price'] = $attributes['base_price'];
            }
            if (array_key_exists('promo_price', $attributes)) {
                $raw['promo_price'] = $attributes['promo_price'];
            }

            return $raw;
        }

        return (array) $product;
    }

    /**
     * @param  array<string, mixed>  $raw
     * @return array{0: float, 1: float}
     */
    private static function resolvePrices(array $raw, ?Model $productModel): array
    {
        $isPreHydrated = array_key_exists('base_price', $raw)
            && array_key_exists('promo_price', $raw);

        if ($isPreHydrated) {
            $basePrice = (float) ($raw['base_price'] ?? 0);
            $promoPrice = $raw['promo_price'];
        } else {
            $priceService = app(PriceListService::class);
            $showPricesToGuests = (bool) SettingsHelper::settings('show_prices_to_guests', false);
            $defaultGuestListId = $showPricesToGuests ? (int) SettingsHelper::settings('alt_user_default_price', 0) : 0;
            $currentUser = current_user();
            $listId = $currentUser
                ? (int) ($priceService->resolveListId($currentUser) ?? 0)
                : $defaultGuestListId;

            $productId = $productModel ? (int) $productModel->id : (int) ($raw['id'] ?? 0);

            $basePrice = (float) (
                $raw['base_price']
                ?? ($productId && $listId ? $priceService->getEffectivePrice($listId, $productId, true) : null)
                ?? ($raw['price'] ?? 0)
            );

            if ($basePrice <= 0 && $showPricesToGuests && $defaultGuestListId && $productId) {
                $fallbackPrice = $priceService->getEffectivePrice($defaultGuestListId, $productId, true);
                if ($fallbackPrice && $fallbackPrice > 0) {
                    $basePrice = (float) $fallbackPrice;
                }
            }

            $promoPrice = array_key_exists('promo_price', $raw)
                ? $raw['promo_price']
                : ($productId && $listId ? $priceService->getEffectivePrice($listId, $productId, false) : null);
        }

        if ($promoPrice !== null && (float) $promoPrice < $basePrice) {
            return [$basePrice, (float) $promoPrice];
        }

        return [$basePrice, 0.0];
    }
}
