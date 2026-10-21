<?php

use App\Enums\Role;
use App\Models\ListName;
use App\Models\ListPrice;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

uses(RefreshDatabase::class);

/**
 * Contrato: total_price = suma de subtotales reales (bulto + sueltas),
 * y la línea guardada es el unitario promedio (redondeo a 2 decimales),
 * con desvío máximo de ±0.01 por línea contra price * quantity.
 */
it('sums the exact subtotals into the order total without bonus', function () {
    $list = ListName::create(['name' => 'General']);
    $user = User::factory()->create(['role' => Role::CUSTOMER, 'list_id' => $list->id]);
    $product = makeProduct(['price' => 80, 'description' => 'Simple']);
    ListPrice::create(['list_id' => $list->id, 'product_id' => $product->id, 'price' => 78.50, 'unit_price' => 80]);

    $this->actingAs($user);
    Session::put('cart', [$product->id => [
        'product_id' => $product->id,
        'name' => $product->description,
        'price' => 78.50,
        'bulkQuantity' => 1,
        'quantity' => 7,
    ]]);

    app(OrderService::class)->placeOrder(draftShipping(), redirect: false);

    $order = Order::where('user_id', $user->id)->firstOrFail();
    $linesSum = $order->items->sum(fn (OrderItem $i) => (float) $i->price * (int) $i->quantity);

    expect((float) $order->total_price)->toBe(7 * 78.50);
    expect(abs($linesSum - (float) $order->total_price))->toBeLessThanOrEqual(0.01);
});

it('sums the exact subtotals into the order total with bonus and line cent drift at most a cent', function () {
    $user = User::factory()->create(['role' => Role::CUSTOMER]);
    $product = makeProduct([
        'price' => 80,
        'description' => 'Bonus 2+1',
        'bonus_threshold' => 2,
        'bonus_amount' => 1,
        'stock' => 100,
    ]);

    $this->actingAs($user);
    Session::put('cart', [$product->id => [
        'product_id' => $product->id,
        'name' => $product->description,
        'price' => 80.0,
        'bulkQuantity' => 1,
        'quantity' => 3,
    ]]);

    app(OrderService::class)->placeOrder(draftShipping(), redirect: false);

    $order = Order::where('user_id', $user->id)->firstOrFail();

    // Se factura 2 unidades (3 pedidas, 1 gratis): 160 exacto.
    expect((float) $order->total_price)->toBe(160.0);

    $linesSum = $order->items->sum(fn (OrderItem $i) => (float) $i->price * (int) $i->quantity);
    expect(abs($linesSum - (float) $order->total_price))->toBeLessThanOrEqual(0.01);
});

it('edges bulk and loose units with the mixed billing scale', function () {
    $list = ListName::create(['name' => 'General']);
    $user = User::factory()->create(['role' => Role::CUSTOMER, 'list_id' => $list->id]);
    $product = makeProduct(['price' => 80, 'qtty_package' => 4, 'description' => 'Bulto x4', 'stock' => 100]);
    ListPrice::create(['list_id' => $list->id, 'product_id' => $product->id, 'price' => 78, 'unit_price' => 80]);

    $this->actingAs($user);
    Session::put('cart', [$product->id => [
        'product_id' => $product->id,
        'name' => $product->description,
        'price' => 78.0,
        'bulkQuantity' => 4,
        'quantity' => 5,
    ]]);

    app(OrderService::class)->placeOrder(draftShipping(), redirect: false);

    $order = Order::where('user_id', $user->id)->firstOrFail();

    // 4 unidades a 78 (bulto) + 1 suelta a 80 = 392.
    expect((float) $order->total_price)->toBe(392.0);

    $linesSum = $order->items->sum(fn (OrderItem $i) => (float) $i->price * (int) $i->quantity);
    expect(abs($linesSum - (float) $order->total_price))->toBeLessThanOrEqual(0.01);
});

it('keeps the same total when the order is re-validated from the cart', function () {
    $user = User::factory()->create(['role' => Role::CUSTOMER]);
    $product = makeProduct(['price' => 80, 'stock' => 100]);

    $this->actingAs($user);
    Session::put('cart', [$product->id => [
        'product_id' => $product->id,
        'name' => $product->description,
        'price' => 80.0,
        'bulkQuantity' => 1,
        'quantity' => 2,
    ]]);

    $validated = app(OrderService::class)
        ->validateCart(Session::get('cart'), $user, enforceStock: false);

    // 2 x 80 = 160 sin bonus.
    expect($validated['total'])->toBe(160.0);
    expect(DB::table('orders')->count())->toBe(0);
});
