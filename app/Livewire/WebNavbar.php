<?php

namespace App\Livewire;

use Livewire\Component;

use App\Models\AltUser;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Collection;
use App\Enums\Role;
use Mary\Traits\Toast;

class WebNavbar extends Component
{
    use Toast;

    public $trial_days_remaining = null;
    public Collection $salesCustomers;
    public $actingAsId = null;
    public $actingAsName = null;
    public $searchCustomer = '';

    public function mount()
    {
        $this->salesCustomers = collect();
        $loggedInUser = Auth::user();
        if (!$loggedInUser && Auth::guard('alt')->check()) {
            $loggedInUser = Auth::guard('alt')->user();
        }

        if ($loggedInUser && $loggedInUser->role === Role::SALES) {
            // Auto-assign if not set
            if (!session('sales_acting_as_customer_id')) {
                 $firstCustomer = $loggedInUser->getManagedCustomersQuery()->first();
                 if ($firstCustomer) {
                     session(['sales_acting_as_customer_id' => $firstCustomer->id]);
                 }
            }

            $this->actingAsId = session('sales_acting_as_customer_id');
             if ($this->actingAsId) {
                 $actingUser = User::find($this->actingAsId);
                 $this->actingAsName = $actingUser ? $actingUser->full_name : 'Unknown';
             }
             
             $this->loadCustomers();
        }

        // Usar current_user() para la lógica de trial si es necesario, 
        // pero aquí mantenemos la lógica específica de AltUser si aplica.
        if ($loggedInUser && $loggedInUser->role === Role::GUEST) {
            $guest = AltUser::where('email', $loggedInUser->email)->first();
            if ($guest) {
                $expirationDays = \App\Helpers\SettingsHelper::settings('guest_access_ttl_days', 10);
                $end_date = $guest->created_at->copy()->addDays($expirationDays);
                $this->trial_days_remaining = floor(max(0, now()->diffInDays($end_date, false)));
            }
        }
    }

    public function updatedSearchCustomer()
    {
        $this->loadCustomers();
    }

    public function loadCustomers()
    {
        $loggedInUser = Auth::user() ?? Auth::guard('alt')->user();
        if ($loggedInUser && $loggedInUser->role === Role::SALES) {
            $customerQuery = $loggedInUser->getManagedCustomersQuery();
            
            if ($this->searchCustomer) {
                $customerQuery->where(function ($q) {
                    $q->where('name', 'like', '%' . $this->searchCustomer . '%')
                      ->orWhere('lastname', 'like', '%' . $this->searchCustomer . '%');
                });
            }
            $this->salesCustomers = $customerQuery->take(10)->get();

            if ($this->actingAsId) {
                $actingUser = User::find($this->actingAsId);
                if (!$loggedInUser->getManagedCustomersQuery()->where('id', $this->actingAsId)->exists()) {
                     session()->forget('sales_acting_as_customer_id');
                     $this->actingAsId = null;
                     $this->actingAsName = null;
                } else {
                     $this->actingAsName = $actingUser ? $actingUser->full_name : 'Unknown';
                }
            }
        } else {
            $this->salesCustomers = collect();
        }
    }


    public function setActingCustomer($id) {
        $loggedInUser = Auth::user() ?? Auth::guard('alt')->user();
        
        if ($id) {
            // Verify ownership using the new method
            $valid = $loggedInUser->getManagedCustomersQuery()
                ->where('id', $id)
                ->exists();

            if ($valid) {
                 session(['sales_acting_as_customer_id' => $id]);
            } else {
                session()->forget('sales_acting_as_customer_id');
                $this->warning('No tiene acceso a este cliente.');
                return;
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
