<?php

use App\Enums\OrderStatus;
use App\Enums\Role;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\putJson;

uses(RefreshDatabase::class);

function orderWithItem(User $owner, OrderStatus $status): array
{
    $product = makeProduct(['description' => 'En pedido', 'price' => 50, 'stock' => 100]);
    $order = Order::create([
        'user_id' => $owner->id,
        'total_price' => 100,
        'status' => $status,
        'payment_method' => 'Contado / Efectivo',
    ]);
    $order->items()->create(['product_id' => $product->id, 'quantity' => 2, 'price' => 50]);

    return [$order, $product];
}

it('rejects product updates on finished orders via API', function () {
    $admin = User::factory()->create(['role' => Role::ADMIN]);
    $owner = User::factory()->create(['role' => Role::CUSTOMER]);
    [$order, $product] = orderWithItem($owner, OrderStatus::COMPLETED);

    actingAs($admin, 'sanctum');

    putJson("/api/orders/{$order->id}/products", [
        'items' => [
            ['product_id' => $product->id, 'quantity' => 1, 'price' => 50],
        ],
    ])->assertStatus(422);

    expect($order->items()->count())->toBe(1);
    expect((float) $order->fresh()->total_price)->toBe(100.0);
});

it('rolls back items when a product became unsellable via API', function () {
    $admin = User::factory()->create(['role' => Role::ADMIN]);
    $owner = User::factory()->create(['role' => Role::CUSTOMER]);
    [$order, $product] = orderWithItem($owner, OrderStatus::PENDING);

    // El sistema externo dejó el producto sin precio.
    $product->update(['price' => 0]);

    actingAs($admin, 'sanctum');

    putJson("/api/orders/{$order->id}/products", [
        'items' => [
            ['product_id' => $product->id, 'quantity' => 1, 'price' => 50],
        ],
    ])->assertStatus(422);

    // Los ítems originales sobreviven (rollback de la transacción).
    $order->refresh();
    expect(OrderItem::where('order_id', $order->id)->get(['product_id', 'price']))->toHaveCount(1);
    expect((float) $order->total_price)->toBe(100.0);
});

it('rejects an invalid status transition via API', function () {
    $admin = User::factory()->create(['role' => Role::ADMIN]);
    $owner = User::factory()->create(['role' => Role::CUSTOMER]);
    [$order] = orderWithItem($owner, OrderStatus::CANCELLED);

    actingAs($admin, 'sanctum');

    putJson("/api/orders/{$order->id}/status", ['status' => 'completed'])
        ->assertStatus(422);

    expect($order->fresh()->status)->toBe(OrderStatus::CANCELLED);
});

it('accepts a valid status transition via API', function () {
    $admin = User::factory()->create(['role' => Role::ADMIN]);
    $owner = User::factory()->create(['role' => Role::CUSTOMER]);
    [$order] = orderWithItem($owner, OrderStatus::PENDING);

    actingAs($admin, 'sanctum');

    putJson("/api/orders/{$order->id}/status", ['status' => 'processing'])
        ->assertSuccessful();

    expect($order->fresh()->status)->toBe(OrderStatus::PROCESSING);
});
