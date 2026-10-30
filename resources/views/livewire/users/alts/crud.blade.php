<?php

use App\Livewire\Traits\ManagesModelCrud;
use App\Models\AltUser;
use Livewire\Volt\Component;

new class extends Component
{
    use ManagesModelCrud;

    protected string $modelClass = AltUser::class;

    protected string $redirectRoute = '/alts';

    protected string $successMessage = 'Usuario Alternativo guardado correctamente.';

    public function mount($id = null): void
    {
        $this->initializeUserForm($id, allowCreate: true);
    }
}; ?>

@include('livewire.users.partials.crud-form')
