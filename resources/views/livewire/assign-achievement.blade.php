<?php

use Livewire\Volt\Component;
use Mary\Traits\Toast;
use App\Models\User;
use App\Models\AltUser;
use App\Models\Achievement;

new class extends Component {
    use Toast;

    public $selectedUserType = 'user';
    public $selectedUserId;
    public $selectedAchievementId;

    public function assignAchievement()
    {
        $this->validate([
            'selectedUserType' => 'required|in:user,alt_user',
            'selectedUserId' => 'required|numeric',
            'selectedAchievementId' => 'required|numeric',
        ]);

        $achievement = Achievement::findOrFail($this->selectedAchievementId);

        if ($this->selectedUserType === 'user') {
            $user = User::findOrFail($this->selectedUserId);
            $user->achievements()->attach($achievement);
        } else {
            $user = AltUser::findOrFail($this->selectedUserId);
            $user->achievements()->attach($achievement);
        }

        $this->success('Logro asignado correctamente.', position: 'toast-bottom');

        // Reset form fields
        $this->selectedUserId = null;
        $this->selectedAchievementId = null;
    }

    public function users()
    {
        return User::all();
    }

    public function altUsers()
    {
        return AltUser::all();
    }

    public function achievements()
    {
        return Achievement::all();
    }

    public function with(): array
    {
        return [
            'users' => $this->users(),
            'altUsers' => $this->altUsers(),
            'achievements' => $this->achievements(),
        ];
    }
}; ?>

<div>
    <x-card title="Asignar Logro" shadow separator>
        <x-form wire:submit="assignAchievement">
            <x-select label="Tipo de Usuario" :options="[['name' => 'Usuario', 'id' => 'user'], ['name' => 'Usuario Alternativo', 'id' => 'alt_user']]" option-value="id" option-label="name" wire:model.live="selectedUserType" />

            @if($selectedUserType === 'user')
                <x-select label="Usuario" :options="$users" option-value="id" option-label="name" wire:model="selectedUserId" />
            @else
                <x-select label="Usuario Alternativo" :options="$altUsers" option-value="id" option-label="name" wire:model="selectedUserId" />
            @endif

            <x-select label="Logro" :options="$achievements" option-value="id" option-label="name" wire:model="selectedAchievementId" />

            <x-slot:actions>
                <x-button label="Asignar" icon="o-plus" class="btn-primary" type="submit" spinner="assignAchievement" />
            </x-slot:actions>
        </x-form>
    </x-card>
</div>