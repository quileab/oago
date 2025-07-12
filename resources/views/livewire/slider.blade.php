<?php

use Livewire\Volt\Component;
use Illuminate\Support\Facades\Storage;
use Livewire\WithFileUploads;
use Mary\Traits\Toast;

new class extends Component {
    use WithFileUploads, Toast;

    public $selectedImageFile;

    public function mount()
    {
        $this->reindexImages(); // Renombra archivos al cargar
    }

    public function getImagesProperty()
    {
        $disk = Storage::disk('public');
        $sliderPath = 'slider';

        $files = $disk->files($sliderPath);

        // Ordenar por nombre "slide (X).jpg"
        usort($files, function ($a, $b) {
            preg_match('/slide \((\d+)\)/', basename($a), $aMatch);
            preg_match('/slide \((\d+)\)/', basename($b), $bMatch);

            return ($aMatch[1] ?? PHP_INT_MAX) <=> ($bMatch[1] ?? PHP_INT_MAX);
        });

        return collect($files)->map(fn($f) => [
            'id' => $f,
            'url' => asset('storage/' . $f),
            'name' => basename($f),
        ]);
    }

    public function uploadImage()
    {
        $this->validate([
            'selectedImageFile' => 'required|image|max:1024',
        ]);

        $disk = Storage::disk('public');
        $sliderPath = 'slider';

        $ext = $this->selectedImageFile->getClientOriginalExtension();
        $files = $disk->files($sliderPath);
        $nextIndex = count($files) + 1;

        $filename = "slide ($nextIndex).$ext";
        $this->selectedImageFile->storeAs($sliderPath, $filename, 'public');

        $this->reset('selectedImageFile');
        $this->reindexImages();
        $this->success('Imagen subida con éxito.');
    }

    public function delete($path)
    {
        Storage::disk('public')->delete($path);
        $this->reindexImages();
        $this->success('Imagen eliminada.');
    }

    public function reorderImages($orderedItems)
    {
        $this->reindexImages($orderedItems);
        $this->success('Imágenes reordenadas.');
    }

    private function reindexImages($desiredOrder = null)
    {
        $disk = Storage::disk('public');
        $sliderPath = 'slider';

        $files = collect($disk->files($sliderPath))
            ->filter(fn($f) => preg_match('/\.(jpg|jpeg|png|webp)$/i', $f))
            ->values();

        if ($desiredOrder) {
            $map = collect($desiredOrder)->pluck('value')->mapWithKeys(fn($v, $i) => [basename($v) => $i]);
            $files = $files->sortBy(fn($f) => $map[basename($f)] ?? PHP_INT_MAX)->values();
        } else {
            $files = $files->sortBy(function ($file) {
                preg_match('/slide \((\d+)\)/', basename($file), $m);
                return isset($m[1]) ? (int) $m[1] : PHP_INT_MAX;
            })->values();
        }

        // Paso 1: renombrar a temporales únicos
        $tempFiles = [];
        foreach ($files as $index => $file) {
            $ext = pathinfo($file, PATHINFO_EXTENSION);
            $tmpName = "$sliderPath/tmp_" . uniqid() . ".$ext";
            $disk->move($file, $tmpName);
            $tempFiles[] = $tmpName;
        }

        // Paso 2: renombrar con slide (N).jpg
        foreach ($tempFiles as $i => $tmp) {
            $ext = pathinfo($tmp, PATHINFO_EXTENSION);
            $newName = "$sliderPath/slide (" . ($i + 1) . ").$ext";
            $disk->move($tmp, $newName);
        }

        // Emitir para actualizar
        $this->dispatch('$refresh');
    }
}; ?>

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

    <div x-ref="imageList" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-8">
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