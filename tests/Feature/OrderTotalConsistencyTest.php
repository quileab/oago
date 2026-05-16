<?php

use App\Enums\Role;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\Session;

it('reproduces the order total inconsistency with bonuses', function () {
    // 1. Setup
    $user = User::factory()->create(['role' => Role::CUSTOMER]);

    // Create a product with bonus: 2 + 1 (buy 2, get 1 free)
    // bonus_threshold is usually "required amount", but Cart.php L117 says:
    // $bonusThreshold = $product->bonus_threshold + $product->bonus_amount;
    // So if threshold=2 and amount=1, total units for bonus = 3.
    $product = Product::create([
        'description' => 'Bonus Product Description',
        'price' => 100,
        'stock' => 100, // Add stock to pass validation
        'bonus_threshold' => 2,
        'bonus_amount' => 1,
        'qtty_package' => 1,
        'published' => 1,
        'model' => 'Bonus Model',
        'brand' => 'Bonus Brand',
        'visibility' => 'visible',
        'tax_status' => 'taxable',
    ]);

    // 2. Set cart with 3 units
    $cart = [
        $product->id => [
            'product_id' => $product->id,
            'name' => $product->description,
            'price' => 100,
            'quantity' => 3,
        ],
    ];
    Session::put('cart', $cart);

    // 3. Verify Cart calculation (Livewire/Cart.php)
    // $bonusThreshold = 2 + 1 = 3
    // $timesBonusApplies = floor(3 / 3) = 1
    // $freeUnits = 1 * 1 = 1
    // $billableQuantity = 3 - 1 = 2
    // total = 100 * 2 = 200

    // We can test the logic directly by calling calculateTotal if we want,
    // but the issue is in placeOrder.

    // 4. Call placeOrder (Models/Order.php)
    // placeOrder does: $total += $item['price'] * $item['quantity']
    // total = 100 * 3 = 300

    $this->actingAs($user);

    // Ensure session is started and cart is set
    session(['cart' => $cart]);

    Order::placeOrder([
        'status' => 'pending',
        'sending_method' => 'Envío a cargo de la Empresa a Dirección Registrada',
    ]);

    $order = Order::where('user_id', $user->id)->latest()->first();

    // This is where it fails if the bug exists
    expect((float) $order->total_price)->toBe(200.0);
});
