<?php

use App\Enums\Role;
use App\Models\Product;
use App\Models\User;
use App\Services\OrderService;
use App\Services\PriceListService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Volt\Volt;

uses(RefreshDatabase::class);

function makeBonusProduct(): Product
{
    return Product::create([
        'description' => 'Bonus Product',
        'price' => 100,
        'bonus_threshold' => 4,
        'bonus_amount' => 1,
        'qtty_package' => 1,
        'stock' => 100,
        'visibility' => 'visible',
        'tax_status' => 'taxable',
        'published' => true,
        'model' => 'M1',
        'brand' => 'B1',
    ]);
}

it('calculates the billable quantity centrally', function () {
    $product = makeBonusProduct();
    $service = app(PriceListService::class);

    // 5 units on "4 + 1" -> 1 free unit, 4 billable
    expect($service->calculateBillableQuantity($product, 5))->toBe(4);

    // Only the full bonus: 8 -> free 2 (8/5 floor 1); billable: 4
    expect($service->calculateBillableQuantity($product, 9))->toBe(8);

    // Less than threshold: pays everything.
    expect($service->calculateBillableQuantity($product, 3))->toBe(3);

    // No bonus products: pay everything.
    $plain = Product::create([
        'description' => 'Plain Product',
        'price' => 50,
        'qtty_package' => 1,
        'visibility' => 'visible',
        'tax_status' => 'taxable',
        'published' => true,
        'model' => 'M2',
        'brand' => 'B1',
    ]);
    expect($service->calculateBillableQuantity($plain, 7))->toBe(7);

    // Null product (removed from DB in the carrito): pay everything.
    expect($service->calculateBillableQuantity(null, 7))->toBe(7);
});

it('keeps cart and order validation totals consistent for bonus products', function () {
    $user = User::factory()->create(['role' => Role::CUSTOMER]);
    $product = makeBonusProduct();
    $this->actingAs($user);

    Session::put('cart', [
        $product->id => [
            'product_id' => $product->id,
            'name' => $product->description,
            'price' => 100.0,
            'bulkQuantity' => 1,
            'quantity' => 5,
        ],
    ]);

    // Calculate total total in the cart component.
    Volt::test('cart', [])
        ->assertSet('total', 400.0);

    // validateCart (misma fuente que OrderService) debe ser consistente.
    $cart = Session::get('cart');
    $method = new ReflectionMethod(OrderService::class, 'validateCart');
    $validated = $method->invoke(new OrderService, $cart, $user);
    expect($validated['total'])->toBe(400.0);
});

it('hydrates products only once per Livewire render request', function () {
    $user = User::factory()->create(['role' => Role::CUSTOMER]);
    $this->actingAs($user);

    $p1 = Product::create([
        'description' => 'P1',
        'price' => 10,
        'qtty_package' => 1,
        'visibility' => 'visible',
        'tax_status' => 'taxable',
        'published' => true,
        'model' => 'M1',
        'brand' => 'B1',
    ]);
    $p2 = Product::create([
        'description' => 'P2',
        'price' => 20,
        'qtty_package' => 1,
        'visibility' => 'visible',
        'tax_status' => 'taxable',
        'published' => true,
        'model' => 'M1',
        'brand' => 'B1',
    ]);

    Session::put('cart', [
        $p1->id => ['product_id' => $p1->id, 'name' => 'P1', 'price' => 10.0, 'bulkQuantity' => 1, 'quantity' => 1],
        $p2->id => ['product_id' => $p2->id, 'name' => 'P2', 'price' => 20.0, 'bulkQuantity' => 1, 'quantity' => 2],
    ]);

    DB::enableQueryLog();

    Volt::test('cart');

    $productQueries = collect(DB::getQueryLog())
        ->filter(fn ($q) => str_contains($q['query'], 'from `products`') || str_contains($q['query'], 'from "products"'))
        ->count();

    expect($productQueries)->toBe(1);

    DB::disableQueryLog();
});

it('applies the same billable quantity in the checkout summary', function () {
    $user = User::factory()->create(['role' => Role::CUSTOMER]);
    $product = makeBonusProduct();
    $this->actingAs($user);

    Session::put('cart', [
        $product->id => [
            'product_id' => $product->id,
            'name' => $product->description,
            'price' => 100.0,
            'bulkQuantity' => 1,
            'quantity' => 5,
        ],
    ]);

    Volt::test('checkout')
        ->assertSet('total', 400.0)
        ->assertSet('cart_content.'.$product->id.'.billable_quantity', 4)
        ->assertSet('cart_content.'.$product->id.'.is_bonus_applied', true);
});
