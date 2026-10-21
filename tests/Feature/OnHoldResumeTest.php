<?php

use App\Enums\OrderStatus;
use App\Enums\Role;
use App\Mail\OrderMail;
use App\Models\Order;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;

uses(RefreshDatabase::class);

it('resume a held order into pending and sends a single confirmation email', function () {
    Mail::fake();

    $user = User::factory()->create(['role' => Role::CUSTOMER]);
    $product = makeProduct(['price' => 80, 'stock' => 50]);

    $order = Order::create([
        'user_id' => $user->id,
        'total_price' => 80,
        'status' => OrderStatus::ON_HOLD,
        'payment_method' => 'Contado / Efectivo',
    ]);

    Session::put('updateOrder', $order->id);
    Session::put('cart', [$product->id => [
        'product_id' => $product->id,
        'name' => $product->description,
        'price' => 80.0,
        'bulkQuantity' => 1,
        'quantity' => 1,
    ]]);
    $this->actingAs($user);

    app(OrderService::class)->placeOrder(draftShipping() + ['status' => 'pending'], redirect: false);

    expect($order->fresh()->status)->toBe(OrderStatus::PENDING);
    expect(session('cart'))->toBeNull();
    Mail::assertQueued(OrderMail::class, 1);
});

it('resume a saved order into pending and sends the delayed email once', function () {
    Mail::fake();

    $user = User::factory()->create(['role' => Role::CUSTOMER]);
    $product = makeProduct(['price' => 100, 'stock' => 20]);

    $order = Order::create([
        'user_id' => $user->id,
        'total_price' => 100,
        'status' => OrderStatus::SAVED,
        'payment_method' => 'Contado / Efectivo',
    ]);

    Session::put('updateOrder', $order->id);
    Session::put('cart', [$product->id => [
        'product_id' => $product->id,
        'name' => $product->description,
        'price' => 100.0,
        'bulkQuantity' => 1,
        'quantity' => 1,
    ]]);
    $this->actingAs($user);

    app(OrderService::class)->placeOrder(draftShipping() + ['status' => 'pending'], redirect: false);

    expect($order->fresh()->status)->toBe(OrderStatus::PENDING);
    Mail::assertQueued(OrderMail::class, 1);
});
