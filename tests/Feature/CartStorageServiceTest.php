<?php

use App\Enums\Role;
use App\Models\User;
use App\Services\CartStorageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Session;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = app(CartStorageService::class);
    $this->user = User::factory()->create(['role' => Role::CUSTOMER]);
});

afterEach(function () {
    $this->service->forget($this->user);
});

it('builds the cart key from the user type', function () {
    expect($this->service->key($this->user))->toBe('web_'.$this->user->id);
});

it('saves atomically and loads the cart back', function () {
    $cart = [5 => ['product_id' => 5, 'quantity' => 2, 'price' => 100]];

    $this->service->save($this->user, $cart);

    expect($this->service->load($this->user))->toBe($cart);
    expect(glob(dirname($this->service->path($this->user)).'/*.tmp'))->toBeEmpty();
});

it('removes the file when the cart is empty', function () {
    $this->service->save($this->user, [1 => ['product_id' => 1, 'quantity' => 1, 'price' => 10]]);
    $this->service->save($this->user, []);

    expect(is_file($this->service->path($this->user)))->toBeFalse();
});

it('restores the persisted cart into a fresh session', function () {
    $product = makeProduct();
    $this->service->save($this->user, [
        $product->id => ['product_id' => $product->id, 'quantity' => 3, 'price' => 1],
    ]);

    Session::flush();
    expect(Session::has('cart'))->toBeFalse();

    $this->service->restoreIfMissing($this->user);

    expect(Session::get("cart.{$product->id}.quantity"))->toBe(3);
});

it('does not overwrite an existing session cart', function () {
    $this->service->save($this->user, [9 => ['product_id' => 9, 'quantity' => 1, 'price' => 1]]);
    Session::put('cart', []);

    $this->service->restoreIfMissing($this->user);

    expect(Session::get('cart'))->toBe([]);
});
