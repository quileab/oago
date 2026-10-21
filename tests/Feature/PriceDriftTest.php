<?php

use App\Enums\Role;
use App\Models\ListName;
use App\Models\ListPrice;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Session;
use Livewire\Volt\Volt;

uses(RefreshDatabase::class);

function driftProduct(User $user, float $listPrice, string $name = 'Normal'): Product
{
    $product = makeProduct(['description' => $name, 'price' => $listPrice, 'stock' => 100]);

    if ($user->list_id) {
        ListPrice::create([
            'list_id' => $user->list_id,
            'product_id' => $product->id,
            'price' => $listPrice,
            'unit_price' => $listPrice,
        ]);
    }

    return $product;
}

function staleCart(Product $product, float $oldPrice, int $quantity = 1): void
{
    Session::put('cart', [$product->id => [
        'product_id' => $product->id,
        'name' => $product->description,
        'price' => $oldPrice,
        'bulkQuantity' => 1,
        'quantity' => $quantity,
    ]]);
}

it('keeps the cart alive and synced when a price drifts', function () {
    $list = ListName::create(['name' => 'General']);
    $user = User::factory()->create(['role' => Role::CUSTOMER, 'list_id' => $list->id]);
    $product = driftProduct($user, 80);

    $this->actingAs($user);
    staleCart($product, 70.00);

    expect(fn () => app(OrderService::class)->placeOrder(draftShipping(), redirect: false))
        ->toThrow(Exception::class);

    // El carrito sobrevive y trae el precio nuevo.
    $cart = Session::get('cart');
    expect($cart)->not->toBeNull();
    expect((float) $cart[$product->id]['price'])->toBe(80.00);
    expect(Order::count())->toBe(0);

    // El archivo JSON se sincroniza con el precio actualizado.
    expect(current_user_cart_id())->toBe('web_'.$user->id);
    $jsonPath = storage_path('app/private/web_'.$user->id.'_cart.json');
    expect(file_exists($jsonPath))->toBeTrue();
    expect(file_get_contents($jsonPath))->toContain('80');
});

it('does not lose the updateOrder session when a price drifts', function () {
    $user = User::factory()->create(['role' => Role::CUSTOMER]);
    $order = Order::create([
        'user_id' => $user->id,
        'total_price' => 70,
        'status' => 'on-hold',
        'payment_method' => 'Contado / Efectivo',
    ]);
    $product = driftProduct($user, 100, 'Cambio');

    $this->actingAs($user);
    Session::put('updateOrder', $order->id);
    staleCart($product, 70.00);

    expect(fn () => app(OrderService::class)->placeOrder(draftShipping() + ['status' => 'pending'], redirect: false))
        ->toThrow(Exception::class);

    expect((int) Session::get('updateOrder'))->toBe($order->id);
    expect((float) Session::get('cart')[$product->id]['price'])->toBe(100.00);
});

it('syncs every drifted price in a single pass, not just the first', function () {
    $list = ListName::create(['name' => 'General']);
    $user = User::factory()->create(['role' => Role::CUSTOMER, 'list_id' => $list->id]);
    $a = driftProduct($user, 80, 'Producto A');
    $b = driftProduct($user, 60, 'Producto B');

    $this->actingAs($user);
    Session::put('cart', [
        $a->id => ['product_id' => $a->id, 'name' => 'Producto A', 'price' => 70.0, 'bulkQuantity' => 1, 'quantity' => 1],
        $b->id => ['product_id' => $b->id, 'name' => 'Producto B', 'price' => 50.0, 'bulkQuantity' => 1, 'quantity' => 2],
    ]);

    expect(fn () => app(OrderService::class)->placeOrder(draftShipping(), redirect: false))
        ->toThrow(Exception::class);

    $cart = Session::get('cart');
    expect((float) $cart[$a->id]['price'])->toBe(80.0);
    expect((float) $cart[$b->id]['price'])->toBe(60.0);
});

it('removes an item that stopped being sellable when updating its quantity', function () {
    $user = User::factory()->create(['role' => Role::CUSTOMER]);
    $product = driftProduct($user, 50, 'Caduca');

    $this->actingAs($user);
    staleCart($product, 50);

    // El sistema externo deja el producto sin precio (no vendible).
    $product->update(['price' => 0]);
    ListPrice::where('product_id', $product->id)->update(['price' => 0, 'unit_price' => 0]);

    $component = Volt::test('cart');
    $component->call('updateQuantity', $product->id, 2);

    expect(Session::get('cart'))->toBe([]);
});
