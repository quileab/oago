<?php

use App\Models\Product;
use App\Models\Tag;
use App\Services\ProductSearchService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('attaches tags via syncTagsBySlug', function () {
    $product = makeProduct();

    $product->syncTagsBySlug(['nuevo', 'oferta']);

    $tagIds = $product->tags()->allRelatedIds()->all();
    expect($tagIds)->toHaveCount(2);
});

it('attaches tags via syncTagsById and creates missing ones by name', function () {
    $nuevo = Tag::fromName('NUEVO');

    $product = makeProduct();
    $product->syncTagsBySlug(['nuevo']);
    $product->syncTagsById([$nuevo->id, Tag::fromName('REMATE')->id]);

    expect($product->tags()->allRelatedIds()->all())->toHaveCount(2);
});

it('clears the pivot when syncing an empty set of ids', function () {
    $product = makeProduct();
    $product->syncTagsBySlug(['nuevo', 'oferta']);

    $product->syncTagsById([]);

    expect($product->tags()->allRelatedIds()->all())->toBeEmpty();
});

it('ignores ids that do not exist when syncing by id', function () {
    $product = makeProduct();

    $product->syncTagsById([999999, 0, -3]);

    expect($product->tags()->allRelatedIds()->all())->toBeEmpty();
});

it('does not touch the pivot when an unrelated product attribute is saved', function () {
    $product = attachTags(makeProduct(), ['CANASTAS NAVIDEÑAS']);

    // Regression guard: any save must leave the tags alone.
    $product->update(['price' => 250]);
    $product->update(['stock' => 99]);
    $product->touch();
    $product->refresh();

    expect($product->tags_array)->toBe(['CANASTAS NAVIDEÑAS']);
});

it('does not create duplicate pivot rows when syncing the same tags twice', function () {
    $product = makeProduct();

    $product->syncTagsBySlug(['nuevo']);
    $product->syncTagsBySlug(['nuevo']);

    expect($product->tags()->allRelatedIds()->all())->toHaveCount(1);
});

it('exposes tag names through the tags_array and tags_string accessors', function () {
    $product = makeProduct();
    $product->syncTagsBySlug(['nuevo']);

    $fresh = $product->fresh();

    expect($fresh->tags_array)->toBe(['NUEVO']);
    expect($fresh->tags_string)->toBe('NUEVO');
});

it('returns an empty tags array when the product has no tags', function () {
    expect(makeProduct()->fresh()->tags_array)->toBe([]);
});

it('detaches tags when the product is deleted', function () {
    $product = makeProduct();
    $product->syncTagsBySlug(['nuevo']);

    $product->delete();

    $this->assertDatabaseMissing('taggables', [
        'taggable_id' => $product->id,
        'taggable_type' => Product::class,
    ]);
});

it('filters products by tag slug via ProductSearchService', function () {
    $productA = makeProduct(['description' => 'Product A']);
    $productA->syncTagsBySlug(['nuevo']);

    makeProduct(['description' => 'Product B'])->syncTagsBySlug(['oferta']);

    $results = (new ProductSearchService)->searchProducts(['tag' => 'nuevo']);

    expect($results->items())->toHaveCount(1);
    expect($results->items()[0]->id)->toBe($productA->id);
});

it('matches products by tag name in the free text search', function () {
    $canasta = attachTags(makeProduct(['description' => 'CANASTA "3" (20 articulos)']), ['CANASTAS NAVIDEÑAS']);

    makeProduct(['description' => 'Producto sin etiqueta']);

    $results = (new ProductSearchService)->searchProducts(['search' => 'NAVIDEÑAS']);

    expect($results->items())->toHaveCount(1);
    expect($results->items()[0]->id)->toBe($canasta->id);
});
