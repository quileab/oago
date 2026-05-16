<?php

use App\Enums\Role;
use App\Models\AltUser;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

it('generates unique cart IDs for different user types', function () {
    // 1. Regular User
    $user = User::factory()->create(['role' => Role::CUSTOMER]);
    Auth::guard('web')->login($user);

    expect(current_user_cart_id())->toBe('web_'.$user->id);
    Auth::guard('web')->logout();

    // 2. Alt User
    $altUser = AltUser::create([
        'name' => 'Alt',
        'lastname' => 'User',
        'email' => 'cart_alt@example.com',
        'password' => bcrypt('password'),
        'role' => Role::CUSTOMER,
    ]);
    Auth::guard('alt')->login($altUser);

    expect(current_user_cart_id())->toBe('alt_'.$altUser->id);
    Auth::guard('alt')->logout();
});

it('generates the correct cart ID for a sales agent acting as a customer', function () {
    $agent = User::factory()->create(['role' => Role::SALES]);
    $customer = User::factory()->create(['role' => Role::CUSTOMER]);

    Auth::guard('web')->login($agent);

    // Not acting yet
    expect(current_user_cart_id())->toBe('web_'.$agent->id);

    // Act as customer
    Session::put('sales_acting_as_customer_id', $customer->id);

    expect(current_user_cart_id())->toBe('web_'.$customer->id);
});
