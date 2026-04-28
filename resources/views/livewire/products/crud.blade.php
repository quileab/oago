<?php

use App\Models\ListName;
use App\Models\ListPrice;
use App\Models\Product;
use Illuminate\Support\Facades\Storage;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Mary\Traits\Toast;

new class extends Component {
    use WithFileUploads, Toast;

    public array $formData = [];

    public array $prices = [];

    public array $selectedTags = [];

    public $photo;

    public string $uploadKey = 'initial';

    public ?Product $product = null;

    public function mount(?Product $id = null)
    {
        if ($id && $id->exists) {
            $this->product = $id;
            $this->formData = $id->toArray();
            $this->selectedTags = array_filter(explode('|', $this->formData['tags'] ?? ''));
            foreach ($this->listNames() as $list) {
                $listPrice = ListPrice::where('product_id', $id->id)
                    ->where('list_id', $list->id)
                    ->first();
                $this->prices[$list->id] = $listPrice ? $listPrice->price : 0;
            }
        } else {
            $this->formData = [
                'description' => '',
                'brand' => '',
                'category' => '',
                'stock' => 0,
                'qtty_package' => 1,
                'qtty_unit' => 1,
                'published' => true,
                'featured' => false,
                'visibility' => 'visible',
                'tags' => '',
            ];
            $this->selectedTags = [];
            foreach ($this->listNames() as $list) {
                $this->prices[$list->id] = 0;
            }
        }
        $this->uploadKey = uniqid();
    }

    public function listNames()
    {
        return ListName::all();
    }

    public function availableTags()
    {
        $tags = \App\Helpers\SettingsHelper::getProductTags();

        return array_map(fn ($tag) => ['id' => $tag, 'name' => $tag], $tags);
    }

    public function updatedPhoto()
    {
        try {
            $this->validate([
                'photo' => 'image|max:1900',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->error($e->getMessage());
            $this->photo = null;
            $this->uploadKey = uniqid();
        }
    }

    public function save()
    {
        $rules = [
            'formData.description' => 'required|string|max:100',
            'formData.brand' => 'nullable|string|max:30',
            'formData.category' => 'nullable|string|max:50',
            'formData.stock' => 'required|numeric',
            'formData.qtty_package' => 'required|numeric|min:1',
            'formData.qtty_unit' => 'required|numeric|min:1',
            'photo' => 'nullable|image|max:1900',
        ];

        $this->validate($rules);

        // Sync tags back to string
        $this->formData['tags'] = implode('|', $this->selectedTags);

        $product = Product::updateOrCreate(
            ['id' => $this->product->id ?? null],
            $this->formData
        );

        // Handle Image
        if ($this->photo) {
            $this->processImage($product);
        }

        // Handle Prices
        foreach ($this->prices as $listId => $price) {
            ListPrice::updateOrCreate(
                ['product_id' => $product->id, 'list_id' => $listId],
                ['price' => $price, 'unit_price' => $price / max(1, $product->qtty_unit)]
            );
        }

        $this->success('Producto guardado correctamente.', redirectTo: '/products');
    }

    public function delete()
    {
        if (! $this->product) {
            return;
        }

        // Delete image if exists
        if ($this->product->image_url) {
            $path = str_replace([Storage::disk('public')->url(''), '/storage/'], '', $this->product->image_url);
            Storage::disk('public')->delete($path);
        }

        $this->product->listPrices()->delete();
        $this->product->delete();

        $this->success('Producto eliminado.', redirectTo: '/products');
    }

    private function processImage(Product $product)
    {
        if (! function_exists('imagewebp')) {
            $this->error('El servidor no tiene soporte para WebP (GD).');

            return;
        }

        $tmpPath = $this->photo->getRealPath();

        try {
            $imageContent = file_get_contents($tmpPath);
            $image = @imagecreatefromstring($imageContent);

            if ($image !== false) {
                $width = imagesx($image);
                $height = imagesy($image);
                $size = min($width, $height);
                $x = (int) (($width - $size) / 2);
                $y = (int) (($height - $size) / 2);

                $croppedImage = imagecrop($image, ['x' => $x, 'y' => $y, 'width' => $size, 'height' => $size]);
                if ($croppedImage !== false) {
                    imagedestroy($image);
                    $image = $croppedImage;
                }

                imagepalettetotruecolor($image);
                imagealphablending($image, true);
                imagesavealpha($image, true);

                $hash = md5($product->id.'-'.time());
                $part1 = substr($hash, 0, 2);
                $part2 = substr($hash, 2, 2);
                $dir = "products/{$part1}/{$part2}";

                if (! Storage::disk('public')->exists($dir)) {
                    Storage::disk('public')->makeDirectory($dir);
                }

                $fileName = "{$dir}/{$hash}.webp";

                ob_start();
                imagewebp($image, null, 80);
                $webpData = ob_get_clean();
                imagedestroy($image);

                if ($webpData) {
                    if ($product->image_url && (str_contains($product->image_url, config('app.url')) || str_starts_with($product->image_url, '/storage/'))) {
                        $oldPath = str_replace([Storage::disk('public')->url(''), '/storage/'], '', $product->image_url);
                        Storage::disk('public')->delete($oldPath);
                    }

                    Storage::disk('public')->put($fileName, $webpData);
                    $product->update([
                        'image_url' => '/storage/'.$fileName,
                    ]);
                }
            }
        } catch (\Throwable $e) {
            $this->error('Error al procesar la imagen: '.$e->getMessage());
        }
    }
}; ?>

<div class="max-w-7xl mx-auto p-4">
    <x-header :title="$product ? 'Editar Producto #' . $product->id : 'Nuevo Producto'" separator progress-indicator>
        <x-slot:actions>
            <x-button label="Volver" icon="o-arrow-left" link="/products" class="btn-ghost" />
        </x-slot:actions>
    </x-header>

    <x-form wire:submit="save">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            {{-- Columna Izquierda: Imagen y Datos Básicos --}}
            <div class="lg:col-span-1 space-y-6">
                <x-card title="Imagen" separator shadow>
                    <div class="flex flex-col items-center">
                        <div class="flex flex-col items-center mb-6" 
                             wire:key="upload-wrapper-{{ $uploadKey }}"
                             x-data="{ 
                                checkSize(e) {
                                    const file = e.target.files[0];
                                    if (file && file.size > 2 * 1024 * 1024) {
                                        $wire.error('El archivo es demasiado grande. El máximo permitido por el servidor es 2MB.');
                                        e.target.value = '';
                                    }
                                }
                             }">
                            <x-file wire:model.live="photo" accept="image/*" x-on:change="checkSize">
                                <div class="relative group cursor-pointer" wire:loading.class="opacity-50" wire:target="photo">
                                    @php
                                        $hasImage = isset($formData['image_url']) && $formData['image_url'];
                                        $imgUrl = $hasImage ? $formData['image_url'] : '/imgs/fallback.webp';
                                    @endphp
                                    
                                    <x-image-proxy :url="$imgUrl" class="w-64 h-64 object-cover rounded-2xl border-4 border-base-300 shadow-xl" />
                                    
                                    <div class="absolute inset-0 bg-primary/20 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity rounded-2xl">
                                        <x-icon name="o-pencil" class="w-12 h-12 text-white" />
                                    </div>
                                    
                                    {{-- Loading Overlay --}}
                                    <div wire:loading wire:target="photo" class="absolute inset-0 flex items-center justify-center bg-base-100/50 rounded-2xl">
                                        <x-loading class="text-primary" />
                                    </div>
                                </div>
                            </x-file>
                            <p class="text-[10px] text-gray-400 mt-4 font-bold uppercase tracking-tighter text-center">
                                Click para cambiar imagen (1:1 WebP automática)
                            </p>
                            @error('photo')
                                <p class="text-[10px] text-error mt-1 font-bold">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </x-card>

                <x-card title="Estado" separator shadow>
                    <div class="space-y-4">
                        <div class="flex flex-col gap-4">
                            <x-checkbox label="Producto Publicado" wire:model="formData.published" tight />
                            <x-checkbox label="Producto Destacado" wire:model="formData.featured" tight />
                        </div>
                        <x-select label="Visibilidad" wire:model="formData.visibility" :options="[
                            ['id' => 'visible', 'name' => 'Visible'],
                            ['id' => 'catalog', 'name' => 'Solo Catálogo'],
                            ['id' => 'hidden', 'name' => 'Oculto']
                        ]" />
                    </div>
                </x-card>
            </div>

            {{-- Columna Central: Datos Principales --}}
            <div class="lg:col-span-1 space-y-6">
                <x-card title="Información General" separator shadow>
                    <div class="space-y-4">
                        <x-input label="Descripción" wire:model="formData.description" placeholder="Ej: Amortiguador Trasero..." />
                        
                        <div class="grid grid-cols-1 gap-4">
                            <x-input label="Marca" wire:model="formData.brand" placeholder="Ej: Monroe" />
                            <x-input label="Categoría" wire:model="formData.category" placeholder="Ej: Suspensión" />
                        </div>

                        <div class="grid grid-cols-3 gap-4">
                            <x-input label="Stock" type="number" wire:model="formData.stock" />
                            <x-input label="Bulto" type="number" wire:model="formData.qtty_package" hint="Un. por bulto" />
                            <x-input label="Unidades" type="number" wire:model="formData.qtty_unit" hint="Un. totales" />
                        </div>

                        <x-choices label="Etiquetas" 
                                   wire:model="selectedTags" 
                                   :options="$this->availableTags()" 
                                   allow-all 
                                   icon="o-tag" 
                                   hint="Seleccione las etiquetas del producto" />
                    </div>
                </x-card>
            </div>

            {{-- Columna Derecha: Listas de Precios --}}
            <div class="lg:col-span-1 space-y-6">
                <x-card separator shadow>
                    <x-slot:title>
                        <div class="flex items-center gap-2">
                            <x-icon name="o-currency-dollar" class="w-6 h-6 text-success" />
                            Listas de Precios
                        </div>
                    </x-slot:title>
                    
                    <div class="space-y-3 max-h-[calc(100vh-250px)] overflow-y-auto pr-2 scrollbar-thin scrollbar-thumb-gray-200">
                        @foreach($this->listNames() as $list)
                            <div class="p-3 border border-base-content/5 rounded-xl hover:border-primary/30 transition-colors bg-base-200/20">
                                <x-input label="{{ $list->name }}" 
                                         wire:model="prices.{{ $list->id }}" 
                                         type="number" 
                                         step="0.01" 
                                         prefix="$"
                                         class="font-bold" />
                            </div>
                        @endforeach
                    </div>
                </x-card>
            </div>
        </div>

        <div class="mt-8 flex justify-between items-center bg-base-100 p-4 sticky bottom-0 border-t z-10 rounded-t-xl shadow-2xl">
            <div>
                @if($product)
                    <x-dropdown label="Eliminar Producto" icon="o-trash" class="btn-error btn-outline btn-sm">
                        <x-menu-item title="Confirmar Eliminación" icon="o-trash" wire:click="delete" class="text-error font-bold" />
                        <x-menu-item title="Cancelar" icon="o-x-mark" />
                    </x-dropdown>
                @endif
            </div>
            <div class="flex gap-4">
                <x-button label="Descartar Cambios" icon="o-x-mark" link="/products" class="btn-ghost" />
                <x-button label="Guardar Producto" icon="o-check" class="btn-primary px-8" type="submit" spinner="save" />
            </div>
        </div>
    </x-form>
</div>
