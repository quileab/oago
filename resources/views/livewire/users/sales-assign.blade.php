<?php

use Livewire\Volt\Component;
use App\Models\User;
use App\Models\AltUser;
use App\Models\CustomerSalesAgent;
use App\Enums\Role;
use Mary\Traits\Toast;

new class extends Component {
    use Toast;

    public User $user;
    public $assignedAgents = [];
    public $availableAgents = [];
    public $selectedAgentId = '';
    public bool $isAdminAssigned = true;

    public function mount($id)
    {
        $this->user = User::findOrFail($id);
        $this->loadData();
    }

    public $searchQuery = '';
    public $selectedAgentName = '';

    public function loadData()
    {
        $this->assignedAgents = $this->user->assignedSalesAgents()
            ->with('salesAgent')
            ->get();
    }

    public function updatedSearchQuery($value)
    {
        if (strlen($value) < 2) {
             $this->availableAgents = [];
             return;
        }

        $users = User::where('role', Role::SALES)
            ->where(function($q) use ($value) {
                $q->where('name', 'like', "%$value%")
                  ->orWhere('lastname', 'like', "%$value%");
            })
            ->take(5)
            ->get()
            ->toBase()
            ->map(function($u) {
                return ['id' => 'user_' . $u->id, 'name' => 'User: ' . $u->full_name];
            });
        
        $altUsers = AltUser::where('role', Role::SALES)
            ->where(function($q) use ($value) {
                $q->where('name', 'like', "%$value%")
                  ->orWhere('lastname', 'like', "%$value%");
            })
            ->take(5)
            ->get()
            ->toBase()
            ->map(function($u) {
                 return ['id' => 'alt_' . $u->id, 'name' => 'Alt: ' . $u->full_name];
            });
        
        $this->availableAgents = $users->merge($altUsers)->all();
    }

    public function selectAgent($id, $name)
    {
        $this->selectedAgentId = $id;
        $this->selectedAgentName = $name;
        $this->searchQuery = ''; 
        $this->availableAgents = [];
    }

    public function addAgent()
    {
        if (!$this->selectedAgentId) {
            $this->error('Seleccione un agente');
            return;
        }

        $parts = explode('_', $this->selectedAgentId);
        $type = $parts[0];
        $id = $parts[1];

        $modelType = $type === 'user' ? User::class : AltUser::class;

        $exists = CustomerSalesAgent::where('customer_id', $this->user->id)
            ->where('sales_agent_id', $id)
            ->where('sales_agent_type', $modelType)
            ->exists();

        if ($exists) {
            $this->error('El agente ya está asignado');
            return;
        }

        CustomerSalesAgent::create([
            'customer_id' => $this->user->id,
            'sales_agent_id' => $id,
            'sales_agent_type' => $modelType,
            'is_admin_assigned' => $this->isAdminAssigned,
            'is_active' => true
        ]);

        $this->success('Agente asignado');
        $this->loadData();
        $this->selectedAgentId = '';
        $this->selectedAgentName = '';
    }

    public function removeAgent($id)
    {
        CustomerSalesAgent::destroy($id);
        $this->success('Agente removido');
        $this->loadData();
    }
}; ?>

<div>
    <x-header title="Asignar Vendedores" />

    <x-card title="Agregar Agente a Cliente: {{ $user->full_name }}" shadow separator class="mb-4">
        <div class="flex gap-2 items-end">
            <div class="w-full md:w-1/2 relative">
                <x-input label="Buscar Agente" wire:model.live.debounce.300ms="searchQuery" icon="o-magnifying-glass" placeholder="Escriba nombre..." />
                
                @if(count($availableAgents) > 0)
                    <div class="absolute z-10 w-full bg-base-100 border border-base-300 rounded-md mt-1 shadow-lg max-h-60 overflow-y-auto">
                        @foreach($availableAgents as $agent)
                            <div class="p-2 hover:bg-base-200 cursor-pointer border-b border-base-200 last:border-0" 
                                 wire:click="selectAgent('{{ $agent['id'] }}', '{{ $agent['name'] }}')">
                                {{ $agent['name'] }}
                            </div>
                        @endforeach
                    </div>
                @endif

                @if($selectedAgentName)
                    <div class="text-sm text-success mt-1 font-bold">Seleccionado: {{ $selectedAgentName }}</div>
                @endif
            </div>

            <x-checkbox label="Asignado por Admin" wire:model="isAdminAssigned" class="pb-2" />
            <x-button label="Asignar" icon="o-plus" wire:click="addAgent" class="btn-success" />
        </div>
    </x-card>

    <x-card title="Agentes Asignados" shadow separator>
        @foreach($assignedAgents as $agent)
            <x-list-item :item="$agent" no-hover>
                <x-slot:value>
                    {{ $agent->salesAgent->full_name ?? 'Unknown' }}
                    <span class="text-xs text-gray-500">({{ class_basename($agent->sales_agent_type) }})</span>
                </x-slot:value>
                <x-slot:sub-value>
                    Asignado por Admin: <x-badge :value="$agent->is_admin_assigned ? 'Sí' : 'No'" class="{{ $agent->is_admin_assigned ? 'badge-info' : 'badge-ghost' }}" />
                    | 
                    Activo: <x-badge :value="$agent->is_active ? 'Sí' : 'No'" class="{{ $agent->is_active ? 'badge-success' : 'badge-warning' }}" />
                </x-slot:sub-value>
                <x-slot:actions>
                    <x-button icon="o-trash" wire:click="removeAgent({{ $agent->id }})" class="btn-error btn-sm" confirm="¿Eliminar asignación?" />
                </x-slot:actions>
            </x-list-item>
        @endforeach
        
        @if($assignedAgents->isEmpty())
            <div class="text-center text-gray-500 py-4">No hay agentes asignados.</div>
        @endif
    </x-card>
</div>
