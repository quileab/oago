<?php

use Livewire\Volt\Component;
use Illuminate\Support\Facades\File;
use Mary\Traits\Toast;

new class extends Component {
    use Toast;

    public string $logType = 'laravel'; // 'laravel' or 'api'
    public ?string $selectedFile = null; // Para logs tipo API (ej: api-2026-03-30.log)
    public int $lines = 100;
    public string $search = '';

    public function mount()
    {
        $this->setDefaultFile();
    }

    public function updatedLogType()
    {
        $this->setDefaultFile();
    }

    protected function setDefaultFile()
    {
        if ($this->logType === 'api') {
            $files = $this->getApiLogFiles();
            $this->selectedFile = !empty($files) ? $files[0]['id'] : null;
        } else {
            $this->selectedFile = 'laravel.log';
        }
    }

    public function getApiLogFiles(): array
    {
        $files = File::glob(storage_path('logs/api-*.log'));
        
        return collect($files)
            ->map(fn($file) => [
                'id' => basename($file),
                'name' => basename($file)
            ])
            ->sortByDesc('id')
            ->values()
            ->toArray();
    }

    public function getLogsProperty()
    {
        if (!$this->selectedFile) {
            return [['text' => "No se ha seleccionado ningún archivo.", 'type' => 'info']];
        }

        $path = storage_path('logs/' . $this->selectedFile);

        if (!File::exists($path)) {
            return [['text' => "El archivo no existe ($this->selectedFile).", 'type' => 'error']];
        }

        $content = File::get($path);
        $lines = explode("\n", $content);
        $lines = array_filter($lines);

        if ($this->search) {
            $lines = array_filter($lines, fn($line) => str_contains(strtolower($line), strtolower($this->search)));
        }

        $lines = array_reverse($lines);
        $lines = array_slice($lines, 0, $this->lines);

        return collect($lines)->map(function($line) {
            $upperLine = strtoupper($line);
            $type = 'info';
            if (str_contains($upperLine, 'ERROR') || str_contains($upperLine, 'CRITICAL') || str_contains($upperLine, 'EMERGENCY')) {
                $type = 'error';
            } elseif (str_contains($upperLine, 'WARNING') || str_contains($upperLine, 'ALERT')) {
                $type = 'warning';
            } elseif (str_contains($upperLine, 'SUCCESS') || str_contains($upperLine, 'INFO')) {
                $type = 'success';
            }
            
            return [
                'text' => $line,
                'type' => $type
            ];
        })->toArray();
    }

    public function download()
    {
        if (!$this->selectedFile) return;
        
        $path = storage_path('logs/' . $this->selectedFile);

        if (!File::exists($path)) {
            $this->error("El archivo no existe.");
            return;
        }

        return response()->download($path);
    }

    public function clear()
    {
        if (!$this->selectedFile) return;

        $path = storage_path('logs/' . $this->selectedFile);

        if (File::exists($path)) {
            File::put($path, '');
            $this->success("Archivo $this->selectedFile limpiado.");
        }
    }
}; ?>

<div>
    <x-header title="Logs del Sistema" subtitle="Visualiza los registros de la aplicación y la API" separator>
        <x-slot:actions>
            <x-button label="Descargar" icon="o-arrow-down-tray" wire:click="download" class="btn-primary" :disabled="!$selectedFile" />
            <x-button label="Limpiar" icon="o-trash" wire:click="clear" confirm="¿Estás seguro de que deseas limpiar este archivo?" class="btn-ghost text-error" :disabled="!$selectedFile" />
        </x-slot:actions>
    </x-header>

    <div class="grid gap-5 mb-8 lg:grid-cols-4">
        <x-choices
            label="Tipo de Log"
            wire:model.live="logType"
            :options="[
                ['id' => 'laravel', 'name' => 'Laravel (General)'],
                ['id' => 'api', 'name' => 'API Activity']
            ]"
            single
        />

        @if($logType === 'api')
            <x-select
                label="Archivo de API"
                wire:model.live="selectedFile"
                :options="$this->getApiLogFiles()"
                placeholder="Seleccione un día..."
            />
        @endif

        <x-input label="Buscar" wire:model.live.debounce.500ms="search" icon="o-magnifying-glass" placeholder="Filtrar por texto..." />

        <x-select
            label="Líneas a mostrar"
            wire:model.live="lines"
            :options="[
                ['id' => 50, 'name' => '50 líneas'],
                ['id' => 100, 'name' => '100 líneas'],
                ['id' => 200, 'name' => '200 líneas'],
                ['id' => 500, 'name' => '500 líneas']
            ]"
        />
    </div>

    <x-card class="bg-neutral text-neutral-content font-mono text-xs overflow-x-auto">
        <div class="max-h-[600px] overflow-y-auto p-2 whitespace-nowrap">
            @if(empty($this->logs) || (count($this->logs) === 1 && $this->logs[0]['type'] === 'info' && str_contains($this->logs[0]['text'], 'No se ha seleccionado')))
                <div class="text-center py-10 opacity-50">
                    No se encontraron registros o no hay archivo seleccionado.
                </div>
            @else
                @foreach($this->logs as $log)
                    <div @class([
                        'py-0.5',
                        'text-red-400' => $log['type'] === 'error',
                        'text-yellow-400' => $log['type'] === 'warning',
                        'text-green-400' => $log['type'] === 'success',
                        'text-gray-300' => $log['type'] === 'info',
                    ])>
                        {{ $log['text'] }}
                    </div>
                @endforeach
            @endif
        </div>
    </x-card>
</div>
