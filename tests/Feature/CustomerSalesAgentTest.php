<?php

namespace Tests\Feature;

use App\Models\AltUser;
use App\Models\CustomerSalesAgent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Enums\Role;

class CustomerSalesAgentTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_be_assigned_a_sales_agent_user()
    {
        // 1. Create Customer
        $customer = User::factory()->create();

        // 2. Create Sales Agent (User)
        $salesAgent = User::factory()->create(['role' => Role::SALES]);

        // 3. Assign
        $customer->assignedSalesAgents()->create([
            'sales_agent_id' => $salesAgent->id,
            'sales_agent_type' => User::class,
            'is_admin_assigned' => true,
        ]);

        // 4. Assertions
        $this->assertCount(1, $customer->assignedSalesAgents);
        $this->assertEquals($salesAgent->id, $customer->assignedSalesAgents->first()->sales_agent_id);
        $this->assertTrue($customer->assignedSalesAgents->first()->is_admin_assigned);
        
        // Inverse
        $this->assertCount(1, $salesAgent->assignedCustomers);
        $this->assertEquals($customer->id, $salesAgent->assignedCustomers->first()->customer_id);
    }

    public function test_customer_can_be_assigned_a_sales_agent_alt_user()
    {
        // 1. Create Customer
        $customer = User::factory()->create();

        // 2. Create Sales Agent (AltUser)
        $salesAgent = AltUser::create([
            'name' => 'Alt Agent',
            'email' => 'alt@example.com',
            'password' => 'password',
            'role' => Role::SALES,
        ]);

        // 3. Assign
        $customer->assignedSalesAgents()->create([
            'sales_agent_id' => $salesAgent->id,
            'sales_agent_type' => AltUser::class,
            'is_admin_assigned' => false,
        ]);

        // 4. Assertions
        $this->assertCount(1, $customer->assignedSalesAgents);
        $this->assertEquals($salesAgent->id, $customer->assignedSalesAgents->first()->sales_agent_id);
        $this->assertEquals(AltUser::class, $customer->assignedSalesAgents->first()->sales_agent_type);
        
        // Inverse
        $this->assertCount(1, $salesAgent->assignedCustomers);
        $this->assertEquals($customer->id, $salesAgent->assignedCustomers->first()->customer_id);
    }
}