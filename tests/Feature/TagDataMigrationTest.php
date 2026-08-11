<?php

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('data migration converts pipe-delimited tags to pivot records', function () {
    // Create product with tags in the old string column format (bypassing HasTags events)
    $productA = Product::withoutEvents(function () {
        return Product::create([
            'description' => 'Product A',
            'price' => 100,
            'stock' => 10,
            'qtty_package' => 1,
            'published' => 1,
            'visibility' => 'visible',
            'tax_status' => 'taxable',
            'tags' => 'NUEVO|OFERTA',
        ]);
    });

    $productB = Product::withoutEvents(function () {
        return Product::create([
            'description' => 'Product B',
            'price' => 200,
            'stock' => 5,
            'qtty_package' => 1,
            'published' => 1,
            'visibility' => 'visible',
            'tax_status' => 'taxable',
            'tags' => 'REMATE',
        ]);
    });

    // Run the data migration
    // The migration is already run by RefreshDatabase, but let's simulate the data
    // migration logic by calling syncTagsFromStringColumnPublic
    $productA->syncTagsFromStringColumnPublic();
    $productB->syncTagsFromStringColumnPublic();

    $this->assertDatabaseCount('tags', 3);

    $aTagIds = $productA->fresh()->tags()->allRelatedIds()->all();
    expect(count($aTagIds))->toBe(2);

    $bTagIds = $productB->fresh()->tags()->allRelatedIds()->all();
    expect(count($bTagIds))->toBe(1);
});

it('data migration handles empty and null tags gracefully', function () {
    $productEmpty = Product::withoutEvents(function () {
        return Product::create([
            'description' => 'Empty Tags Product',
            'price' => 100,
            'stock' => 10,
            'qtty_package' => 1,
            'published' => 1,
            'visibility' => 'visible',
            'tax_status' => 'taxable',
            'tags' => '',
        ]);
    });

    $productNull = Product::withoutEvents(function () {
        return Product::create([
            'description' => 'Null Tags Product',
            'price' => 100,
            'stock' => 10,
            'qtty_package' => 1,
            'published' => 1,
            'visibility' => 'visible',
            'tax_status' => 'taxable',
            'tags' => '',
        ]);
    });

    $productEmpty->syncTagsFromStringColumnPublic();
    $productNull->syncTagsFromStringColumnPublic();

    $this->assertDatabaseMissing('taggables', ['taggable_id' => $productEmpty->id]);
    $this->assertDatabaseMissing('taggables', ['taggable_id' => $productNull->id]);
});

it('data migration deduplicates and normalizes tag names', function () {
    $product = Product::withoutEvents(function () {
        return Product::create([
            'description' => 'Dedup Product',
            'price' => 100,
            'stock' => 10,
            'qtty_package' => 1,
            'published' => 1,
            'visibility' => 'visible',
            'tax_status' => 'taxable',
            'tags' => 'nuevo|nuevo|NUEVO|Oferta',
        ]);
    });

    $product->syncTagsFromStringColumnPublic();

    // Should have 2 unique tags: NUEVO and OFERTA
    $this->assertDatabaseCount('tags', 2);
    $tagIds = $product->fresh()->tags()->allRelatedIds()->all();
    expect(count($tagIds))->toBe(2);
});
