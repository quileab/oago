<?php

use App\Enums\OrderStatus;
use App\Enums\Role;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\putJson;

it('calculates bonuses correctly when updating order items via API', function () {
    $admin = User::factory()->create(['role' => Role::ADMIN]);
    $user = User::factory()->create(['role' => Role::CUSTOMER]);

    // Create a product with bonus: 2 + 1 (buy 2, get 1 free)
    $product = Product::create([
        'description' => 'Bonus Product',
        'price' => 100,
        'stock' => 100,
        'bonus_threshold' => 2,
        'bonus_amount' => 1,
        'qtty_package' => 1,
        'published' => 1,
        'model' => 'M1',
        'brand' => 'B1',
        'visibility' => 'visible',
        'tax_status' => 'taxable',
    ]);

    $order = Order::create([
        'user_id' => $user->id,
        'total_price' => 0,
        'status' => OrderStatus::PENDING,
    ]);

    actingAs($admin, 'sanctum');

    // Update with 3 units (should be price of 2 = 200.0)
    $response = putJson("/api/orders/{$order->id}/products", [
        'items' => [
            [
                'product_id' => $product->id,
                'quantity' => 3,
                'price' => 100,
            ],
        ],
    ]);

    $response->assertSuccessful();

    $order->refresh();
    expect((float) $order->total_price)->toBe(200.0);
    expect($order->items)->toHaveCount(1);
    expect($order->items->first()->quantity)->toBe(3);
});

it('uses a transaction when updating order products via API', function () {
    $admin = User::factory()->create(['role' => Role::ADMIN]);
    $user = User::factory()->create(['role' => Role::CUSTOMER]);

    $product = Product::create([
        'description' => 'Existing Product',
        'price' => 100,
        'stock' => 100,
        'qtty_package' => 1,
        'published' => 1,
        'model' => 'M1',
        'brand' => 'B1',
        'visibility' => 'visible',
        'tax_status' => 'taxable',
    ]);

    $order = Order::create([
        'user_id' => $user->id,
        'total_price' => 500,
        'status' => OrderStatus::PENDING,
    ]);

    $order->items()->create([
        'product_id' => $product->id,
        'quantity' => 5,
        'price' => 100,
    ]);

    actingAs($admin, 'sanctum');

    // Attempt update with invalid data (e.g. non-existent product)
    $response = putJson("/api/orders/{$order->id}/products", [
        'items' => [
            [
                'product_id' => 99999, // Should trigger exists validation failure
                'quantity' => 1,
                'price' => 100,
            ],
        ],
    ]);

    $response->assertStatus(400); // Controller returns 400 on validation fail

    // Verify rollback: items should still exist
    $order->refresh();
    expect($order->items)->toHaveCount(1);
    expect($order->items->first()->product_id)->toBe($product->id);
    expect((float) $order->total_price)->toBe(500.0);
});
