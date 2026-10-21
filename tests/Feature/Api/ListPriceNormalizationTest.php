<?php

use App\Enums\Role;
use App\Models\ListName;
use App\Models\ListPrice;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('prices are stored per list without legacy "U" normalization', function () {
    // 0. Setup: Create admin, product and lists
    $admin = User::factory()->create(['role' => Role::ADMIN]);
    $product = Product::create([
        'description' => 'Test Product',
        'visibility' => 'visible',
        'tax_status' => 'taxable',
    ]);

    $baseList = ListName::create(['name' => 'Lista A  $']);
    $unitList = ListName::create(['name' => 'Lista A  $ U']);

    // 1. Post to base list (BULK PRICE)
    $response1 = $this->actingAs($admin, 'sanctum')
        ->postJson('/api/list-prices', [
            'product_id' => $product->id,
            'list_id' => $baseList->id,
            'price' => 100.00,
        ]);

    $response1->assertSuccessful();

    $this->assertDatabaseHas('list_prices', [
        'product_id' => $product->id,
        'list_id' => $baseList->id,
        'price' => 100.00,
        'unit_price' => 0.00,
    ]);

    // 2. Post to "$ U" list: se guarda en su propia lista, sin tocar la base
    $response2 = $this->actingAs($admin, 'sanctum')
        ->postJson('/api/list-prices', [
            'product_id' => $product->id,
            'list_id' => $unitList->id,
            'price' => 12.50,
        ]);

    $response2->assertSuccessful();

    $this->assertDatabaseHas('list_prices', [
        'product_id' => $product->id,
        'list_id' => $unitList->id,
        'price' => 12.50,
    ]);

    // La lista base conserva sus valores originales
    $this->assertDatabaseHas('list_prices', [
        'product_id' => $product->id,
        'list_id' => $baseList->id,
        'price' => 100.00,
        'unit_price' => 0.00,
    ]);
});

test('the normalization command works correctly', function () {
    $product = Product::create([
        'description' => 'Test Product',
        'visibility' => 'visible',
        'tax_status' => 'taxable',
    ]);

    $baseList = ListName::create(['name' => 'Lista B  $']);
    $unitList = ListName::create(['name' => 'Lista B  $ U']);

    // Create a price in base list
    ListPrice::create([
        'product_id' => $product->id,
        'list_id' => $baseList->id,
        'price' => 100.00,
    ]);

    // Create a price in unit list
    ListPrice::create([
        'product_id' => $product->id,
        'list_id' => $unitList->id,
        'price' => 12.50,
    ]);

    // Run the command
    $this->artisan('app:normalize-price-lists')
        ->expectsOutput('Normalizando: Lista B  $ U -> Lista B  $')
        ->assertSuccessful();

    // Verify database
    $this->assertDatabaseHas('list_prices', [
        'product_id' => $product->id,
        'list_id' => $baseList->id,
        'price' => 100.00,
        'unit_price' => 12.50,
    ]);

    $this->assertDatabaseHas('list_names', ['id' => $unitList->id]); // Command no longer deletes U lists
    $this->assertDatabaseMissing('list_prices', ['list_id' => $unitList->id]);
});
