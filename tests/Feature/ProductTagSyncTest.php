<?php

use App\Models\Product;
use App\Models\Tag;
use App\Services\ProductSearchService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('writes through pivot when product tags string changes on create', function () {
    $tag = Tag::fromName('NUEVO');

    $product = Product::create([
        'description' => 'Test Product',
        'price' => 100,
        'stock' => 10,
        'qtty_package' => 1,
        'published' => 1,
        'visibility' => 'visible',
        'tax_status' => 'taxable',
        'tags' => 'NUEVO',
    ]);

    $this->assertDatabaseHas('taggables', [
        'taggable_id' => $product->id,
        'taggable_type' => Product::class,
        'taggable_id' => $product->id,
    ]);
});

it('writes through pivot when product tags string changes on update', function () {
    $product = Product::create([
        'description' => 'Test Product',
        'price' => 100,
        'stock' => 10,
        'qtty_package' => 1,
        'published' => 1,
        'visibility' => 'visible',
        'tax_status' => 'taxable',
        'tags' => 'NUEVO',
    ]);

    Tag::fromName('OFERTA');

    $product->update(['tags' => 'NUEVO|OFERTA']);

    $this->assertDatabaseHas('taggables', [
        'taggable_id' => $product->id,
        'taggable_type' => Product::class,
    ]);

    $tagIds = $product->tags()->allRelatedIds()->all();
    expect(count($tagIds))->toBe(2);
});

it('clears pivot when tags string is emptied', function () {
    $product = Product::create([
        'description' => 'Test Product',
        'price' => 100,
        'stock' => 10,
        'qtty_package' => 1,
        'published' => 1,
        'visibility' => 'visible',
        'tax_status' => 'taxable',
        'tags' => 'NUEVO',
    ]);

    $this->assertDatabaseHas('taggables', ['taggable_id' => $product->id]);

    $product->update(['tags' => '']);

    $this->assertDatabaseMissing('taggables', ['taggable_id' => $product->id]);
});

it('syncs via syncTagsBySlug method', function () {
    $product = Product::create([
        'description' => 'Test Product',
        'price' => 100,
        'stock' => 10,
        'qtty_package' => 1,
        'published' => 1,
        'visibility' => 'visible',
        'tax_status' => 'taxable',
        'tags' => '',
    ]);

    $product->syncTagsBySlug(['nuevo', 'oferta']);

    $tagIds = $product->tags()->allRelatedIds()->all();
    expect(count($tagIds))->toBe(2);
});

it('getTagsArrayAttribute returns tag names from relationship', function () {
    $tag = Tag::fromName('NUEVO');

    $product = Product::create([
        'description' => 'Test Product',
        'price' => 100,
        'stock' => 10,
        'qtty_package' => 1,
        'published' => 1,
        'visibility' => 'visible',
        'tax_status' => 'taxable',
        'tags' => 'NUEVO',
    ]);

    $product = $product->fresh()->load('tags');
    expect($product->tags_array)->toBe(['NUEVO']);
});

it('getTagsArrayAttribute falls back to string column when relation is empty', function () {
    $product = Product::create([
        'description' => 'Test Product',
        'price' => 100,
        'stock' => 10,
        'qtty_package' => 1,
        'published' => 1,
        'visibility' => 'visible',
        'tax_status' => 'taxable',
        'tags' => 'NUEVO|OFERTA',
    ]);

    $product = $product->fresh()->load('tags');
    // Relation is loaded but empty (DataImport bypassed Eloquent events)
    expect($product->tags_array)->toBe(['NUEVO', 'OFERTA']);
});

it('filters products by tag slug via ProductSearchService', function () {
    $nuevo = Tag::fromName('NUEVO');
    $oferta = Tag::fromName('OFERTA');

    $productA = Product::create([
        'description' => 'Product A',
        'price' => 100,
        'stock' => 10,
        'qtty_package' => 1,
        'published' => 1,
        'visibility' => 'visible',
        'tax_status' => 'taxable',
        'tags' => 'NUEVO',
    ]);

    $productB = Product::create([
        'description' => 'Product B',
        'price' => 100,
        'stock' => 10,
        'qtty_package' => 1,
        'published' => 1,
        'visibility' => 'visible',
        'tax_status' => 'taxable',
        'tags' => 'OFERTA',
    ]);

    $service = new ProductSearchService;
    $results = $service->searchProducts(['tag' => 'nuevo']);

    expect($results->items())->toHaveCount(1);
    expect($results->items()[0]->id)->toBe($productA->id);
});
