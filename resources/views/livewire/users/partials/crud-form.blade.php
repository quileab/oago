<div>
    <x-card :title="$this->isAltUser() ? 'Usuario Alternativo' : 'Usuario'" shadow separator class="mb-4">
        <x-form wire:submit="save">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-6">
                <div class="lg:col-span-3">
                    <x-input label="Apellido" wire:model="formData.lastname" icon="o-user" maxlength="30" />
                </div>
                <div class="lg:col-span-3">
                    <x-input label="Nombre/s" wire:model="formData.name" icon="o-user" maxlength="30" />
                </div>

                <div class="lg:col-span-3">
                    <x-input label="Dirección" wire:model="formData.address" icon="o-map-pin" maxlength="100" />
                </div>
                <div class="lg:col-span-2">
                    <x-input label="Ciudad" wire:model="formData.city" icon="o-map-pin" maxlength="30" />
                </div>
                <div class="lg:col-span-1">
                    <x-input label="C.P." wire:model="formData.postal_code" icon="o-hashtag" maxlength="10" />
                </div>

                <div class="lg:col-span-3">
                    <x-input label="Teléfono" wire:model="formData.phone" icon="o-phone" maxlength="50" />
                </div>
                <div class="lg:col-span-3">
                    <x-input label="E-mail" wire:model="formData.email" icon="o-envelope" />
                </div>

                <div class="lg:col-span-2">
                    <x-select label="Rol" icon="o-queue-list" :options="$this->roles" option-value="id" option-label="name" wire:model="formData.role" />
                </div>
                <div class="lg:col-span-2">
                    <x-select label="Lista de Precios" icon="o-queue-list" wire:model="formData.list_id" :options="$this->priceLists" option-value="id" option-label="name" placeholder="Sin lista (precio base)" />
                </div>
                <div class="lg:col-span-2 flex items-center pt-4">
                    <x-toggle label="Personal Interno" wire:model="formData.is_internal" hint="Acceso a todos los clientes" class="toggle-primary" />
                </div>
            </div>

            <x-slot:actions>
                <div class="flex flex-1 justify-between">
                    @if(!$this->isAltUser() && isset($formData['role']) && $formData['role'] === 'customer' && !empty($formData['id']))
                        <x-button link="/users/{{ $formData['id'] }}/sales-assign" icon="o-users" label="Asignar Vendedores" class="btn-primary mr-1" />
                    @else
                        <div></div>
                    @endif
                    <x-button label="Guardar" icon="o-check" class="btn-primary" type="submit" spinner="save" />
                </div>
            </x-slot:actions>
        </x-form>
    </x-card>

    @if(!empty($formData['id']))
        <x-card title="Acciones" shadow separator>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <x-input label="Password" wire:model="newPassword" type="password" icon="o-key">
                        <x-slot:append>
                            <x-button label="Cambiar Clave" icon="o-check" class="btn-primary join-item" wire:click="changePassword" spinner="changePassword" />
                        </x-slot:append>
                    </x-input>
                </div>

                <div>
                    <x-dropdown label="Eliminar Usuario" class="btn-error w-full mt-8">
                        <x-menu-item title="Confirmar" wire:click="delete" spinner="delete" icon="o-trash" class="text-red-500" />
                    </x-dropdown>
                </div>

                @if($this->isAltUser())
                    <div class="md:col-span-2 border-t border-base-200 pt-4 mt-2 grid grid-cols-1 gap-4 md:grid-cols-3 items-center">
                        <div class="md:col-span-1">
                            <x-input label="RESET Fecha" type="date" wire:model="createdAtDate">
                                <x-slot:append>
                                    <x-button label="RESET" icon="o-clock" wire:click="resetDate" class="join-item btn-primary" spinner="resetDate" />
                                </x-slot:append>
                            </x-input>
                        </div>

                        <div class="md:col-span-2 flex flex-col sm:flex-row items-center justify-between w-full border border-gray-300/50 rounded-md p-3 gap-2">
                            <p class="text-sm">Enviar correo de bienvenida con <br><b>· contraseña autogenerada · rol: invitado · reseteo de fecha</b>.</p>
                            <x-button label="Enviar" icon="o-envelope" wire:click="sendWelcomeEmail" spinner="sendWelcomeEmail" class="btn-primary" />
                        </div>
                    </div>
                @endif
            </div>
        </x-card>
    @endif
</div>
