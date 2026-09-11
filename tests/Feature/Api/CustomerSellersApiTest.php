<?php

use App\Enums\Role;
use App\Models\AltUser;
use App\Models\User;

test('sellers endpoint requires authentication', function () {
    $response = $this->getJson('/api/customer/sellers');

    $response->assertStatus(401);
});

test('customer without sellers returns empty array', function () {
    $customer = User::factory()->create(['role' => Role::CUSTOMER]);

    $response = $this->actingAs($customer, 'sanctum')->getJson('/api/customer/sellers');

    $response->assertStatus(200)
        ->assertExactJson([]);
});

test('customer can retrieve assigned sales agents', function () {
    $customer = User::factory()->create(['role' => Role::CUSTOMER]);

    $agentUser = User::factory()->create([
        'name' => 'Marcos',
        'lastname' => 'Vendedor',
        'email' => 'marcos@test.com',
        'phone' => '11223344',
        'role' => Role::SALES,
    ]);

    $agentAltUser = AltUser::create([
        'name' => 'Laura',
        'lastname' => 'Externa',
        'email' => 'laura@test.com',
        'phone' => '99887766',
        'password' => 'secret',
        'role' => Role::SALES,
    ]);

    $customer->assignedSalesAgents()->create([
        'sales_agent_id' => $agentUser->id,
        'sales_agent_type' => User::class,
        'is_admin_assigned' => true,
        'is_active' => true,
    ]);

    $customer->assignedSalesAgents()->create([
        'sales_agent_id' => $agentAltUser->id,
        'sales_agent_type' => AltUser::class,
        'is_admin_assigned' => false,
        'is_active' => true,
    ]);

    $response = $this->actingAs($customer, 'sanctum')->getJson('/api/customer/sellers');

    $response->assertStatus(200)
        ->assertJsonCount(2)
        ->assertJsonFragment([
            'id' => $agentUser->id,
            'name' => 'Marcos',
            'lastname' => 'Vendedor',
            'email' => 'marcos@test.com',
            'phone' => '11223344',
            'agent_type' => 'User',
            'is_admin_assigned' => true,
        ])
        ->assertJsonFragment([
            'id' => $agentAltUser->id,
            'name' => 'Laura',
            'lastname' => 'Externa',
            'email' => 'laura@test.com',
            'phone' => '99887766',
            'agent_type' => 'AltUser',
            'is_admin_assigned' => false,
        ]);
});

test('inactive assigned sellers are excluded', function () {
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
        ->assertExactJson([]);
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
