<?php

use Livewire\Volt\Component;
use Illuminate\Support\Facades\Storage;
use Livewire\WithFileUploads;
use Mary\Traits\Toast;
use Illuminate\Support\Str;

new class extends Component {
    use WithFileUploads, Toast;

    public $selectedImageFile;
    private $sliderPath = 'slider';
    private $jsonFile = 'slider/slider.json';

    public function getImagesProperty()
    {
        $disk = Storage::disk('public');

        if (!$disk->exists($this->jsonFile)) {
            $this->generateJsonFromFiles();
        }

        $files_in_json = json_decode($disk->get($this->jsonFile), true);
        $files_on_disk = $disk->files($this->sliderPath);
        $files_on_disk_basename = array_map('basename', $files_on_disk);

        // Filter out files from JSON that are no longer on disk
        $existing_files = array_filter($files_in_json, function($file) use ($files_on_disk_basename) {
            return in_array(basename($file), $files_on_disk_basename);
        });

        // If there's a mismatch, update the JSON file
        if (count($existing_files) !== count($files_in_json)) {
            $disk->put($this->jsonFile, json_encode(array_values($existing_files), JSON_PRETTY_PRINT));
            $files_in_json = $existing_files;
        }

        return collect($files_in_json)->map(fn($f) => [
            'id' => $f,
            'url' => asset('storage/' . $f) . '?v=' . $disk->lastModified($f),
            'name' => basename($f),
        ]);
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
        $newOrder = collect($orderedItems)->pluck('value')->toArray();
        $disk->put($this->jsonFile, json_encode($newOrder, JSON_PRETTY_PRINT));
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

        $disk->put($this->jsonFile, json_encode(array_values($imageFiles), JSON_PRETTY_PRINT));
    }

    private function addImageToJson($path)
    {
        $disk = Storage::disk('public');
        $images = json_decode($disk->get($this->jsonFile), true);
        $images[] = $path;
        $disk->put($this->jsonFile, json_encode($images, JSON_PRETTY_PRINT));
    }

    private function removeImageFromJson($path)
    {
        $disk = Storage::disk('public');
        $images = json_decode($disk->get($this->jsonFile), true);
        $images = array_filter($images, fn($image) => $image !== $path);
        $disk->put($this->jsonFile, json_encode(array_values($images), JSON_PRETTY_PRINT));
    }
};
?>

<div x-data="{}" x-init="$nextTick(() => {
    new Sortable($refs.imageList, {
        animation: 200,
        handle: '.handle', // Drag handle
        onEnd: function (evt) {
            const orderedIds = Array.from(evt.to.children).map(item => ({ value: item.dataset.id }));
            @this.call('reorderImages', orderedIds);
        },
    });
})">
    <x-header title="Administrar Slider" subtitle="Sube, elimina y ordena las imágenes del carrusel." separator />

    <div class="relative">
        <div wire:loading.flex wire:target="reorderImages" class="absolute inset-0 bg-white bg-opacity-75 z-10 items-center justify-center" style="display: none;">
            <div class="text-center">
                <x-icon name="o-arrow-path" class="w-8 h-8 animate-spin mx-auto" />
                <p>Reordenando imágenes...</p>
            </div>
        </div>
        <div x-ref="imageList" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-8" wire:loading.class="opacity-50" wire:target="reorderImages">
            @foreach($this->images as $image)
                <div wire:key="{{ $image['id'] }}" data-id="{{ $image['id'] }}"
                    class="relative group bg-base-200 rounded-lg shadow-md overflow-hidden">
                    <img src="{{ $image['url'] }}" class="w-full h-48 object-cover" alt="Slider Image">
                    <div
                        class="absolute inset-0 bg-black bg-opacity-50 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                        <button class="handle cursor-grab text-white p-2 rounded-full mr-2">
                            <x-icon name="o-arrows-pointing-out" class="w-6 h-6" />
                        </button>
                        <button wire:click="delete('{{ $image['id'] }}')"
                            wire:confirm="¿Estás seguro de que quieres eliminar esta imagen?"
                            class="bg-red-500 text-white p-2 rounded-full">
                            <x-icon name="o-trash" class="w-6 h-6" />
                        </button>
                    </div>
                    <div class="absolute bottom-2 left-2 text-white text-sm bg-black bg-opacity-70 px-2 py-1 rounded">
                        {{ $image['name'] }}
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <x-form wire:submit="uploadImage" class="flex p-4 bg-base-100 rounded-lg shadow-md">
        <x-file wire:model="selectedImageFile" label="Subir Imagen" accept="image/*" />
        @error('selectedImageFile')
            <span class="text-red-500 text-sm">{{ $message }}</span>
        @enderror
        @if($selectedImageFile)
            <x-button type="submit" class="btn-primary mt-4">Subir Imagen</x-button>
        @endif
    </x-form>
</div>

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
@endpush
