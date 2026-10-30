<?php

namespace App\Livewire\Traits;

use App\Enums\Role;
use App\Helpers\SettingsHelper;
use App\Mail\AltUserWelcomeMail;
use App\Models\AltUser;
use App\Models\ListName;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Mary\Traits\Toast;

trait ManagesModelCrud
{
    use Toast;

    public string $newPassword = '';

    public array $formData = [];

    public string $createdAtDate = '';

    public function isAltUser(): bool
    {
        return $this->modelClass === AltUser::class;
    }

    protected function initializeUserForm(int|string|null $id = null, bool $allowCreate = false): void
    {
        if ($id) {
            $this->formData = $this->modelClass::findOrFail($id)->toArray();
            if ($this->isAltUser()) {
                $this->createdAtDate = ! empty($this->formData['created_at'])
                    ? Carbon::parse($this->formData['created_at'])->format('Y-m-d')
                    : now()->format('Y-m-d');
            }

            return;
        }

        if (! $allowCreate) {
            $this->redirect($this->redirectRoute, navigate: true);

            return;
        }

        $defaultListSetting = (int) SettingsHelper::settings('alt_user_default_price', 0);
        $defaultListId = ($defaultListSetting && ListName::where('id', $defaultListSetting)->exists())
            ? $defaultListSetting
            : null;

        $this->formData = [
            'name' => '',
            'lastname' => '',
            'address' => '',
            'city' => '',
            'postal_code' => '',
            'phone' => '',
            'email' => '',
            'role' => Role::CUSTOMER->value,
            'list_id' => $defaultListId,
            'is_internal' => false,
        ];

        if ($this->isAltUser()) {
            $this->createdAtDate = now()->format('Y-m-d');
        }
    }

    #[Computed]
    public function roles(): array
    {
        return array_map(fn ($role) => ['id' => $role->value, 'name' => $role->value], Role::cases());
    }

    #[Computed]
    public function priceLists(): array
    {
        return ListName::all()->map(fn ($l) => ['id' => $l->id, 'name' => $l->name])->toArray();
    }

    protected function rules(): array
    {
        $table = (new $this->modelClass)->getTable();
        $ignoreId = isset($this->formData['id']) ? (int) $this->formData['id'] : null;

        return array_merge(
            $this->modelClass::getProfileRules($table, $ignoreId, 'formData.'),
            [
                'formData.list_id' => 'nullable|exists:list_names,id',
                'formData.role' => 'required|string',
                'formData.is_internal' => 'boolean',
            ]
        );
    }

    protected function messages(): array
    {
        return array_merge(
            $this->modelClass::getProfileMessages('formData.'),
            [
                'formData.list_id.exists' => 'La lista de precios seleccionada no es válida.',
                'formData.role.required' => 'El rol es requerido.',
            ]
        );
    }

    public function save(): void
    {
        $validated = $this->validate($this->rules(), $this->messages());

        $data = $validated['formData'];
        $data['list_id'] = ! empty($data['list_id']) ? (int) $data['list_id'] : null;

        $recordId = $this->formData['id'] ?? null;

        if ($recordId) {
            $this->modelClass::findOrFail($recordId)->update($data);
        } else {
            $data['password'] = Str::random(16);
            $this->modelClass::create($data);
        }

        $this->success($this->successMessage ?? 'Registro guardado correctamente.', position: 'toast-bottom');
        $this->redirect($this->redirectRoute, navigate: true);
    }

    public function changePassword(): void
    {
        $this->validate(
            ['newPassword' => 'required|min:6'],
            [
                'newPassword.required' => 'Debe ingresar una nueva clave.',
                'newPassword.min' => 'La clave debe tener al menos 6 caracteres.',
            ]
        );

        $user = $this->modelClass::findOrFail($this->formData['id']);
        $user->update(['password' => $this->newPassword]);

        $this->newPassword = '';
        $this->success('Clave actualizada.', position: 'toast-bottom');
    }

    public function delete(): void
    {
        $this->modelClass::findOrFail($this->formData['id'])->delete();
        $this->success('Usuario eliminado.', position: 'toast-bottom');
        $this->redirect($this->redirectRoute, navigate: true);
    }

    public function resetDate(): void
    {
        if (! $this->isAltUser()) {
            return;
        }

        $this->validate(['createdAtDate' => 'required|date']);

        $user = AltUser::findOrFail($this->formData['id']);
        $user->created_at = $this->createdAtDate;
        $user->save();
        $this->createdAtDate = $user->created_at->format('Y-m-d');
        $this->success('Fecha de creación reiniciada.', position: 'toast-bottom');
    }

    public function sendWelcomeEmail(): void
    {
        if (! $this->isAltUser()) {
            return;
        }

        $user = AltUser::findOrFail($this->formData['id']);
        $user->created_at = now();
        $user->updated_at = $user->created_at;
        $user->role = Role::GUEST;

        $password = Str::random(8);
        $user->password = $password;

        $activationToken = Str::random(60);
        $user->activation_token = $activationToken;
        $user->save();

        Mail::to($user->email)->queue(new AltUserWelcomeMail($user, $password, $activationToken));
        $this->success('Correo de bienvenida enviado.', position: 'toast-bottom');
    }
}
