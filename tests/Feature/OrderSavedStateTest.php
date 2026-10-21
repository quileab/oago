<?php

use App\Enums\OrderStatus;
use App\Enums\Role;
use App\Livewire\Cart;
use App\Mail\OrderMail;
use App\Models\AltOrder;
use App\Models\AltUser;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function makeDraftProduct(array $overrides = []): Product
{
    return makeProduct(array_merge([
        'price' => 100,
        'stock' => 10,
    ], $overrides));
}

function draftCartItem(Product $product, int $quantity = 1): array
{
    return [
        'product_id' => $product->id,
        'name' => $product->description,
        'price' => (float) $product->price,
        'bulkQuantity' => max(1, $product->qtty_package),
        'quantity' => $quantity,
    ];
}

function draftShipping(): array
{
    return [
        'sending_method' => 'Envío a cargo de la Empresa a Dirección Registrada',
        'payment_method' => 'Contado / Efectivo',
    ];
}

it('saves the cart as a draft without sending mail and clears the cart', function () {
    Mail::fake();

    $user = User::factory()->create(['role' => Role::CUSTOMER]);
    $product = makeDraftProduct();

    Session::put('cart', [$product->id => draftCartItem($product, 2)]);
    $this->actingAs($user);

    Livewire::test(Cart::class)->call('saveCart')->assertHasNoErrors();

    $order = Order::query()->first();

    expect($order)->not->toBeNull()
        ->and($order->status)->toBe(OrderStatus::SAVED)
        ->and($order->user_id)->toBe($user->id)
        ->and($order->items()->count())->toBe(1)
        ->and(Session::get('cart'))->toBeNull();

    Mail::assertNothingSent();
});

it('confirms a saved order from the checkout as pending and sends one mail', function () {
    Mail::fake();

    $user = User::factory()->create(['role' => Role::CUSTOMER]);
    $product = makeDraftProduct();

    $order = Order::create([
        'user_id' => $user->id,
        'total_price' => 200,
        'status' => OrderStatus::SAVED,
    ]);

    Session::put('updateOrder', $order->id);
    Session::put('cart', [$product->id => draftCartItem($product, 2)]);
    $this->actingAs($user);

    app(OrderService::class)->placeOrder(draftShipping(), redirect: false);

    expect($order->fresh()->status)->toBe(OrderStatus::PENDING);
    Mail::assertQueued(OrderMail::class, 1);
});

it('keeps the admin status when a customer edits a processing order', function () {
    Mail::fake();

    $user = User::factory()->create(['role' => Role::CUSTOMER]);
    $product = makeDraftProduct();

    $order = Order::create([
        'user_id' => $user->id,
        'total_price' => 100,
        'status' => OrderStatus::PROCESSING,
    ]);

    Session::put('updateOrder', $order->id);
    Session::put('cart', [$product->id => draftCartItem($product, 2)]);
    $this->actingAs($user);

    // El checkout ya no manda estado; aun si lo mandara, no debe pisar el actual.
    app(OrderService::class)->placeOrder(draftShipping() + ['status' => 'pending'], redirect: false);

    expect($order->fresh()->status)->toBe(OrderStatus::PROCESSING);
    Mail::assertNothingSent();
});

it('blocks editing an already completed order', function () {
    Mail::fake();

    $user = User::factory()->create(['role' => Role::CUSTOMER]);
    $product = makeDraftProduct();

    $order = Order::create([
        'user_id' => $user->id,
        'total_price' => 100,
        'status' => OrderStatus::COMPLETED,
    ]);

    Session::put('updateOrder', $order->id);
    Session::put('cart', [$product->id => draftCartItem($product, 1)]);
    $this->actingAs($user);

    expect(fn () => app(OrderService::class)->placeOrder(draftShipping(), redirect: false))
        ->toThrow(ValidationException::class);

    expect($order->fresh()->status)->toBe(OrderStatus::COMPLETED);
});

it('rejects an unknown status instead of silently converting it', function () {
    $user = User::factory()->create(['role' => Role::CUSTOMER]);
    $product = makeDraftProduct();

    Session::put('cart', [$product->id => draftCartItem($product)]);
    $this->actingAs($user);

    expect(fn () => app(OrderService::class)->placeOrder(['status' => 'bogus'], redirect: false))
        ->toThrow(ValidationException::class);

    expect(Order::count())->toBe(0);
});

it('allows saving a draft without stock but blocks the checkout', function () {
    Mail::fake();

    $user = User::factory()->create(['role' => Role::CUSTOMER]);
    $product = makeDraftProduct(['stock' => 0]);

    Session::put('cart', [$product->id => draftCartItem($product, 3)]);
    $this->actingAs($user);

    Livewire::test(Cart::class)->call('saveCart')->assertHasNoErrors();

    expect(Order::query()->first()?->status)->toBe(OrderStatus::SAVED);

    // Al confirmar (checkout) sí se exige stock.
    Session::put('updateOrder', Order::query()->first()->id);
    Session::put('cart', [$product->id => draftCartItem($product, 3)]);

    expect(fn () => app(OrderService::class)->placeOrder(draftShipping(), redirect: false))
        ->toThrow(ValidationException::class);
});

it('prevents modifying another user order through the session', function () {
    $owner = User::factory()->create(['role' => Role::CUSTOMER]);
    $intruder = User::factory()->create(['role' => Role::CUSTOMER]);
    $product = makeDraftProduct();

    $order = Order::create([
        'user_id' => $owner->id,
        'total_price' => 100,
        'status' => OrderStatus::SAVED,
    ]);

    Session::put('updateOrder', $order->id);
    Session::put('cart', [$product->id => draftCartItem($product)]);
    $this->actingAs($intruder);

    expect(fn () => app(OrderService::class)->placeOrder(draftShipping(), redirect: false))
        ->toThrow(ValidationException::class);

    expect($order->fresh()->status)->toBe(OrderStatus::SAVED);
});

it('saves alt user drafts as alt orders', function () {
    Mail::fake();

    $altUser = AltUser::create([
        'name' => 'Alt',
        'lastname' => 'Draft',
        'email' => 'alt-draft@example.com',
        'password' => 'secret',
        'role' => Role::GUEST,
        'is_internal' => true,
    ]);
    $product = makeDraftProduct();

    Auth::guard('alt')->login($altUser);
    Session::put('is_alt_login', true);
    Session::put('cart', [$product->id => draftCartItem($product)]);

    Livewire::test(Cart::class)->call('saveCart')->assertHasNoErrors();

    $order = AltOrder::query()->first();

    expect($order)->not->toBeNull()
        ->and($order->status)->toBe(OrderStatus::SAVED)
        ->and($order->alt_user_id)->toBe($altUser->id);

    Mail::assertNothingSent();
});
