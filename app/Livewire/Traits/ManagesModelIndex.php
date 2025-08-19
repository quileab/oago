<?php

namespace App\Livewire\Traits;

// NOTE: This trait assumes the component is using `Mary\Traits\Toast`
// and `Livewire\WithPagination`.
trait ManagesModelIndex
{
    public $search = '';

    protected string $deleteSuccessMessage = 'Registro eliminado.';

    public function delete($id): void
    {
        $this->modelClass::destroy($id);
        $this->success($this->deleteSuccessMessage, position: 'toast-bottom');
    }

    public function getPaginatedData($paginate = 20)
    {
        $query = $this->modelClass::query();

        if ($this->search && !empty($this->searchableColumns)) {
            $query->where(function ($q) {
                foreach ($this->searchableColumns as $column) {
                    $q->orWhere($column, 'like', '%' . $this->search . '%');
                }
            });
        }

        // Order by role descending
        if (property_exists($this, 'modelClass') && $this->modelClass === \App\Models\GuestUser::class) {
            $query->orderBy('role', 'desc');
        }

        return $query->paginate($paginate);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }
}
