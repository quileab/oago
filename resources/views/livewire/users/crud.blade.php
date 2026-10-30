<?php

use App\Livewire\Traits\ManagesModelCrud;
use App\Models\User;
use Livewire\Volt\Component;

new class extends Component
{
    use ManagesModelCrud;

    protected string $modelClass = User::class;

    protected string $redirectRoute = '/users';

    protected string $successMessage = 'Usuario guardado correctamente.';

    public function mount($id = null): void
    {
        $this->initializeUserForm($id, allowCreate: false);
    }
}; ?>

@include('livewire.users.partials.crud-form')
