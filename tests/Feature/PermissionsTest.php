<?php

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

it('blocks non-admin users from admin API routes', function () {
    $user = User::factory()->create(['role' => Role::CUSTOMER]);

    actingAs($user, 'sanctum')
        ->getJson('/api/orders/pending')
        ->assertNotFound();
});

it('allows admin users to access admin API routes', function () {
    $admin = User::factory()->create(['role' => Role::ADMIN]);

    actingAs($admin, 'sanctum')
        ->getJson('/api/orders/pending')
        ->assertSuccessful();
});

it('correctly identifies the current user with the helper', function () {
    $user = User::factory()->create(['role' => Role::CUSTOMER]);

    actingAs($user, 'web');

    expect(current_user()->id)->toBe($user->id);
    expect(current_user())->toBeInstanceOf(User::class);
});

it('handles sales agent acting as customer in current_user helper', function () {
    $agent = User::factory()->create(['role' => Role::SALES]);
    $customer = User::factory()->create(['role' => Role::CUSTOMER]);

    actingAs($agent, 'web');
    session(['sales_acting_as_customer_id' => $customer->id]);

    expect(current_user()->id)->toBe($customer->id);
    expect(current_user()->role)->toBe(Role::CUSTOMER);
});
