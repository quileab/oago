<?php

use App\Enums\Role;
use App\Models\ListName;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('legacy ingestion flow: products and prices are correctly handled', function () {
    // 1. Setup Admin
    $admin = User::factory()->create(['role' => Role::ADMIN]);

    // 2. Setup Lists as they exist in oagostini
    $baseList = ListName::create(['id' => 1, 'name' => 'Lista A  $']);
    $unitList = ListName::create(['id' => 2, 'name' => 'Lista A  $ U']);

    // 3. Create Product via API (as the legacy would do)
    $productData = [
        'id' => 10059,
        'description' => 'Producto de Prueba Legacy',
        'sku' => 'LEG-10059',
        'published' => true,
        'featured' => false,
        'visibility' => 'visible',
        'tax_status' => 'taxable',
        'in_stock' => true,
        'stock' => 10,
        'allow_reservation' => false,
        'qtty_package' => 12,
        'qtty_unit' => 1,
    ];

    $response = $this->actingAs($admin, 'sanctum')
        ->postJson('/api/products', $productData);

    $response->assertStatus(201);
    $this->assertDatabaseHas('products', ['id' => 10059, 'sku' => 'LEG-10059']);

    // 4. Update Price - BULK (Lista A $)
    $response = $this->actingAs($admin, 'sanctum')
        ->postJson('/api/list-prices', [
            'product_id' => 10059,
            'list_id' => 1, // Base list
            'price' => 12000.00,
        ]);

    $response->assertSuccessful();
    $this->assertDatabaseHas('list_prices', [
        'product_id' => 10059,
        'list_id' => 1,
        'price' => 12000.00,
        'unit_price' => 0.00,
    ]);

    // 5. Update Price - UNIT (Lista A $ U)
    // Se guarda en su propia lista: ya no hay normalización legacy hacia la base.
    $response = $this->actingAs($admin, 'sanctum')
        ->postJson('/api/list-prices', [
            'product_id' => 10059,
            'list_id' => 2, // Unit list
            'price' => 1150.00,
        ]);

    $response->assertSuccessful();

    $this->assertDatabaseHas('list_prices', [
        'product_id' => 10059,
        'list_id' => 2,
        'price' => 1150.00,
    ]);

    // La lista base conserva el precio de bulto
    $this->assertDatabaseHas('list_prices', [
        'product_id' => 10059,
        'list_id' => 1,
        'price' => 12000.00,
        'unit_price' => 0.00,
    ]);

    // 6. Update Product again (ensure update works)
    $productData['description'] = 'Producto Actualizado';
    $response = $this->actingAs($admin, 'sanctum')
        ->postJson('/api/products', $productData);

    $response->assertStatus(200); // Controller returns 200 on update via store()
    $this->assertDatabaseHas('products', ['id' => 10059, 'description' => 'Producto Actualizado']);

    // 7. Verify GET show returns the price of the requested list
    $response = $this->actingAs($admin, 'sanctum')
        ->getJson('/api/list-prices/10059/2'); // Asking for unit list

    $response->assertStatus(200)
        ->assertJson([
            'list_id' => 2,
            'price' => 1150.00,
        ]);
});
