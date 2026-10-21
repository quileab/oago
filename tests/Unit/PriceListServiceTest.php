<?php

namespace Tests\Unit;

use App\Helpers\SettingsHelper;
use App\Models\ListName;
use App\Models\ListPrice;
use App\Models\Product;
use App\Services\PriceListService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PriceListServiceTest extends TestCase
{
    use RefreshDatabase;

    private PriceListService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PriceListService;
    }

    public function test_it_returns_the_same_list_id_since_unit_lists_no_longer_exist()
    {
        $baseList = ListName::create(['name' => 'General List']);
        $unitList = ListName::create(['name' => 'General List U']);

        $resolvedId = $this->service->resolveBaseListId($unitList->id);

        $this->assertEquals($unitList->id, $resolvedId);
        $this->assertNotEquals($baseList->id, $resolvedId);
    }

    public function test_it_gets_effective_price_from_regular_list()
    {
        $list = ListName::create(['name' => 'General List']);
        $product = Product::create([
            'description' => 'Test Product',
            'price' => 100,
            'qtty_package' => 1,
            'visibility' => 'visible',
            'tax_status' => 'taxable',
            'published' => true,
            'model' => 'M1',
            'brand' => 'B1',
        ]);

        ListPrice::create([
            'list_id' => $list->id,
            'product_id' => $product->id,
            'price' => 85.50,
            'unit_price' => 10.00,
        ]);

        $price = $this->service->getEffectivePrice($list->id, $product->id);

        $this->assertEquals(85.50, $price);
    }

    public function test_it_does_not_mix_prices_between_lists()
    {
        $baseList = ListName::create(['name' => 'General List']);
        $unitList = ListName::create(['name' => 'General List U']);
        $product = Product::create([
            'description' => 'Test Product',
            'price' => 100,
            'qtty_package' => 1,
            'visibility' => 'visible',
            'tax_status' => 'taxable',
            'published' => true,
            'model' => 'M1',
            'brand' => 'B1',
        ]);

        ListPrice::create([
            'list_id' => $baseList->id,
            'product_id' => $product->id,
            'price' => 85.50,
            'unit_price' => 9.25,
        ]);

        // El precio de una lista no se resuelve contra otra: sin fila propia, cae al precio base del producto.
        $price = $this->service->getEffectivePrice($unitList->id, $product->id);

        $this->assertNull($price);
    }

    public function test_it_returns_null_if_price_not_found()
    {
        $list = ListName::create(['name' => 'General List']);

        $price = $this->service->getEffectivePrice($list->id, 9999);

        $this->assertNull($price);
    }

    private function makeProduct(float $price): Product
    {
        return Product::create([
            'description' => 'Sellable Product',
            'price' => $price,
            'qtty_package' => 1,
            'visibility' => 'visible',
            'tax_status' => 'taxable',
            'published' => true,
            'model' => 'M1',
            'brand' => 'B1',
        ]);
    }

    public function test_it_marks_a_product_without_any_price_as_not_sellable()
    {
        $product = $this->makeProduct(0);

        $this->assertFalse($this->service->isSellable(null, $product));
    }

    public function test_it_marks_a_product_with_base_price_but_without_list_price_as_sellable()
    {
        $product = $this->makeProduct(100);

        $this->assertTrue($this->service->isSellable(null, $product));
    }

    public function test_it_marks_a_product_with_list_price_but_zero_base_price_as_sellable()
    {
        $list = ListName::create(['name' => 'General List']);
        $product = $this->makeProduct(0);

        ListPrice::create([
            'list_id' => $list->id,
            'product_id' => $product->id,
            'price' => 85.50,
            'unit_price' => 9.25,
        ]);

        $this->assertTrue($this->service->isSellable($list->id, $product));
    }

    public function test_a_zero_promo_price_means_no_offer_and_does_not_block_sales()
    {
        $list = ListName::create(['name' => 'General List']);
        $product = $this->makeProduct(100);

        ListPrice::create([
            'list_id' => $list->id,
            'product_id' => $product->id,
            'price' => 90,
            'unit_price' => 9,
        ]);

        $promoList = ListName::create(['name' => 'Promo List']);
        SettingsHelper::update_setting('promo_list_id', $promoList->id);

        // Promo en 0 = sin oferta: se usa el precio de la lista (90).
        ListPrice::create([
            'list_id' => $promoList->id,
            'product_id' => $product->id,
            'price' => 0,
            'unit_price' => 0,
        ]);

        $this->assertTrue($this->service->isSellable($list->id, $product));
        $this->assertEquals(90.0, $this->service->getEffectivePrice($list->id, $product->id));
    }
}
