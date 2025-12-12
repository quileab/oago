<?php

namespace App\Livewire;

use Livewire\Component;

use App\Models\AltUser;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use App\Enums\Role; // Importar el Enum Role
use Mary\Traits\Toast;

class WebNavbar extends Component
{
    use Toast;

    public $trial_days_remaining = null;
    public $salesCustomers = [];
    public $actingAsId = null;
    public $actingAsName = null;
    public $searchCustomer = '';

    public function mount()
    {
        $loggedInUser = Auth::user();
        if (!$loggedInUser && Auth::guard('alt')->check()) {
            $loggedInUser = Auth::guard('alt')->user();
        }

        if ($loggedInUser && $loggedInUser->role === Role::SALES) { // Usar el enum
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

        if ($loggedInUser && $loggedInUser->role === Role::GUEST) { // Usar el enum
            $guest = AltUser::where('email', $loggedInUser->email)->first();
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
        $loggedInUser = Auth::user() ?? Auth::guard('alt')->user();
        if ($loggedInUser && $loggedInUser->role === Role::SALES) { // Usar el enum
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
                // Si el cliente actual no está en la lista de los que puede manejar el vendedor, 
                // o si la lista ha cambiado y ya no lo puede ver, desasigna.
                if (!$loggedInUser->getManagedCustomersQuery()->where('id', $this->actingAsId)->exists()) {
                     session()->forget('sales_acting_as_customer_id');
                     $this->actingAsId = null;
                     $this->actingAsName = null;
                } else {
                     $this->actingAsName = $actingUser ? $actingUser->full_name : 'Unknown';
                }
            }
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
