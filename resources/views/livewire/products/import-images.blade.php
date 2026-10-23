<?php

use App\Services\ImageImportService;
use Livewire\Volt\Component;
use Mary\Traits\Toast;

new class extends Component
{
    use Toast;

    public string $folder = 'imagenes_productos';

    public string $field = 'sku';

    public string $separator = '_';

    public int $pad_length = 0;

    public bool $delete_source = false;

    public bool $overwrite = true;

    public int $limit = 0;

    public array $scanSummary = [
        'total_files' => 0,
        'matched_count' => 0,
        'unmatched_count' => 0,
    ];

    public array $previewItems = [];

    public array $importStatuses = [];

    public ?array $lastResult = null;

    public bool $isProcessing = false;

    public int $currentIndex = 0;

    public int $importedCount = 0;

    public int $skippedCount = 0;

    public int $errorCount = 0;

    public function mount(ImageImportService $service): void
    {
        $prefs = $service->getPreferences();

        $this->folder = $prefs['folder'] ?? 'imagenes_productos';
        $this->field = $prefs['field'] ?? 'sku';
        $this->separator = $prefs['separator'] ?? '_';
        $this->pad_length = (int) ($prefs['pad_length'] ?? 0);
        $this->delete_source = (bool) ($prefs['delete_source'] ?? false);
        $this->overwrite = (bool) ($prefs['overwrite'] ?? true);
        $this->limit = (int) ($prefs['limit'] ?? 0);

        $this->refreshScan($service);
    }

    public function refreshScan(ImageImportService $service): void
    {
        $scan = $service->scan($this->folder, [
            'field' => $this->field,
            'separator' => $this->separator,
            'pad_length' => $this->pad_length,
        ]);

        $this->scanSummary = [
            'total_files' => $scan['total_files'],
            'matched_count' => $scan['matched_count'],
            'unmatched_count' => $scan['unmatched_count'],
        ];

        // Guardamos todos los ítems para el procesamiento por chunks/batches reactivo
        $this->previewItems = $scan['items'];
    }

    public function updated($property, ImageImportService $service): void
    {
        if (in_array($property, ['folder', 'field', 'separator', 'pad_length', 'delete_source', 'overwrite', 'limit'])) {
            $service->savePreferences([
                'folder' => $this->folder,
                'field' => $this->field,
                'separator' => $this->separator,
                'pad_length' => $this->pad_length,
                'delete_source' => $this->delete_source,
                'overwrite' => $this->overwrite,
                'limit' => $this->limit,
            ]);

            if (in_array($property, ['folder', 'field', 'separator', 'pad_length'])) {
                $this->importStatuses = [];
                $this->refreshScan($service);
            }
        }
    }

    public function startBatchImport(ImageImportService $service): void
    {
        $this->isProcessing = true;
        $this->currentIndex = 0;
        $this->importedCount = 0;
        $this->skippedCount = 0;
        $this->errorCount = 0;
        $this->importStatuses = [];

        $service->savePreferences([
            'folder' => $this->folder,
            'field' => $this->field,
            'separator' => $this->separator,
            'pad_length' => $this->pad_length,
            'delete_source' => $this->delete_source,
            'overwrite' => $this->overwrite,
            'limit' => $this->limit,
        ]);

        $this->processNextItem($service);
    }

    public function processNextItem(ImageImportService $service): void
    {
        $maxItems = $this->limit > 0 ? min($this->limit, count($this->previewItems)) : count($this->previewItems);

        if ($this->currentIndex >= $maxItems) {
            $this->isProcessing = false;
            $this->lastResult = [
                'processed' => $this->currentIndex,
                'imported' => $this->importedCount,
                'skipped' => $this->skippedCount,
                'errors' => $this->errorCount,
                'details' => [],
            ];
            $this->success("¡Importación completada con éxito! {$this->importedCount} convertidas a WebP.");

            return;
        }

        $item = $this->previewItems[$this->currentIndex];
        $filename = $item['filename'];

        $result = $service->importSingleItem($item, [
            'overwrite' => $this->overwrite,
            'delete_source' => $this->delete_source,
        ]);

        $status = $result['status'];
        $this->importStatuses[$filename] = [
            'status' => $status,
            'message' => $result['message'],
        ];

        if ($status === 'imported') {
            $this->importedCount++;
        } elseif ($status === 'skipped') {
            $this->skippedCount++;
        } else {
            $this->errorCount++;
        }

        $this->currentIndex++;

        // Si faltan ítems, despachar llamada para el siguiente ítem (evita timeouts de 60s/300s en el servidor web)
        if ($this->currentIndex < $maxItems) {
            $this->dispatch('import-next-step');
        } else {
            $this->isProcessing = false;
            $this->lastResult = [
                'processed' => $this->currentIndex,
                'imported' => $this->importedCount,
                'skipped' => $this->skippedCount,
                'errors' => $this->errorCount,
                'details' => [],
            ];
            $this->success("¡Importación completada! {$this->importedCount} procesadas correctamente.");
        }
    }
}; ?>

<div class="p-4 md:p-6 space-y-6 max-w-7xl mx-auto">
    {{-- Header --}}
    <x-header title="Importador de Imágenes de Productos" 
              subtitle="Convierte e incorpora imágenes de hosting a WebP con galería automática" 
              separator progress-indicator>
        <x-slot:actions>
            <x-button label="Volver a Productos" icon="o-arrow-left" link="/products" class="btn-ghost" />
            <x-button label="Actualizar Escaneo" icon="o-arrow-path" wire:click="refreshScan" class="btn-outline" />
        </x-slot:actions>
    </x-header>

    {{-- Tarjetas de Estadísticas --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <x-stat title="Archivos Detectados" 
                :value="$scanSummary['total_files']" 
                icon="o-photo" 
                description="En la carpeta origen" 
                class="bg-base-100 shadow border border-base-200" />

        <x-stat title="Con Coincidencia en Catálogo" 
                :value="$scanSummary['matched_count']" 
                icon="o-check-circle" 
                description="Listos para vincular e importar" 
                class="bg-base-100 text-success shadow border border-base-200" />

        <x-stat title="Sin Coincidencia" 
                :value="$scanSummary['unmatched_count']" 
                icon="o-exclamation-triangle" 
                description="Código no encontrado en BD" 
                class="bg-base-100 text-warning shadow border border-base-200" />
    </div>

    {{-- Formulario de Configuración y Panel de Control --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-1 space-y-4">
            <x-card title="Parámetros de Importación" separator shadow class="bg-base-100">
                <div class="space-y-4">
                    <x-input label="Carpeta Origen (en public/)" 
                             wire:model.live.debounce.400ms="folder" 
                             prefix="public/" 
                             hint="Ruta donde se suben las imágenes (ej. imagenes_productos)" />

                    <x-select label="Campo Identificador" 
                              wire:model.live="field" 
                              :options="[
                                  ['id' => 'sku', 'name' => 'SKU (Recomendado)'],
                                  ['id' => 'barcode', 'name' => 'Código de Barras (Barcode)'],
                                  ['id' => 'id', 'name' => 'ID de Producto (Interno)'],
                                  ['id' => 'product_type', 'name' => 'Tipo de Producto (product_type)']
                              ]" />

                    <x-select label="Separador de Índice" 
                              wire:model.live="separator" 
                              :options="[
                                  ['id' => '_', 'name' => 'Guion bajo ( _ ) ej: SKU_1.jpg'],
                                  ['id' => '-', 'name' => 'Guion medio ( - ) ej: SKU-1.jpg'],
                                  ['id' => 'none', 'name' => 'Sin separador']
                              ]" 
                              hint="El índice 1 es la portada, 2 a 5 van a la galería" />

                    <x-input label="Relleno Numérico (str_pad)" 
                             wire:model.live.debounce.400ms="pad_length" 
                             type="number" 
                             min="0" 
                             max="12" 
                             hint="Longitud fija con ceros a la izquierda (0 para desactivar)" />

                    <div class="divider text-xs uppercase opacity-50">Opciones de Ejecución</div>

                    <x-checkbox label="Eliminar imágenes origen tras importar" 
                                wire:model="delete_source" 
                                hint="Mantener desmarcado para conservar los JPG originales en la carpeta" 
                                tight />

                    <x-checkbox label="Sobrescribir imágenes existentes" 
                                wire:model="overwrite" 
                                hint="Reemplaza la imagen WebP si ya fue importada previamente" 
                                tight />

                    <x-input label="Límite por tanda (0 = todas)" 
                             wire:model="limit" 
                             type="number" 
                             min="0" 
                             hint="Útil para probar con lotes pequeños" />

                    <div class="pt-4">
                        @if($isProcessing)
                            <div class="space-y-2 p-3 bg-primary/10 border border-primary/20 rounded-xl">
                                <div class="flex items-center justify-between text-xs font-semibold text-primary">
                                    <span>Importando en tiempo real...</span>
                                    <span>{{ $currentIndex }} / {{ $limit > 0 ? min($limit, count($previewItems)) : count($previewItems) }}</span>
                                </div>
                                <progress class="progress progress-primary w-full" 
                                          value="{{ $currentIndex }}" 
                                          max="{{ $limit > 0 ? min($limit, count($previewItems)) : count($previewItems) }}"></progress>
                                <p class="text-[11px] opacity-70 text-center">Procesando imagen por imagen para evitar bloqueos del servidor.</p>
                            </div>
                        @else
                            <x-button label="Iniciar Importación a WebP" 
                                      icon="o-arrow-down-tray" 
                                      wire:click="startBatchImport" 
                                      class="btn-primary w-full" 
                                      :disabled="$scanSummary['total_files'] === 0 || $scanSummary['matched_count'] === 0" />
                        @endif
                    </div>
                </div>
            </x-card>
        </div>

        {{-- Resultados de Última Ejecución o Previsualización --}}
        <div class="lg:col-span-2 space-y-4">
            @if($lastResult)
                <x-card title="Resultados de la Última Importación" separator shadow class="bg-base-100 border-2 border-primary/20">
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4 text-center">
                        <div class="p-3 bg-base-200/50 rounded-xl">
                            <div class="text-xs opacity-60">Procesados</div>
                            <div class="text-2xl font-bold">{{ $lastResult['processed'] }}</div>
                        </div>
                        <div class="p-3 bg-success/10 text-success rounded-xl">
                            <div class="text-xs opacity-80">Importados a WebP</div>
                            <div class="text-2xl font-bold">{{ $lastResult['imported'] }}</div>
                        </div>
                        <div class="p-3 bg-warning/10 text-warning rounded-xl">
                            <div class="text-xs opacity-80">Omitidos</div>
                            <div class="text-2xl font-bold">{{ $lastResult['skipped'] }}</div>
                        </div>
                        <div class="p-3 bg-error/10 text-error rounded-xl">
                            <div class="text-xs opacity-80">Errores</div>
                            <div class="text-2xl font-bold">{{ $lastResult['errors'] }}</div>
                        </div>
                    </div>

                    <div class="max-h-60 overflow-y-auto space-y-1 text-xs font-mono">
                        @foreach(array_slice($lastResult['details'], -30) as $detail)
                            <div class="flex items-center justify-between p-2 rounded {{ $detail['status'] === 'imported' ? 'bg-success/5 text-success' : ($detail['status'] === 'error' ? 'bg-error/10 text-error' : 'bg-base-200/40 text-base-content/70') }}">
                                <span class="truncate max-w-xs">{{ $detail['filename'] }}</span>
                                <span class="font-semibold">{{ $detail['message'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </x-card>
            @endif

            {{-- Previsualización de Archivos y Matching --}}
            <x-card title="Previsualización de Archivos Detectados" separator shadow class="bg-base-100">
                @if(count($previewItems) > 0)
                    <div class="overflow-x-auto">
                        <table class="table table-sm w-full">
                            <thead>
                                <tr>
                                    <th>Estado</th>
                                    <th>Archivo</th>
                                    <th>Identificador Extraído</th>
                                    <th>Tipo de Imagen</th>
                                    <th>Matching en Catálogo</th>
                                    <th>Tamaño</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($previewItems as $item)
                                    @php
                                        $fName = $item['filename'];
                                        $itemStatus = $importStatuses[$fName]['status'] ?? null;
                                        $statusMsg = $importStatuses[$fName]['message'] ?? null;
                                        
                                        $rowClass = match($itemStatus) {
                                            'imported' => 'bg-success/15 border-l-4 border-success text-success-content transition-colors duration-300',
                                            'skipped' => 'bg-warning/10 border-l-4 border-warning transition-colors duration-300',
                                            'error' => 'bg-error/15 border-l-4 border-error text-error-content transition-colors duration-300',
                                            default => 'hover:bg-base-200/40'
                                        };
                                    @endphp
                                    <tr class="{{ $rowClass }}">
                                        <td>
                                            @if($itemStatus === 'imported')
                                                <span class="badge badge-success text-[11px] gap-1 font-semibold">
                                                    <x-icon name="o-check" class="w-3.5 h-3.5" />
                                                    WebP OK
                                                </span>
                                            @elseif($itemStatus === 'skipped')
                                                <span class="badge badge-warning text-[11px] gap-1 font-medium" title="{{ $statusMsg }}">
                                                    <x-icon name="o-forward" class="w-3.5 h-3.5" />
                                                    Omitido
                                                </span>
                                            @elseif($itemStatus === 'error')
                                                <span class="badge badge-error text-[11px] gap-1 font-semibold" title="{{ $statusMsg }}">
                                                    <x-icon name="o-x-mark" class="w-3.5 h-3.5" />
                                                    Error
                                                </span>
                                            @elseif($isProcessing && ($currentIndex < count($previewItems)) && ($previewItems[$currentIndex]['filename'] === $fName))
                                                <span class="badge badge-info text-[11px] gap-1 animate-pulse font-semibold">
                                                    <span class="loading loading-spinner loading-xs"></span>
                                                    Procesando...
                                                </span>
                                            @else
                                                <span class="badge badge-ghost text-[11px] opacity-60">Pendiente</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="flex items-center gap-2">
                                                <img src="{{ $item['relative_url'] }}" 
                                                     alt="preview" 
                                                     class="w-10 h-10 object-cover rounded shadow-sm border border-base-300" 
                                                     onerror="this.src='/imgs/fallback.webp'" />
                                                <span class="font-mono text-xs font-medium">{{ $item['filename'] }}</span>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge badge-outline font-mono">{{ $item['parsed']['identifier'] }}</span>
                                        </td>
                                        <td>
                                            @if($item['parsed']['index'] === 1)
                                                <span class="badge badge-primary text-xs">Portada (Ppal)</span>
                                            @else
                                                <span class="badge badge-ghost text-xs">Galería #{{ $item['parsed']['index'] - 1 }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($item['is_matched'])
                                                <div class="flex flex-col">
                                                    <span class="text-xs font-semibold text-success flex items-center gap-1">
                                                        <x-icon name="o-check-circle" class="w-4 h-4" />
                                                        Producto #{{ $item['product_id'] }}
                                                    </span>
                                                    <span class="text-[11px] opacity-70 truncate max-w-xs">{{ $item['product_description'] }}</span>
                                                </div>
                                            @else
                                                <span class="badge badge-warning text-xs flex items-center gap-1">
                                                    <x-icon name="o-x-circle" class="w-3.5 h-3.5" />
                                                    No Encontrado
                                                </span>
                                            @endif
                                        </td>
                                        <td class="text-xs font-mono opacity-60">
                                            {{ $item['filesize_formatted'] }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @if($scanSummary['total_files'] > 50)
                        <div class="text-xs text-center opacity-60 mt-3">
                            Mostrando los primeros 50 archivos de un total de {{ $scanSummary['total_files'] }}.
                        </div>
                    @endif
                @else
                    <div class="text-center py-12 text-base-content/60">
                        <x-icon name="o-photo" class="w-12 h-12 mx-auto mb-2 opacity-30" />
                        <p class="font-medium">No se detectaron imágenes en <code>public/{{ $folder }}</code>.</p>
                        <p class="text-xs opacity-70 mt-1">Sube archivos a esa carpeta y pulsa "Actualizar Escaneo".</p>
                    </div>
                @endif
            </x-card>
        </div>
    </div>

    {{-- Listener en Alpine para avanzar paso a paso sin timeout HTTP --}}
    <div x-data x-on:import-next-step.window="$wire.processNextItem()"></div>
</div>
