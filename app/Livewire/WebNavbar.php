<?php

namespace App\Livewire;

use Livewire\Component;

use App\Models\GuestUser;
use Illuminate\Support\Facades\Auth;

class WebNavbar extends Component
{
    public $trial_days_remaining = null;

    public function mount()
    {
        $user = Auth::user();
        if ($user && $user->role === 'guest') {
            $guest = GuestUser::where('email', $user->email)->first();
            if ($guest) {
                $created_date = $guest->created_at;
                $end_date = $created_date->copy()->addDays(10);
                $this->trial_days_remaining = floor(max(0, now()->diffInDays($end_date, false)));
                $expiration_date = $guest->created_at->addDays(10);
                if (now()->isAfter($expiration_date)) {
                    // logout
                    Auth::guard('guest')->logout();
                    return;
                }
            }
        }
    }

    public function render()
    {
        return view('livewire.web-navbar');
    }
}
