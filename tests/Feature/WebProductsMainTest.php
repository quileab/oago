<?php

use App\Models\Product;
use App\Services\ProductSearchService;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Volt\Volt;

use function Pest\Laravel\mock;

it('renders products with a stable wire:key', function () {
    // Mock ProductSearchService
    $mock = mock(ProductSearchService::class);

    $product = new Product;
    $product->forceFill([
        'id' => 999,
        'name' => 'Test Product',
        'description_html' => 'Test Description',
        'qtty_package' => 1,
        'slug' => 'test-product',
        'featured' => false,
        'img_small_path' => 'test.jpg',
        'image_url' => 'test.jpg',
        'stock' => 10,
        'price' => 100,
        'brand' => 'Test Brand',
        'model' => 'Test Model',
        'qtty' => 1,
        'tags' => '',
        'description' => 'Test Description',
    ]);

    $paginator = new LengthAwarePaginator([$product], 1, 30);

    $mock->shouldReceive('searchProducts')
        ->andReturn($paginator);

    app()->instance(ProductSearchService::class, $mock);

    // Verify the stable key is present in the Livewire snapshot
    $test = Volt::test('webproductsmain');
    $test->assertSee('wire:key="prod-card-999"', escape: false)
        ->assertDontSee('Str::random'); // Just to be sure it's not somehow there
});
