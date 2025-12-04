<?php

use Livewire\Volt\Component;
use App\Models\User;
use Mary\Traits\Toast;
use Livewire\Attributes\Rule;
use Livewire\Attributes\Title;
use Livewire\Attributes\Layout;

new #[Layout('components.layouts.app')]
    #[Title('Actualización Masiva de Roles')]
    class extends Component {
        use Toast;

        #[Rule('required')]
        public string $userIds = '';

        #[Rule('required|in:admin,customer,none,other')]
        public string $newRole = 'customer';

        public array $results = [];

        public function updateRoles()
        {
            $this->validate();

            $ids = array_filter(array_map('trim', explode("\n", $this->userIds)));

            $this->results = [];

            foreach ($ids as $id) {
                $user = User::find($id);

                if (!$user) {
                    $this->results[] = ['id' => $id, 'status' => 'Error', 'message' => 'Usuario no encontrado'];
                    continue;
                }

                $oldRole = $user->role;
                $user->role = $this->newRole;
                $user->save();

                $this->results[] = [
                    'id' => $id,
                    'status' => 'Éxito',
                    'message' => "Rol actualizado de '{$oldRole}' a '{$this->newRole}'",
                ];
            }

            $this->success('Roles actualizados masivamente.', position: 'toast-bottom');
        }
    };
?>

<div>
    <x-header title="Actualización Masiva de Roles" separator />

    <x-card title="Pegar IDs de Usuarios" shadow class="mb-4">
        <x-textarea
            label="IDs de Usuarios (uno por línea)"
            wire:model="userIds"
            rows="10"
            placeholder="Un ID por línea."
            />
        @error('userIds')
            <span class="error">{{ $message }}</span>
        @enderror

        <x-select
            label="Nuevo Rol"
            wire:model="newRole"
            :options="[['name' => 'customer'],['name' => 'admin'],['name' => 'none'],['name' => 'other']]"
            option-value="name"
            />
        @error('newRole')
            <span class="error">{{ $message }}</span>
        @enderror

        <x-slot:actions>
            <x-button
                label="Actualizar Roles"
                icon="o-check"
                class="btn-primary"
                wire:click="updateRoles"
                spinner="updateRoles"
                />
        </x-slot:actions>
    </x-card>

    @if (!empty($results))
        <x-card title="Resultados de la Operación" shadow>
            @foreach ($results as $result)
                <div class="mb-2">
                    <strong>ID:</strong> {{ $result['id'] }} - 
                    <strong>Estado:</strong> {{ $result['status'] }} - 
                    <strong>Mensaje:</strong> {{ $result['message'] }}
                </div>
            @endforeach
        </x-card>
    @endif
</div>
