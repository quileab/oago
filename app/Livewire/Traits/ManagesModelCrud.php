<?php

namespace App\Livewire\Traits;

use Mary\Traits\Toast;
use Illuminate\Validation\ValidationException;

trait ManagesModelCrud
{
    use Toast;

    // Properties to be defined by the component using this trait
    // public $model; // The actual model instance (e.g., User $user or AltUser $altUser)
    // protected string $modelClass; // The class name of the model (e.g., User::class)
    // protected string $successMessage = 'Registro guardado exitosamente.';
    // protected string $redirectRoute; // The route to redirect to after saving

    // This method will be called by the component's mount method
    // It expects the component to have a public property named $model
    // and a protected $modelClass property.
    protected function initializeModel($id = null)
    {
        if ($id) {
            $this->model = $this->modelClass::findOrFail($id);
        } else {
            $this->model = new $this->modelClass();
        }
    }

    public function save()
    {
        try {
            $this->validate(); // Assumes validation rules are defined in the component

            $this->model->save();

            $this->success($this->successMessage, position: 'toast-bottom');

            if (isset($this->redirectRoute)) {
                $this->redirect($this->redirectRoute, navigate: true);
            }
        } catch (ValidationException $e) {
            $this->error('Error de validación.', position: 'toast-bottom');
            throw $e; // Re-throw to display validation errors
        } catch (\Exception $e) {
            $this->error('Ocurrió un error al guardar.', position: 'toast-bottom');
            // Log the error if necessary
        }
    }
}
