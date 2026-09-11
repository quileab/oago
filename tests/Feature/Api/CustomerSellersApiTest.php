<?php

use App\Enums\Role;
use App\Models\AltUser;
use App\Models\User;

test('sellers endpoint requires authentication', function () {
    $response = $this->getJson('/api/customer/sellers');

    $response->assertStatus(401);
});

test('customer with no sellers in system returns empty array', function () {
    $customer = User::factory()->create(['role' => Role::CUSTOMER]);

    $response = $this->actingAs($customer, 'sanctum')->getJson('/api/customer/sellers');

    $response->assertStatus(200)
        ->assertExactJson([]);
});

test('customer retrieves all sales agents with is_assigned boolean and assigned first', function () {
    $customer = User::factory()->create(['role' => Role::CUSTOMER]);

    // Unassigned User Sales Agent
    $unassignedUser = User::factory()->create([
        'name' => 'Carlos',
        'lastname' => 'Libre',
        'email' => 'carlos@test.com',
        'phone' => '111111',
        'role' => Role::SALES,
    ]);

    // Assigned User Sales Agent
    $assignedUser = User::factory()->create([
        'name' => 'Marcos',
        'lastname' => 'Vendedor',
        'email' => 'marcos@test.com',
        'phone' => '222222',
        'role' => Role::SALES,
    ]);

    // Assigned AltUser Sales Agent
    $assignedAltUser = AltUser::create([
        'name' => 'Laura',
        'lastname' => 'Externa',
        'email' => 'laura@test.com',
        'phone' => '333333',
        'password' => 'secret',
        'role' => Role::SALES,
    ]);

    // Unassigned AltUser Sales Agent
    $unassignedAltUser = AltUser::create([
        'name' => 'Pedro',
        'lastname' => 'Externo Libre',
        'email' => 'pedro@test.com',
        'phone' => '444444',
        'password' => 'secret',
        'role' => Role::SALES,
    ]);

    // Assign only Marcos and Laura
    $customer->assignedSalesAgents()->create([
        'sales_agent_id' => $assignedUser->id,
        'sales_agent_type' => User::class,
        'is_admin_assigned' => true,
        'is_active' => true,
    ]);

    $customer->assignedSalesAgents()->create([
        'sales_agent_id' => $assignedAltUser->id,
        'sales_agent_type' => AltUser::class,
        'is_admin_assigned' => false,
        'is_active' => true,
    ]);

    $response = $this->actingAs($customer, 'sanctum')->getJson('/api/customer/sellers');

    $response->assertStatus(200)
        ->assertJsonCount(4);

    $data = $response->json();

    // Verify first two items have is_assigned: true
    expect($data[0]['is_assigned'])->toBeTrue();
    expect($data[1]['is_assigned'])->toBeTrue();

    // Verify last two items have is_assigned: false
    expect($data[2]['is_assigned'])->toBeFalse();
    expect($data[3]['is_assigned'])->toBeFalse();

    // Verify fragments
    $response->assertJsonFragment([
        'id' => $assignedUser->id,
        'name' => 'Marcos',
        'is_assigned' => true,
        'is_admin_assigned' => true,
        'agent_type' => 'User',
    ])->assertJsonFragment([
        'id' => $assignedAltUser->id,
        'name' => 'Laura',
        'is_assigned' => true,
        'is_admin_assigned' => false,
        'agent_type' => 'AltUser',
    ])->assertJsonFragment([
        'id' => $unassignedUser->id,
        'name' => 'Carlos',
        'is_assigned' => false,
        'is_admin_assigned' => false,
        'agent_type' => 'User',
    ])->assertJsonFragment([
        'id' => $unassignedAltUser->id,
        'name' => 'Pedro',
        'is_assigned' => false,
        'is_admin_assigned' => false,
        'agent_type' => 'AltUser',
    ]);
});

test('inactive assigned sellers have is_assigned false', function () {
    $customer = User::factory()->create(['role' => Role::CUSTOMER]);

    $agentUser = User::factory()->create([
        'name' => 'Inactivo',
        'lastname' => 'Vendedor',
        'email' => 'inactivo@test.com',
        'role' => Role::SALES,
    ]);

    $customer->assignedSalesAgents()->create([
        'sales_agent_id' => $agentUser->id,
        'sales_agent_type' => User::class,
        'is_admin_assigned' => true,
        'is_active' => false,
    ]);

    $response = $this->actingAs($customer, 'sanctum')->getJson('/api/customer/sellers');

    $response->assertStatus(200)
        ->assertJsonCount(1)
        ->assertJsonFragment([
            'id' => $agentUser->id,
            'name' => 'Inactivo',
            'is_assigned' => false,
        ]);
});

test('get user includes assigned sales agents relation', function () {
    $customer = User::factory()->create(['role' => Role::CUSTOMER]);
    $agentUser = User::factory()->create(['role' => Role::SALES]);

    $customer->assignedSalesAgents()->create([
        'sales_agent_id' => $agentUser->id,
        'sales_agent_type' => User::class,
        'is_admin_assigned' => true,
        'is_active' => true,
    ]);

    $response = $this->actingAs($customer, 'sanctum')->getJson('/api/user');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'id',
            'email',
            'assigned_sales_agents' => [
                '*' => [
                    'id',
                    'customer_id',
                    'sales_agent_id',
                    'sales_agent_type',
                    'sales_agent',
                ],
            ],
        ]);
});
