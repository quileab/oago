<?php

use App\Models\Product;
use App\Services\ProductSearchService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('includes products with null brand, model, or category in general search', function () {
    $product = Product::create([
        'description' => 'Test NULL Product',
        'price' => 100,
        'stock' => 10,
        'qtty_package' => 1,
        'published' => 1,
        'model' => null,
        'brand' => null,
        'category' => null,
        'product_type' => null,
        'visibility' => 'visible',
        'tax_status' => 'taxable',
    ]);

    $service = new ProductSearchService;
    $results = $service->searchProducts([]);

    expect($results->items())->toHaveCount(1);
    expect($results->items()[0]->id)->toBe($product->id);
});

it('finds products with null fields when matching description via search query', function () {
    $product = Product::create([
        'description' => 'UniqueKeywordProduct',
        'price' => 100,
        'stock' => 10,
        'qtty_package' => 1,
        'published' => 1,
        'model' => null,
        'brand' => null,
        'category' => null,
        'product_type' => null,
        'visibility' => 'visible',
        'tax_status' => 'taxable',
    ]);

    $service = new ProductSearchService;
    $results = $service->searchProducts(['search' => 'UniqueKeywordProduct']);

    expect($results->items())->toHaveCount(1);
    expect($results->items()[0]->id)->toBe($product->id);
});

it('casts null brand, model, and category to empty strings automatically', function () {
    $product = Product::create([
        'description' => 'Cast Test Product',
        'price' => 100,
        'stock' => 10,
        'qtty_package' => 1,
        'published' => 1,
        'model' => null,
        'brand' => null,
        'category' => null,
        'product_type' => null,
        'visibility' => 'visible',
        'tax_status' => 'taxable',
    ]);

    expect($product->brand)->toBe('');
    expect($product->model)->toBe('');
    expect($product->category)->toBe('');

    $freshProduct = Product::find($product->id);
    expect($freshProduct->brand)->toBe('');
    expect($freshProduct->model)->toBe('');
    expect($freshProduct->category)->toBe('');
});
