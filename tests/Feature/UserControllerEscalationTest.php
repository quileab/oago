<?php

use App\Enums\Role;
use App\Models\ListName;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

it('prevents a regular user from updating their own list_id', function () {
    // 1. Setup
    $list1 = ListName::create(['name' => 'Default List']);
    $list2 = ListName::create(['name' => 'Cheap List']);

    $user = User::factory()->create([
        'role' => Role::CUSTOMER,
        'list_id' => $list1->id,
    ]);

    // 2. Attempt to update list_id
    actingAs($user, 'sanctum')
        ->putJson("/api/users/{$user->id}", [
            'name' => 'Updated Name',
            'list_id' => $list2->id,
            'email' => $user->email, // email is usually required in PUT
        ])
        ->assertSuccessful();

    // 3. Verify
    $user->refresh();
    expect($user->name)->toBe('Updated Name')
        ->and($user->list_id)->toBe($list1->id); // Should NOT have changed to $list2->id
});

it('allows an admin to update a user\'s list_id', function () {
    // 1. Setup
    $list1 = ListName::create(['name' => 'Default List']);
    $list2 = ListName::create(['name' => 'Cheap List']);

    $admin = User::factory()->create(['role' => Role::ADMIN]);
    $user = User::factory()->create([
        'role' => Role::CUSTOMER,
        'list_id' => $list1->id,
    ]);

    // 2. Admin updates user's list_id
    actingAs($admin, 'sanctum')
        ->putJson("/api/users/{$user->id}", [
            'name' => $user->name,
            'email' => $user->email,
            'list_id' => $list2->id,
        ])
        ->assertSuccessful();

    // 3. Verify
    $user->refresh();
    expect($user->list_id)->toBe($list2->id); // Should HAVE changed
});
