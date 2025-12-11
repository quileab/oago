<?php

namespace App\Livewire;

use Livewire\Component;

use App\Models\AltUser;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class WebNavbar extends Component
{
    public $trial_days_remaining = null;
    public $salesCustomers = [];
    public $actingAsId = null;
    public $actingAsName = null;
    public $searchCustomer = '';

    public function mount()
    {
        $user = Auth::user();
        if (!$user && Auth::guard('alt')->check()) {
            $user = Auth::guard('alt')->user();
        }

        if ($user && $user->role->value === 'sales') {
            // Auto-assign if not set
            if (!session('sales_acting_as_customer_id')) {
                 $first = $user->assignedCustomers()->where('is_active', true)->first();
                 if ($first) {
                     session(['sales_acting_as_customer_id' => $first->customer_id]);
                 }
            }

            $this->actingAsId = session('sales_acting_as_customer_id');
             if ($this->actingAsId) {
                 $actingUser = User::find($this->actingAsId);
                 $this->actingAsName = $actingUser ? $actingUser->full_name : 'Unknown';
             }
             
             $this->loadCustomers();
        }

        if ($user && $user->role->value === 'guest') {
            $guest = AltUser::where('email', $user->email)->first();
            if ($guest) {
                $created_date = $guest->created_at;
                $end_date = $created_date->copy()->addDays(10);
                $this->trial_days_remaining = floor(max(0, now()->diffInDays($end_date, false)));
                $expiration_date = $guest->created_at->addDays(10);
                if (now()->isAfter($expiration_date)) {
                    // logout
                    Auth::guard('alt')->logout();
                    return;
                }
            }
        }
    }

    public function updatedSearchCustomer()
    {
        $this->loadCustomers();
    }

    public function loadCustomers()
    {
        $user = Auth::user() ?? Auth::guard('alt')->user();
        if ($user && $user->role->value === 'sales') {
            $query = $user->assignedCustomers()
               ->where('is_active', true)
               ->with('customer');
            
            if ($this->searchCustomer) {
                $query->whereHas('customer', function($q) {
                    $q->where('name', 'like', '%' . $this->searchCustomer . '%')
                      ->orWhere('lastname', 'like', '%' . $this->searchCustomer . '%');
                });
            }
            $this->salesCustomers = $query->take(10)->get()->pluck('customer');
        }
    }

    public function setActingCustomer($id) {
        $user = Auth::user() ?? Auth::guard('alt')->user();
        
        if ($id) {
            // Verify ownership
            $valid = $user->assignedCustomers()
                ->where('customer_id', $id)
                ->where('is_active', true)
                ->exists();
            if ($valid) {
                 session(['sales_acting_as_customer_id' => $id]);
            }
        } else {
            session()->forget('sales_acting_as_customer_id');
        }
        session()->forget('cart'); // Clear cart on switch to avoid price conflicts
        $this->redirect(request()->header('Referer'));
    }

    public function render()
    {
        return view('livewire.web-navbar');
    }
}
