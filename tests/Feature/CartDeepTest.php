<?php

use App\Enums\Role;
use App\Models\ListName;
use App\Models\ListPrice;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Session;
use Livewire\Volt\Volt;

uses(RefreshDatabase::class);

it('handles bulk ordering correctly', function () {
    $user = User::factory()->create(['role' => Role::CUSTOMER]);

    $product = Product::create([
        'description' => 'Bulk Product',
        'price' => 100,
        'qtty_package' => 12,
        'visibility' => 'visible',
        'tax_status' => 'taxable',
        'published' => true,
        'model' => 'M1',
        'brand' => 'B1',
    ]);

    $this->actingAs($user);

    Volt::test('cart')
        ->dispatch('addToCart', product: $product->id, quantity: 1)
        ->assertSet('total', 100.0);

    $cart = Session::get('cart');
    expect($cart[$product->id]['byBulk'])->toBeFalse();

    // Update to 12
    Volt::test('cart')
        ->call('updateQuantity', $product->id, 12);

    $cart = Session::get('cart');
    expect($cart[$product->id]['byBulk'])->toBeTrue();
});

it('calculates complex bonuses correctly', function () {
    $user = User::factory()->create(['role' => Role::CUSTOMER]);

    $product = Product::create([
        'description' => 'Bonus Product',
        'price' => 100,
        'bonus_threshold' => 2,
        'bonus_amount' => 1,
        'qtty_package' => 1,
        'visibility' => 'visible',
        'tax_status' => 'taxable',
        'published' => true,
        'model' => 'M1',
        'brand' => 'B1',
    ]);

    $this->actingAs($user);

    // Buy 3 (2 + 1 free)
    Volt::test('cart')
        ->dispatch('addToCart', product: $product->id, quantity: 3)
        ->assertSet('total', 200.0);

    // Buy 6 (2*(2 + 1 free))
    Volt::test('cart')
        ->call('updateQuantity', $product->id, 6)
        ->assertSet('total', 400.0);

    // Buy 5 (2 + 1 free + 2 paid)
    Volt::test('cart')
        ->call('updateQuantity', $product->id, 5)
        ->assertSet('total', 400.0); // 3 (2+1) + 2 = 5 units. Billable: 2 (from first 3) + 2 = 4. 4 * 100 = 400.
});

it('calculates mixed package and unit pricing correctly', function () {
    $user = User::factory()->create(['role' => Role::CUSTOMER]);

    // Crear producto: bulto de 12 unidades
    $product = Product::create([
        'description' => 'Mixed Pricing Product',
        'price' => 1000,
        'qtty_package' => 12,
        'visibility' => 'visible',
        'tax_status' => 'taxable',
        'published' => true,
        'model' => 'M1',
        'brand' => 'B1',
    ]);

    // Crear lista de precios base
    $baseList = ListName::create(['id' => 1, 'name' => 'Lista A  $']);

    // Asignar lista al usuario
    $user->update(['list_id' => $baseList->id]);

    // Crear registro de precios: bulto = 500/u, unidad suelta = 800/u
    ListPrice::create([
        'product_id' => $product->id,
        'list_id' => $baseList->id,
        'price' => 500,
        'unit_price' => 800,
    ]);

    $this->actingAs($user);

    // Comprar 15 unidades (1 bulto de 12 + 3 sueltas)
    // 12 * 500 + 3 * 800 = 6000 + 2400 = 8400.0
    Volt::test('cart')
        ->dispatch('addToCart', product: $product->id, quantity: 15)
        ->assertSet('total', 8400.0);
});

it('renders safely when product in cart is deleted from database', function () {
    $user = User::factory()->create(['role' => Role::CUSTOMER]);
    $this->actingAs($user);

    $product = Product::create([
        'description' => 'Test Product',
        'price' => 100,
        'qtty_package' => 1,
        'visibility' => 'visible',
        'tax_status' => 'taxable',
        'published' => true,
        'model' => 'M1',
        'brand' => 'B1',
    ]);

    Volt::test('cart')
        ->dispatch('addToCart', product: $product->id, quantity: 1)
        ->assertSet('total', 100.0);

    $product->delete();

    Volt::test('cart')
        ->assertSee('Test Product');
});
