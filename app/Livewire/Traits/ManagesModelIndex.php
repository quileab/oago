<?php

namespace App\Livewire\Traits;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

trait ManagesModelIndex
{
    public string $search = '';

    protected string $deleteSuccessMessage = 'Registro eliminado.';

    public function delete(int|string $id): void
    {
        $this->modelClass::destroy($id);
        $this->success($this->deleteSuccessMessage, position: 'toast-bottom');
    }

    public function getPaginatedRecords(array $sortBy = ['column' => 'id', 'direction' => 'asc'], int $paginate = 20): LengthAwarePaginator
    {
        return $this->modelClass::query()
            ->when(
                $this->search,
                fn ($q) => $q->where(DB::raw('concat(name, " ", lastname, " ", email)'), 'like', "%{$this->search}%")
            )
            ->orderBy(...array_values($sortBy))
            ->paginate($paginate);
    }

    public function getPaginatedData(int $paginate = 20): LengthAwarePaginator
    {
        return $this->getPaginatedRecords($this->sortBy ?? ['column' => 'id', 'direction' => 'asc'], $paginate);
    }

    public function updated(mixed $property): void
    {
        if (! is_array($property) && $property !== '') {
            $this->resetPage();
        }
    }
}
