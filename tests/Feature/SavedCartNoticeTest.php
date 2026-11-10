<?php

use App\Enums\OrderStatus;
use App\Enums\Role;
use App\Livewire\WebNavbar;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Session;
use Livewire\Livewire;
use Livewire\Volt\Volt;

uses(RefreshDatabase::class);

it('shows saved cart notice only when the current cart is empty', function () {
    $user = User::factory()->create(['role' => Role::CUSTOMER]);
    $product = makeProduct();

    $order = Order::create([
        'user_id' => $user->id,
        'status' => OrderStatus::SAVED,
        'total_price' => 200,
    ]);

    OrderItem::create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'quantity' => 2,
        'price' => 100,
    ]);

    $this->actingAs($user);

    // 1. Cuando el carrito actual está vacío: debe mostrar el aviso y el botón para retomar
    Volt::test('cart')
        ->assertSee('Tenés 1 carrito guardado')
        ->assertSee('Retomar Carrito Guardado');

    // 2. Si el carrito tiene productos: NO debe mostrar el aviso
    Session::put('cart', [
        $product->id => [
            'product_id' => $product->id,
            'name' => $product->description,
            'price' => 100,
            'quantity' => 1,
            'bulkQuantity' => 1,
            'byBulk' => true,
        ],
    ]);

    Volt::test('cart')
        ->assertDontSee('Tenés 1 carrito guardado')
        ->assertDontSee('Retomar Carrito Guardado');
});

it('resumes a saved cart correctly into the active session', function () {
    $user = User::factory()->create(['role' => Role::CUSTOMER]);
    $product = makeProduct(['price' => 150]);

    $order = Order::create([
        'user_id' => $user->id,
        'status' => OrderStatus::SAVED,
        'total_price' => 300,
    ]);

    OrderItem::create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'quantity' => 2,
        'price' => 150,
    ]);

    $this->actingAs($user);

    Volt::test('cart')
        ->call('resumeSavedOrder', $order->id)
        ->assertDispatched('cart-updated');

    expect(Session::get("cart.{$product->id}.quantity"))->toBe(2);
    expect(Session::get('updateOrder'))->toBe($order->id);
});

it('calculates the saved orders count on WebNavbar', function () {
    $user = User::factory()->create(['role' => Role::CUSTOMER]);
    Order::create([
        'user_id' => $user->id,
        'status' => OrderStatus::SAVED,
        'total_price' => 100,
    ]);

    $this->actingAs($user);

    Livewire::test(WebNavbar::class)
        ->assertSeeHtml('badge-warning font-bold">1</span>');
});
