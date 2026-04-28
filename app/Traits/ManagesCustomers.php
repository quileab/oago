<?php

namespace App\Traits;

use App\Enums\Role;
use App\Models\CustomerSalesAgent;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait ManagesCustomers
{
    /**
     * Get the customers assigned to this sales agent.
     */
    public function assignedCustomers(): MorphMany
    {
        return $this->morphMany(CustomerSalesAgent::class, 'sales_agent');
    }

    /**
     * Get the query for customers managed by this user/agent.
     */
    public function getManagedCustomersQuery(): Builder
    {
        if ($this->is_internal || $this->role === Role::ADMIN) {
            return User::where('role', Role::CUSTOMER);
        }

        return User::whereHas('assignedSalesAgents', function ($query) {
            $query->where('sales_agent_id', $this->id)
                ->where('sales_agent_type', self::class);
        });
    }
}
