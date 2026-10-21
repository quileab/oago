<?php

use App\Enums\OrderStatus;
use App\Enums\Role;
use App\Models\AltOrder;
use App\Models\AltUser;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;

uses(RefreshDatabase::class);

function authzOrder(User $user, string $status = 'completed'): Order
{
    return Order::create([
        'user_id' => $user->id,
        'total_price' => 100,
        'status' => $status,
        'payment_method' => 'Contado / Efectivo',
    ]);
}

it('blocks a customer from changing the order status', function () {
    $customer = User::factory()->create(['role' => Role::CUSTOMER]);
    $order = authzOrder($customer);

    $this->actingAs($customer);

    Volt::test('orderitems', ['orderId' => $order->id])
        ->call('changeStatus', 'completed')
        ->assertForbidden();

    expect($order->fresh()->status)->toBe(OrderStatus::COMPLETED);
});

it('lets an admin change the order status', function () {
    $customer = User::factory()->create(['role' => Role::CUSTOMER]);
    $admin = User::factory()->create(['role' => Role::ADMIN]);
    $order = authzOrder($customer, 'pending');

    $this->actingAs($admin);

    Volt::test('orderitems', ['orderId' => $order->id])
        ->call('changeStatus', 'completed');

    expect($order->fresh()->status)->toBe(OrderStatus::COMPLETED);
});

it('rejects an invalid status even for an admin', function () {
    $admin = User::factory()->create(['role' => Role::ADMIN]);
    $order = authzOrder($admin, 'pending');

    $this->actingAs($admin);

    Volt::test('orderitems', ['orderId' => $order->id])
        ->call('changeStatus', 'ultra-pending');

    expect($order->fresh()->status)->toBe(OrderStatus::PENDING);
});

it('lets the owner delete a saved order and blocks deleting a finished one', function () {
    $customer = User::factory()->create(['role' => Role::CUSTOMER]);
    $saved = authzOrder($customer, 'saved');
    $completed = authzOrder($customer, 'completed');

    $this->actingAs($customer);

    $finished = Volt::test('orderitems', ['orderId' => $completed->id]);
    $finished->call('delete')->assertForbidden();

    expect($completed->fresh()->status)->toBe(OrderStatus::COMPLETED);

    Volt::test('orderitems', ['orderId' => $saved->id])
        ->call('delete');

    expect(Order::find($saved->id))->toBeNull();
});

it('protects transfer to normal account behind the admin role', function () {
    $altOwner = AltUser::create([
        'name' => 'Alt',
        'lastname' => 'Owner',
        'email' => 'alt-owner.test@example.com',
        'password' => bcrypt('secret12345'),
        'role' => Role::CUSTOMER,
        'is_internal' => false,
    ]);
    $altOrder = AltOrder::create([
        'alt_user_id' => $altOwner->id,
        'total_price' => 100,
        'status' => OrderStatus::ON_HOLD,
        'payment_method' => 'Contado / Efectivo',
    ]);

    $this->actingAs($altOwner, 'alt');

    $component = Volt::test('alt-orderitems', ['orderId' => $altOrder->id]);
    $component->call('transferToNormalAccount')->assertForbidden();
    expect(Order::count())->toBe(0);
});

it('lets only an admin change an alt order status', function () {
    $altOwner = AltUser::create([
        'name' => 'Alt',
        'lastname' => 'Two',
        'email' => 'alt-two.test@example.com',
        'password' => bcrypt('secret12345'),
        'role' => Role::CUSTOMER,
        'is_internal' => false,
    ]);
    $altOrder = AltOrder::create([
        'alt_user_id' => $altOwner->id,
        'total_price' => 100,
        'status' => OrderStatus::ON_HOLD,
        'payment_method' => 'Contado / Efectivo',
    ]);
    $admin = User::factory()->create(['role' => Role::ADMIN]);

    $this->actingAs($altOwner, 'alt');

    $component = Volt::test('alt-orderitems', ['orderId' => $altOrder->id]);
    $component->call('changeStatus', 'completed')->assertForbidden();

    expect($altOrder->fresh()->status)->toBe(OrderStatus::ON_HOLD);

    $this->actingAs($admin);

    Volt::test('alt-orderitems', ['orderId' => $altOrder->id])
        ->call('changeStatus', 'completed');

    expect($altOrder->fresh()->status)->toBe(OrderStatus::COMPLETED);
});
