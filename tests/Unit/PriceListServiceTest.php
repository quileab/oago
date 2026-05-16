<?php

namespace Tests\Unit;

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

    public function test_it_resolves_base_list_id_for_unit_lists()
    {
        $baseList = ListName::create(['name' => 'General List']);
        $unitList = ListName::create(['name' => 'General List U']);

        $resolvedId = $this->service->resolveBaseListId($unitList->id);

        $this->assertEquals($baseList->id, $resolvedId);
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

    public function test_it_gets_effective_price_from_unit_list()
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

        $price = $this->service->getEffectivePrice($unitList->id, $product->id);

        $this->assertEquals(9.25, $price);
    }

    public function test_it_returns_null_if_price_not_found()
    {
        $list = ListName::create(['name' => 'General List']);

        $price = $this->service->getEffectivePrice($list->id, 9999);

        $this->assertNull($price);
    }
}
