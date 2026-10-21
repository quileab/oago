<?php

use App\Enums\Role;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function printOrderFor(User $user): Order
{
    return Order::create([
        'user_id' => $user->id,
        'total_price' => 100,
        'status' => 'pending',
        'payment_method' => 'Contado / Efectivo',
    ]);
}

it('lets the owner print their own order', function () {
    $owner = User::factory()->create(['role' => Role::CUSTOMER]);
    $order = printOrderFor($owner);

    $this->actingAs($owner)
        ->get(route('order.print', $order->id))
        ->assertOk()
        ->assertSee('Pedido');
});

it('blocks printing to another customer', function () {
    $owner = User::factory()->create(['role' => Role::CUSTOMER]);
    $stranger = User::factory()->create(['role' => Role::CUSTOMER]);
    $order = printOrderFor($owner);

    $this->actingAs($stranger)
        ->get(route('order.print', $order->id))
        ->assertForbidden();
});

it('lets an admin print any order', function () {
    $owner = User::factory()->create(['role' => Role::CUSTOMER]);
    $admin = User::factory()->create(['role' => Role::ADMIN]);
    $order = printOrderFor($owner);

    $this->actingAs($admin)
        ->get(route('order.print', $order->id))
        ->assertOk();
});

it('lets a sales agent impersonating the owner print their order', function () {
    $owner = User::factory()->create(['role' => Role::CUSTOMER]);
    $sales = User::factory()->create(['role' => Role::SALES]);
    $order = printOrderFor($owner);

    session()->put('sales_acting_as_customer_id', $owner->id);
    $this->actingAs($sales)
        ->get(route('order.print', $order->id))
        ->assertOk();
});

it('blocks a sales agent impersonating another customer', function () {
    $owner = User::factory()->create(['role' => Role::CUSTOMER]);
    $other = User::factory()->create(['role' => Role::CUSTOMER]);
    $sales = User::factory()->create(['role' => Role::SALES]);
    $order = printOrderFor($owner);

    session()->put('sales_acting_as_customer_id', $other->id);
    $this->actingAs($sales)
        ->get(route('order.print', $order->id))
        ->assertForbidden();
});
