<?php

use App\Enums\Role;
use App\Helpers\SettingsHelper;
use App\Models\ListName;
use App\Models\ListPrice;
use App\Models\Order;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;
use Livewire\Volt\Volt;

uses(RefreshDatabase::class);

it('rejects adding a product without price to the cart', function () {
    $user = User::factory()->create(['role' => Role::CUSTOMER]);
    $product = makeProduct(['price' => 0, 'description' => 'Sin precio']);

    $this->actingAs($user);

    Volt::test('cart')->dispatch('addToCart', product: $product->id, quantity: 1);

    expect((array) Session::get('cart'))->toBeEmpty();
});

it('blocks placing an order with a product without price', function () {
    $user = User::factory()->create(['role' => Role::CUSTOMER]);
    $product = makeProduct(['price' => 0, 'description' => 'Sin precio']);

    Session::put('cart', [$product->id => [
        'product_id' => $product->id,
        'name' => $product->description,
        'price' => 50,
        'bulkQuantity' => 1,
        'quantity' => 1,
    ]]);
    $this->actingAs($user);

    expect(fn () => app(OrderService::class)->placeOrder(draftShipping(), redirect: false))
        ->toThrow(ValidationException::class);

    expect(Order::count())->toBe(0);
});

it('sells a product with a zero promo price at its list price', function () {
    Mail::fake();

    $list = ListName::create(['name' => 'General']);
    $promoList = ListName::create(['name' => 'Promo']);
    SettingsHelper::update_setting('promo_list_id', $promoList->id);
    $user = User::factory()->create(['role' => Role::CUSTOMER, 'list_id' => $list->id]);

    $product = makeProduct(['price' => 0, 'description' => 'Promo cero', 'stock' => 10]);
    ListPrice::create(['list_id' => $list->id, 'product_id' => $product->id, 'price' => 80, 'unit_price' => 8]);
    ListPrice::create(['list_id' => $promoList->id, 'product_id' => $product->id, 'price' => 0, 'unit_price' => 0]);

    Session::put('cart', [$product->id => [
        'product_id' => $product->id,
        'name' => $product->description,
        'price' => 80.0,
        'bulkQuantity' => 1,
        'quantity' => 2,
    ]]);
    $this->actingAs($user);

    app(OrderService::class)->placeOrder(draftShipping(), redirect: false);

    $order = Order::with('items')->where('user_id', $user->id)->first();
    expect((float) $order->total_price)->toBe(160.0);
    expect((float) $order->items->first()->price)->toBe(80.0);
});
