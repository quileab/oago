<?php

use App\Enums\OrderStatus;
use App\Enums\Role;
use App\Models\Order;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\putJson;

it('validates order status using OrderStatus Enum', function () {
    $admin = User::factory()->create(['role' => Role::ADMIN]);
    $user = User::factory()->create(['role' => Role::CUSTOMER]);
    $order = Order::create([
        'user_id' => $user->id,
        'total_price' => 100,
        'status' => OrderStatus::PENDING,
    ]);

    actingAs($admin, 'sanctum');

    // 1. Valid status
    putJson("/api/orders/{$order->id}/status", ['status' => 'completed'])
        ->assertSuccessful();

    $order->refresh();
    expect($order->status)->toBe(OrderStatus::COMPLETED);

    // 2. Invalid status
    putJson("/api/orders/{$order->id}/status", ['status' => 'invalid_status'])
        ->assertStatus(422); // Laravel default for API validation failure
});

it('casts order status to OrderStatus Enum', function () {
    $user = User::factory()->create(['role' => Role::CUSTOMER]);
    $order = Order::create([
        'user_id' => $user->id,
        'total_price' => 100,
        'status' => 'on-hold',
    ]);

    expect($order->status)->toBeInstanceOf(OrderStatus::class)
        ->and($order->status)->toBe(OrderStatus::ON_HOLD);
});

it('returns translated labels via orderStates', function () {
    expect(Order::orderStates(OrderStatus::PENDING))->toBe('Pendiente')
        ->and(Order::orderStates('pending'))->toBe('Pendiente');
});
