<?php

use App\Enums\Role;
use App\Models\Product;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('accepts tags as pipe-delimited string via API', function () {
    $admin = User::factory()->create(['role' => Role::ADMIN]);

    $response = $this->actingAs($admin, 'sanctum')
        ->postJson('/api/products', [
            'description' => 'API Tag Product',
            'sku' => 'API-TAG-1',
            'published' => true,
            'featured' => false,
            'visibility' => 'visible',
            'tax_status' => 'taxable',
            'in_stock' => true,
            'stock' => 10,
            'allow_reservation' => false,
            'qtty_package' => 1,
            'qtty_unit' => 1,
            'tags' => 'nuevo|Oferta',
        ]);

    $response->assertStatus(201);

    $product = Product::where('sku', 'API-TAG-1')->first();
    expect($product->tags_array)->toBe(['NUEVO', 'OFERTA']);

    $this->assertDatabaseCount('tags', 2);
    $this->assertDatabaseHas('taggables', ['taggable_id' => $product->id]);
});

it('accepts tags as comma-delimited string via API', function () {
    $admin = User::factory()->create(['role' => Role::ADMIN]);

    $response = $this->actingAs($admin, 'sanctum')
        ->postJson('/api/products', [
            'description' => 'API Comma Tags',
            'sku' => 'API-COMMA-1',
            'published' => true,
            'featured' => false,
            'visibility' => 'visible',
            'tax_status' => 'taxable',
            'in_stock' => true,
            'stock' => 10,
            'allow_reservation' => false,
            'qtty_package' => 1,
            'qtty_unit' => 1,
            'tags' => 'nuevo,oferta',
        ]);

    $response->assertStatus(201);

    $product = Product::where('sku', 'API-COMMA-1')->first();
    expect($product->tags_array)->toBe(['NUEVO', 'OFERTA']);
});

it('accepts tags as array of names via API', function () {
    $admin = User::factory()->create(['role' => Role::ADMIN]);

    $response = $this->actingAs($admin, 'sanctum')
        ->postJson('/api/products', [
            'description' => 'API Array Tags',
            'sku' => 'API-ARR-1',
            'published' => true,
            'featured' => false,
            'visibility' => 'visible',
            'tax_status' => 'taxable',
            'in_stock' => true,
            'stock' => 10,
            'allow_reservation' => false,
            'qtty_package' => 1,
            'qtty_unit' => 1,
            'tags' => ['Nuevo', 'Oferta'],
        ]);

    $response->assertStatus(201);

    $product = Product::where('sku', 'API-ARR-1')->first();
    expect($product->tags_array)->toBe(['NUEVO', 'OFERTA']);
});

it('accepts tags as array of IDs via API', function () {
    $admin = User::factory()->create(['role' => Role::ADMIN]);

    $tag1 = Tag::fromName('nuevo');
    $tag2 = Tag::fromName('oferta');

    $response = $this->actingAs($admin, 'sanctum')
        ->postJson('/api/products', [
            'description' => 'API ID Tags',
            'sku' => 'API-ID-1',
            'published' => true,
            'featured' => false,
            'visibility' => 'visible',
            'tax_status' => 'taxable',
            'in_stock' => true,
            'stock' => 10,
            'allow_reservation' => false,
            'qtty_package' => 1,
            'qtty_unit' => 1,
            'tags' => [$tag1->id, $tag2->id],
        ]);

    $response->assertStatus(201);

    $product = Product::where('sku', 'API-ID-1')->first();
    expect($product->tags_array)->toBe(['NUEVO', 'OFERTA']);
});

it('updates product tags via API', function () {
    $admin = User::factory()->create(['role' => Role::ADMIN]);

    $response = $this->actingAs($admin, 'sanctum')
        ->postJson('/api/products', [
            'description' => 'API Update Tags',
            'sku' => 'API-UPD-1',
            'published' => true,
            'featured' => false,
            'visibility' => 'visible',
            'tax_status' => 'taxable',
            'in_stock' => true,
            'stock' => 10,
            'allow_reservation' => false,
            'qtty_package' => 1,
            'qtty_unit' => 1,
            'tags' => 'nuevo',
        ]);

    $response->assertStatus(201);
    $product = Product::where('sku', 'API-UPD-1')->first();
    expect($product->tags_array)->toBe(['NUEVO']);

    $response = $this->actingAs($admin, 'sanctum')
        ->postJson('/api/products', [
            'id' => $product->id,
            'description' => 'API Update Tags',
            'sku' => 'API-UPD-1',
            'published' => true,
            'featured' => false,
            'visibility' => 'visible',
            'tax_status' => 'taxable',
            'in_stock' => true,
            'stock' => 10,
            'allow_reservation' => false,
            'qtty_package' => 1,
            'qtty_unit' => 1,
            'tags' => 'remate',
        ]);

    $response->assertStatus(200);
    $product = $product->fresh();
    expect($product->tags_array)->toBe(['REMATE']);
});

it('handles empty tags via API', function () {
    $admin = User::factory()->create(['role' => Role::ADMIN]);

    $response = $this->actingAs($admin, 'sanctum')
        ->postJson('/api/products', [
            'description' => 'API No Tags',
            'sku' => 'API-NOTAG-1',
            'published' => true,
            'featured' => false,
            'visibility' => 'visible',
            'tax_status' => 'taxable',
            'in_stock' => true,
            'stock' => 10,
            'allow_reservation' => false,
            'qtty_package' => 1,
            'qtty_unit' => 1,
            'tags' => '',
        ]);

    $response->assertStatus(201);

    $product = Product::where('sku', 'API-NOTAG-1')->first();
    expect($product->tags_array)->toBe([]);
});
