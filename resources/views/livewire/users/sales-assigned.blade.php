<?php

use Livewire\Volt\Component;
use Illuminate\Support\Facades\Auth;
use Mary\Traits\Toast;
use App\Models\CustomerSalesAgent;

new class extends Component {
    use Toast;

    public $assignedAgents = [];

    public function mount()
    {
        $this->loadData();
    }

    public function loadData()
    {
        $user = Auth::user();
        if ($user && $user instanceof \App\Models\User) {
            $this->assignedAgents = $user->assignedSalesAgents()
                ->with('salesAgent')
                ->get();
        }
    }

    public function toggleActive($id)
    {
        $agent = CustomerSalesAgent::where('id', $id)
            ->where('customer_id', Auth::id())
            ->firstOrFail();
        
        $agent->is_active = !$agent->is_active;
        $agent->save();
        
        $this->success('Estado actualizado');
        $this->loadData();
    }
}; ?>

<div>
    <x-header title="Mis Vendedores Asignados" separator />

    <x-card shadow separator>
        @foreach($assignedAgents as $agent)
            <x-list-item :item="$agent" no-hover>
                <x-slot:value>
                    {{ $agent->salesAgent->full_name ?? 'Unknown' }}
                </x-slot:value>
                <x-slot:sub-value>
                    Origen: {{ $agent->is_admin_assigned ? 'Administrador' : 'Solicitud' }}
                </x-slot:sub-value>
                <x-slot:actions>
                    <x-button 
                        :label="$agent->is_active ? 'Deshabilitar' : 'Habilitar'"
                        :class="$agent->is_active ? 'btn-warning btn-sm' : 'btn-success btn-sm'"
                        wire:click="toggleActive({{ $agent->id }})" 
                    />
                </x-slot:actions>
            </x-list-item>
        @endforeach

        @if($assignedAgents->isEmpty())
            <div class="text-center text-gray-500 py-4">No tiene vendedores asignados.</div>
        @endif
    </x-card>
</div>
