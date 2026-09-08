<?php

use App\Enums\Role;
use App\Models\AltUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Volt;

uses(RefreshDatabase::class);

beforeEach(function () {
    request()->setLaravelSession(session()->driver());
});

it('clears alt_login flag when web user logs in', function () {
    $user = User::factory()->create([
        'email' => 'web@test.com',
        'password' => bcrypt('password123'),
        'role' => Role::CUSTOMER,
    ]);

    // Simulate an existing dirty session
    session()->put('is_alt_login', true);

    Volt::test('login')
        ->set('email', 'web@test.com')
        ->set('password', 'password123')
        ->call('login');

    expect(Auth::guard('web')->check())->toBeTrue();
    expect(Auth::guard('alt')->check())->toBeFalse();
    expect(session()->has('is_alt_login'))->toBeFalse();
});

it('sets alt_login flag when alt user logs in', function () {
    $altUser = AltUser::create([
        'name' => 'Alt',
        'lastname' => 'User',
        'email' => 'alt@test.com',
        'password' => 'password123',
        'role' => Role::GUEST,
    ]);

    Volt::test('login')
        ->set('email', 'alt@test.com')
        ->set('password', 'password123')
        ->call('login');

    expect(Auth::guard('alt')->check())->toBeTrue();
    expect(Auth::guard('web')->check())->toBeFalse();
    expect(session('is_alt_login'))->toBeTrue();
});

it('current_user helper resolves the correct guard', function () {
    $webUser = User::factory()->create(['role' => Role::CUSTOMER]);
    $this->actingAs($webUser, 'web');

    expect(current_user()->id)->toBe($webUser->id);
    expect(current_user())->toBeInstanceOf(User::class);

    Auth::guard('web')->logout();

    $altUser = AltUser::create([
        'name' => 'Alt',
        'lastname' => 'User',
        'email' => 'alt2@test.com',
        'password' => 'password123',
        'role' => Role::GUEST,
    ]);
    $this->actingAs($altUser, 'alt');

    expect(current_user()->id)->toBe($altUser->id);
    expect(current_user())->toBeInstanceOf(AltUser::class);
});

it('current_user helper respects sales impersonation', function () {
    $sales = User::factory()->create(['role' => Role::SALES]);
    $customer = User::factory()->create(['role' => Role::CUSTOMER]);

    $this->actingAs($sales, 'web');

    // Normal resolution
    expect(current_user()->id)->toBe($sales->id);

    // Set impersonation
    session(['sales_acting_as_customer_id' => $customer->id]);

    // Should now return the customer
    expect(current_user()->id)->toBe($customer->id);
    expect(current_user()->role->value)->toBe(Role::CUSTOMER->value);
});
