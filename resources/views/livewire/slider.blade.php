<?php

use Livewire\Volt\Component;
use Illuminate\Support\Facades\Storage;
use Livewire\WithFileUploads;
use Mary\Traits\Toast;
use Illuminate\Support\Str;

new class extends Component {
    use WithFileUploads, Toast;

    public $selectedImageFile;
    public $editModal = false;
    public $editingSlide = [
        'id' => '',
        'title' => '',
        'description' => '',
        'url' => '',
        'urlText' => ''
    ];

    // Configuración general del slider
    public $config = [
        'autoplay' => true,
        'interval' => 5000,
        'withoutArrows' => false,
        'withoutIndicators' => false
    ];

    private $sliderPath = 'slider';
    private $jsonFile = 'slider/slider.json';

    public function mount()
    {
        $disk = Storage::disk('public');
        if ($disk->exists($this->jsonFile)) {
            $data = json_decode($disk->get($this->jsonFile), true);
            if (isset($data['config'])) {
                $this->config = array_merge($this->config, $data['config']);
            }
        }
    }

    public function saveConfig()
    {
        $disk = Storage::disk('public');
        $data = $disk->exists($this->jsonFile) ? json_decode($disk->get($this->jsonFile), true) : ['slides' => []];

        // Si el JSON viejo era un array plano o de slides, normalizar
        if (!isset($data['slides'])) {
            $data = ['slides' => $data, 'config' => []];
        }

        $data['config'] = $this->config;
        $disk->put($this->jsonFile, json_encode($data, JSON_PRETTY_PRINT));
        $this->success('Configuración general actualizada.');
    }

    public function getImagesProperty()
    {
        $disk = Storage::disk('public');

        if (!$disk->exists($this->jsonFile)) {
            $this->generateJsonFromFiles();
        }

        $data = json_decode($disk->get($this->jsonFile), true);

        // Normalización de estructura nueva/vieja
        $files_in_json = isset($data['slides']) ? $data['slides'] : $data;

        $files_on_disk = $disk->files($this->sliderPath);
        $files_on_disk_basename = array_map('basename', $files_on_disk);

        // Filter out files from JSON that are no longer on disk
        $existing_files = array_filter($files_in_json, function($file) use ($files_on_disk_basename) {
            $path = is_array($file) ? $file['id'] : $file;
            return in_array(basename($path), $files_on_disk_basename);
        });

        // If there's a mismatch, update the JSON file
        if (count($existing_files) !== count($files_in_json)) {
            if (isset($data['slides'])) {
                $data['slides'] = array_values($existing_files);
            } else {
                $data = array_values($existing_files);
            }
            $disk->put($this->jsonFile, json_encode($data, JSON_PRETTY_PRINT));
            $files_in_json = $existing_files;
        }

        return collect($files_in_json)->map(function($f) use ($disk) {
            $id = is_array($f) ? $f['id'] : $f;
            return [
                'id' => $id,
                'image_url' => asset('storage/' . $id) . '?v=' . ($disk->exists($id) ? $disk->lastModified($id) : time()),
                'name' => basename($id),
                'title' => is_array($f) ? ($f['title'] ?? '') : '',
                'description' => is_array($f) ? ($f['description'] ?? '') : '',
                'url' => is_array($f) ? ($f['url'] ?? '') : '',
                'urlText' => is_array($f) ? ($f['urlText'] ?? '') : '',
            ];
        });
    }

    public function edit($id)
    {
        $disk = Storage::disk('public');
        $data = json_decode($disk->get($this->jsonFile), true);
        $slides = isset($data['slides']) ? $data['slides'] : $data;

        $slide = collect($slides)->first(function($f) use ($id) {
            $path = is_array($f) ? $f['id'] : $f;
            return $path === $id;
        });

        if ($slide) {
            $this->editingSlide = [
                'id' => is_array($slide) ? $slide['id'] : $slide,
                'title' => is_array($slide) ? ($slide['title'] ?? '') : '',
                'description' => is_array($slide) ? ($slide['description'] ?? '') : '',
                'url' => is_array($slide) ? ($slide['url'] ?? '') : '',
                'urlText' => is_array($slide) ? ($slide['urlText'] ?? '') : '',
            ];
            $this->editModal = true;
        }
    }

    public function save()
    {
        $disk = Storage::disk('public');
        $data = json_decode($disk->get($this->jsonFile), true);
        $slides = isset($data['slides']) ? $data['slides'] : $data;

        $newSlides = collect($slides)->map(function($f) {
            $path = is_array($f) ? $f['id'] : $f;
            if ($path === $this->editingSlide['id']) {
                return $this->editingSlide;
            }
            return $f;
        })->toArray();

        if (isset($data['slides'])) {
            $data['slides'] = array_values($newSlides);
        } else {
            $data = array_values($newSlides);
        }

        $disk->put($this->jsonFile, json_encode($data, JSON_PRETTY_PRINT));
        $this->editModal = false;
        $this->success('Imagen actualizada.');
    }

    public function uploadImage()
    {
        $this->validate([
            'selectedImageFile' => 'required|image|max:1024',
        ]);

        $disk = Storage::disk('public');
        $ext = $this->selectedImageFile->getClientOriginalExtension();
        $filename = time() . '_' . Str::random(10) . '.' . $ext;

        $path = $this->selectedImageFile->storeAs($this->sliderPath, $filename, 'public');

        $this->addImageToJson($path);

        $this->reset('selectedImageFile');
        $this->success('Imagen subida con éxito.');
    }

    public function delete($path)
    {
        Storage::disk('public')->delete($path);
        $this->removeImageFromJson($path);
        $this->success('Imagen eliminada.');
    }

    public function reorderImages($orderedItems)
    {
        $disk = Storage::disk('public');
        $data = json_decode($disk->get($this->jsonFile), true);
        $slides = isset($data['slides']) ? $data['slides'] : $data;
        $orderedIds = collect($orderedItems)->pluck('value')->toArray();

        $newOrder = [];
        foreach ($orderedIds as $id) {
            $slide = collect($slides)->first(function($f) use ($id) {
                return (is_array($f) ? $f['id'] : $f) === $id;
            });
            if ($slide) {
                $newOrder[] = $slide;
            }
        }

        if (isset($data['slides'])) {
            $data['slides'] = $newOrder;
        } else {
            $data = $newOrder;
        }

        $disk->put($this->jsonFile, json_encode($data, JSON_PRETTY_PRINT));
        $this->success('Imágenes reordenadas.');
    }

    private function generateJsonFromFiles()
    {
        $disk = Storage::disk('public');
        $files = $disk->files($this->sliderPath);

        // Exclude slider.json itself
        $imageFiles = array_filter($files, fn($file) => basename($file) !== 'slider.json');

        // Sort by old naming convention if present
        usort($imageFiles, function ($a, $b) {
            preg_match('/slide \((\\d+)\)/', basename($a), $aMatch);
            preg_match('/slide \((\\d+)\)/', basename($b), $bMatch);
            return ($aMatch[1] ?? PHP_INT_MAX) <=> ($bMatch[1] ?? PHP_INT_MAX);
        });

        $disk->put($this->jsonFile, json_encode(['slides' => array_values($imageFiles), 'config' => $this->config], JSON_PRETTY_PRINT));
    }

    private function addImageToJson($path)
    {
        $disk = Storage::disk('public');
        $data = json_decode($disk->get($this->jsonFile), true);

        $newSlide = ['id' => $path, 'title' => '', 'description' => '', 'url' => '', 'urlText' => ''];

        if (isset($data['slides'])) {
            $data['slides'][] = $newSlide;
        } else {
            // Migrar a nueva estructura si era un array plano
            $data = ['slides' => $data, 'config' => $this->config];
            $data['slides'][] = $newSlide;
        }

        $disk->put($this->jsonFile, json_encode($data, JSON_PRETTY_PRINT));
    }

    private function removeImageFromJson($path)
    {
        $disk = Storage::disk('public');
        $data = json_decode($disk->get($this->jsonFile), true);
        $slides = isset($data['slides']) ? $data['slides'] : $data;

        $newSlides = array_filter($slides, function($image) use ($path) {
            $currentPath = is_array($image) ? $image['id'] : $image;
            return $currentPath !== $path;
        });

        if (isset($data['slides'])) {
            $data['slides'] = array_values($newSlides);
        } else {
            $data = array_values($newSlides);
        }

        $disk->put($this->jsonFile, json_encode($data, JSON_PRETTY_PRINT));
    }
};
?>

<div x-data="{}" x-init="$nextTick(() => {
    new window.Sortable($refs.imageList, {
        animation: 200,
        handle: '.handle', // Drag handle
        onEnd: function (evt) {
            const orderedIds = Array.from(evt.to.children).map(item => ({ value: item.dataset.id }));
            @this.call('reorderImages', orderedIds);
        },
    });
})">
    <x-header title="Administrar Slider" subtitle="Sube, elimina, ordena y configura el carrusel." separator />

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-8 mb-8">
        <!-- Lado izquierdo: Configuración General -->
        <div class="lg:col-span-1">
            <x-card title="Configuración" shadow separator class="bg-base-100">
                <div class="space-y-4">
                    <x-checkbox label="Autoplay" wire:model="config.autoplay" hint="Cambio automático" />
                    <x-input label="Intervalo (ms)" type="number" wire:model="config.interval" step="500" hint="Ej: 5000 = 5 seg" />
                    <x-checkbox label="Sin Flechas" wire:model="config.withoutArrows" />
                    <x-checkbox label="Sin Indicadores" wire:model="config.withoutIndicators" />
                </div>
                <x-slot:actions>
                    <x-button label="Guardar Configuración" class="btn-primary btn-sm w-full" icon="o-check" wire:click="saveConfig" spinner="saveConfig" />
                </x-slot:actions>
            </x-card>

            <x-form wire:submit="uploadImage" class="mt-4 p-4 bg-base-100 rounded-lg shadow-md border border-base-200">
                <x-file wire:model="selectedImageFile" label="Subir Nueva Imagen" accept="image/*" />
                @error('selectedImageFile')
                    <span class="text-red-500 text-sm">{{ $message }}</span>
                @enderror
                @if($selectedImageFile)
                    <x-button type="submit" class="btn-primary mt-4 w-full" spinner="uploadImage">Subir Imagen</x-button>
                @endif
            </x-form>
        </div>

        <!-- Lado derecho: Listado de Imágenes -->
        <div class="lg:col-span-3">
            <div class="relative">
                <div wire:loading.flex wire:target="reorderImages" class="absolute inset-0 bg-white bg-opacity-75 z-10 items-center justify-center" style="display: none;">
                    <div class="text-center">
                        <x-icon name="o-arrow-path" class="w-8 h-8 animate-spin mx-auto" />
                        <p>Reordenando imágenes...</p>
                    </div>
                </div>
                <div x-ref="imageList" class="grid grid-cols-1 md:grid-cols-2 gap-4" wire:loading.class="opacity-50" wire:target="reorderImages">
                    @foreach($this->images as $image)
                        <div wire:key="{{ $image['id'] }}" data-id="{{ $image['id'] }}"
                            class="relative group bg-base-200 rounded-lg shadow-md overflow-hidden border border-base-300">
                            <img src="{{ $image['image_url'] }}" class="w-full h-48 object-cover" alt="Slider Image">
                            <div
                                class="absolute inset-0 bg-black bg-opacity-50 flex flex-col items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-300 gap-2">
                                <div class="flex gap-2">
                                    <button class="handle cursor-grab text-white p-2 bg-blue-500 rounded-full hover:scale-110 transition-transform">
                                        <x-icon name="o-arrows-pointing-out" class="w-6 h-6" />
                                    </button>
                                    <button wire:click="edit('{{ $image['id'] }}')"
                                        class="bg-amber-500 text-white p-2 rounded-full hover:scale-110 transition-transform">
                                        <x-icon name="o-pencil" class="w-6 h-6" />
                                    </button>
                                    <button wire:click="delete('{{ $image['id'] }}')"
                                        wire:confirm="¿Estás seguro de que quieres eliminar esta imagen?"
                                        class="bg-red-500 text-white p-2 rounded-full hover:scale-110 transition-transform">
                                        <x-icon name="o-trash" class="w-6 h-6" />
                                    </button>
                                </div>
                            </div>
                            <div class="absolute bottom-2 left-2 text-white text-xs bg-black bg-opacity-70 px-2 py-1 rounded max-w-[90%] truncate">
                                @if($image['title'])
                                    <span class="font-bold block text-blue-300">{{ $image['title'] }}</span>
                                @endif
                                {{ $image['name'] }}
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <x-modal wire:model="editModal" title="Editar Texto del Slide" class="backdrop-blur">
        <div class="grid gap-4">
            <img src="{{ asset('storage/' . $editingSlide['id']) }}" class="w-full h-48 object-cover rounded-lg border border-base-300 shadow-inner" /> 
            <x-input label="Título" wire:model="editingSlide.title" placeholder="Título que aparece en grande" />   
            <x-textarea label="Descripción" wire:model="editingSlide.description" placeholder="Texto descriptivo inferior" rows="2" />
            <div class="grid grid-cols-2 gap-4">
                <x-input label="Texto del Botón" wire:model="editingSlide.urlText" placeholder="Ej: Ver Más" />     
                <x-input label="Enlace del Botón" wire:model="editingSlide.url" placeholder="Ej: /products?tag=oferta" />
            </div>
        </div>
        <x-slot:actions>
            <x-button label="Cancelar" @click="$wire.editModal = false" />
            <x-button label="Guardar" class="btn-primary" wire:click="save" spinner="save" />
        </x-slot:actions>
    </x-modal>
</div>
