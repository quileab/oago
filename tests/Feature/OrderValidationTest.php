<?php

use App\Enums\Role;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;

it('prevents placing order if stock is insufficient', function () {
    $user = User::factory()->create(['role' => Role::CUSTOMER]);

    $product = Product::create([
        'description' => 'Test Product',
        'price' => 100,
        'stock' => 5, // Only 5 in stock
        'qtty_package' => 1,
        'published' => 1,
        'model' => 'M1',
        'brand' => 'B1',
        'visibility' => 'visible',
        'tax_status' => 'taxable',
    ]);

    Session::put('cart', [
        $product->id => [
            'product_id' => $product->id,
            'name' => $product->description,
            'price' => 100,
            'quantity' => 10, // Requesting 10
        ],
    ]);

    $this->actingAs($user);

    expect(fn () => Order::placeOrder(['status' => 'pending']))
        ->toThrow(ValidationException::class, 'Stock insuficiente');
});

it('prevents placing order if price has changed', function () {
    $user = User::factory()->create(['role' => Role::CUSTOMER]);

    $product = Product::create([
        'description' => 'Test Product',
        'price' => 100, // Current price is 100
        'stock' => 50,
        'qtty_package' => 1,
        'published' => 1,
        'model' => 'M1',
        'brand' => 'B1',
        'visibility' => 'visible',
        'tax_status' => 'taxable',
    ]);

    Session::put('cart', [
        $product->id => [
            'product_id' => $product->id,
            'name' => $product->description,
            'price' => 80, // Cart price is outdated (80)
            'quantity' => 1,
        ],
    ]);

    $this->actingAs($user);

    expect(fn () => Order::placeOrder(['status' => 'pending']))
        ->toThrow(ValidationException::class, "El precio de {$product->description} ha cambiado");
});
