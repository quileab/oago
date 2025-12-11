<?php

use App\Enums\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Computed;
use Livewire\Volt\Component;
use Mary\Traits\Toast;

new class extends Component {
    use Toast;

    public bool $drawer = false;
    public string $newPassword = '';
    public array $formData = [];

    public function mount($id = null): void
    {
        if ($id) {
            $this->formData = User::findOrFail($id)->toArray();
        } else {
            $this->formData = [
                'name' => '',
                'lastname' => '',
                'address' => '',
                'city' => '',
                'postal_code' => '',
                'phone' => '',
                'email' => '',
                'password' => '',
                'role' => 'customer',
                'list_id' => 0,
            ];
        }
    }

    #[Computed]
    public function roles(): array
    {
        return array_map(fn($role) => ['id' => $role->value, 'name' => $role->value], Role::cases());
    }

    public function save(): void
    {
        $validated = $this->validate([
            'formData.name' => 'required|string|max:255',
            'formData.lastname' => 'required|string|max:255',
            'formData.address' => 'required|string|max:255',
            'formData.city' => 'required|string|max:255',
            'formData.postal_code' => 'required|string|max:20',
            'formData.phone' => 'required|string|max:50',
            'formData.email' => 'required|email|max:255',
            'formData.role' => 'required',
            'formData.list_id' => 'nullable|integer',
        ]);

        User::updateOrCreate(
            ['id' => $this->formData['id'] ?? null],
            $validated['formData']
        );

        $this->success('Usuario guardado correctamente.', position: 'toast-bottom');
        $this->redirect('/users', navigate: true);
    }

    public function changePassword(): void
    {
        $this->validate(['newPassword' => 'required|min:6']);
        
        $user = User::findOrFail($this->formData['id']);
        $user->update(['password' => Hash::make($this->newPassword)]);
        
        $this->newPassword = '';
        $this->drawer = false;
        $this->success('Clave actualizada.', position: 'toast-bottom');
    }

    public function delete(): void
    {
        User::findOrFail($this->formData['id'])->delete();
        $this->redirect('/users');
    }
}; ?>

<div>
    <x-card title="Usuario" shadow separator>
        <x-slot:menu>
            <x-button @click="$wire.drawer = true" responsive icon="o-ellipsis-vertical" class="btn-ghost btn-circle btn-outline btn-sm" />
        </x-slot:menu>

        <x-form wire:submit="save">
            <!-- Estructura Grid Simplificada -->
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-6">
                <div class="lg:col-span-3">
                    <x-input label="Apellido" wire:model="formData.lastname" icon="o-user" />
                </div>
                <div class="lg:col-span-3">
                    <x-input label="Nombre/s" wire:model="formData.name" icon="o-user" />
                </div>

                <div class="lg:col-span-3">
                    <x-input label="Dirección" wire:model="formData.address" icon="o-map-pin" />
                </div>
                <div class="lg:col-span-2">
                    <x-input label="Ciudad" wire:model="formData.city" icon="o-map-pin" />
                </div>
                <div class="lg:col-span-1">
                    <x-input label="C.P." wire:model="formData.postal_code" icon="o-hashtag" />
                </div>

                <div class="lg:col-span-3">
                    <x-input label="Teléfono" wire:model="formData.phone" icon="o-phone" />
                </div>
                <div class="lg:col-span-3">
                    <x-input label="E-mail" wire:model="formData.email" icon="o-envelope" />
                </div>

                <div class="lg:col-span-3">
                    <x-select label="Rol" icon="o-queue-list" :options="$this->roles" option-value="id" option-label="name" wire:model="formData.role" />
                </div>
                <div class="lg:col-span-3">
                    <x-input label="Lista de Precios" wire:model="formData.list_id" type="number" icon="o-numbered-list" />
                </div>
            </div>

            <x-slot:actions>
                <div class="flex flex-1 justify-between">
                    @if(isset($formData['role']) && $formData['role'] === 'customer' && isset($formData['id']))
                        <x-button link="/users/{{ $formData['id'] }}/sales-assign" icon="o-users" label="Asignar Vendedores" class="btn-primary mr-1" />
                    @endif
                    <x-button label="Guardar" icon="o-check" class="btn-primary" type="submit" spinner="save" />
                </div>
            </x-slot:actions>
        </x-form>
    </x-card>

    <!-- DRAWER -->
    <x-drawer wire:model="drawer" title="Acciones" right separator with-close-button close-on-escape class="lg:w-1/3">
        <x-input inline label="Nueva Clave" wire:model="newPassword" type="password" icon="o-key">
            <x-slot:append>
                <x-button label="Cambiar" icon="o-check" class="btn-primary rounded-s-none" wire:click="changePassword" spinner="changePassword" />
            </x-slot:append>
        </x-input>

        <x-slot:actions>
            <x-dropdown label="Eliminar Usuario" class="btn-error w-full mt-4">
                <x-menu-item title="Confirmar" wire:click="delete" spinner="delete" icon="o-trash" class="text-red-500" />
            </x-dropdown>
        </x-slot:actions>
    </x-drawer>
</div>