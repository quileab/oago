<?php

use App\Helpers\SettingsHelper;
use App\Models\ListName;
use App\Models\ListPrice;
use App\Models\Product;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Mary\Traits\Toast;

new class extends Component
{
    use Toast, WithFileUploads;

    public array $formData = [];

    public array $prices = [];

    public array $selectedTags = [];

    public $photo;

    public $extraPhotos = [];

    public $extraVideos = [];

    public array $existingMedia = [];

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
            $this->loadExistingMedia();
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
                'in_stock' => true,
                'allow_reservation' => false,
                'by_bulk' => false,
            ];
            $this->selectedTags = [];
            foreach ($this->listNames() as $list) {
                $this->prices[$list->id] = 0;
            }
        }
        $this->uploadKey = uniqid();
    }

    private function loadExistingMedia()
    {
        if (! $this->product) {
            return;
        }

        $allMedia = $this->product->media;

        // Asegurarnos de que cada item tenga storage_path (por si acaso el cache tiene datos viejos)
        $allMedia = array_map(function ($item) {
            if (! isset($item['storage_path'])) {
                $item['storage_path'] = str_replace(['/storage/', Storage::disk('public')->url('')], '', $item['url']);
            }

            return $item;
        }, $allMedia);

        // Si existe imagen principal, la primera de 'media' es la principal y la ignoramos para extras.
        // Si no existe, todas las que haya en 'media' son extras.
        $this->existingMedia = $this->product->image_url ? array_slice($allMedia, 1) : $allMedia;
    }

    public function addVideo()
    {
        $this->extraVideos[] = '';
    }

    public function removeExtraVideo($index)
    {
        unset($this->extraVideos[$index]);
        $this->extraVideos = array_values($this->extraVideos);
    }

    public function deleteMedia($path)
    {
        Storage::disk('public')->delete($path);

        Cache::forget("product_media_{$this->product->id}");
        $this->loadExistingMedia();
        $this->success('Archivo eliminado correctamente.');
    }

    public function listNames()
    {
        return ListName::all();
    }

    public function availableTags()
    {
        $tags = SettingsHelper::getProductTags();

        return array_map(fn ($tag) => ['id' => $tag, 'name' => $tag], $tags);
    }

    public function updatedPhoto()
    {
        try {
            $this->validate([
                'photo' => 'image|max:1900',
            ]);
            $this->uploadKey = uniqid(); // Forzar refresco para nueva vista previa
        } catch (ValidationException $e) {
            $this->error($e->getMessage());
            $this->photo = null;
            $this->uploadKey = uniqid();
        }
    }

    public function updatedExtraPhotos()
    {
        try {
            $this->validate([
                'extraPhotos.*' => 'image|max:10240',
            ]);
        } catch (ValidationException $e) {
            $this->error($e->getMessage());
            $this->extraPhotos = [];
        }
    }

    public function save()
    {
        $rules = [
            'formData.description' => 'required|string|max:100',
            'formData.description_html' => 'nullable|string|max:250',
            'formData.brand' => 'nullable|string|max:30',
            'formData.category' => 'nullable|string|max:50',
            'formData.model' => 'nullable|string|max:130',
            'formData.sku' => 'nullable|string|max:50',
            'formData.barcode' => 'nullable|string|max:50',
            'formData.product_type' => 'nullable|string|max:30',
            'formData.stock' => 'required|numeric',
            'formData.qtty_package' => 'required|numeric|min:1',
            'formData.qtty_unit' => 'required|numeric|min:1',
            'formData.weight' => 'nullable|numeric',
            'formData.lenght' => 'nullable|numeric',
            'formData.width' => 'nullable|numeric',
            'formData.height' => 'nullable|numeric',
            'formData.price' => 'nullable|numeric',
            'formData.offer_price' => 'nullable|numeric',
            'formData.offer_start' => 'nullable|date',
            'formData.offer_end' => 'nullable|date',
            'formData.bonus_threshold' => 'nullable|integer',
            'formData.bonus_amount' => 'nullable|integer',
            'formData.tax_status' => 'nullable|string|max:10',
            'formData.visibility' => 'required|string|max:10',
            'photo' => 'nullable|image|max:10240',
            'extraPhotos.*' => 'image|max:10240',
            'extraVideos.*' => 'nullable|url',
        ];

        $this->validate($rules);

        // Sync tags back to string
        $this->formData['tags'] = implode('|', $this->selectedTags);

        $product = Product::updateOrCreate(
            ['id' => $this->product->id ?? null],
            $this->formData
        );

        $this->product = $product;

        // Handle Main Image
        if ($this->photo) {
            $this->processImage($product);
        }

        // Handle Extra Media
        $this->processExtraMedia($product);

        // Handle Prices
        foreach ($this->prices as $listId => $price) {
            ListPrice::updateOrCreate(
                ['product_id' => $product->id, 'list_id' => $listId],
                ['price' => $price, 'unit_price' => $price / max(1, $product->qtty_unit ?? 1)]
            );
        }

        $this->loadExistingMedia();
        $this->success('Producto guardado correctamente.');
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

    private function processExtraMedia(Product $product)
    {
        $directory = "products/{$product->id}";

        if (! Storage::disk('public')->exists($directory)) {
            Storage::disk('public')->makeDirectory($directory);
        }

        // 1. Determinar el siguiente índice disponible
        $files = Storage::disk('public')->files($directory);
        $maxIndex = 0;
        foreach ($files as $file) {
            $name = basename($file);
            if (preg_match('/extra-(\d+)\./', $name, $matches)) {
                $maxIndex = max($maxIndex, (int) $matches[1]);
            }
        }

        // 2. Procesar fotos extras
        // Livewire puede devolver un objeto solo si es un archivo, o array si son varios
        $photos = is_array($this->extraPhotos) ? $this->extraPhotos : ($this->extraPhotos ? [$this->extraPhotos] : []);

        foreach ($photos as $photo) {
            try {
                $maxIndex++;
                $tmpPath = $photo->getRealPath();
                $imageContent = file_get_contents($tmpPath);
                $image = @imagecreatefromstring($imageContent);

                if ($image !== false) {
                    imagepalettetotruecolor($image);
                    imagealphablending($image, true);
                    imagesavealpha($image, true);

                    $extraFileName = "{$directory}/extra-{$maxIndex}.webp";

                    ob_start();
                    imagewebp($image, null, 80);
                    $webpData = ob_get_clean();
                    imagedestroy($image);

                    if ($webpData) {
                        Storage::disk('public')->put($extraFileName, $webpData);
                    }
                }
            } catch (Exception $e) {
                $this->error('Error al procesar foto extra: '.$e->getMessage());
            }
        }

        // 3. Procesar videos extras
        foreach ($this->extraVideos as $videoUrl) {
            if (empty($videoUrl)) {
                continue;
            }

            $maxIndex++;
            $videoFileName = "{$directory}/extra-{$maxIndex}.url";
            Storage::disk('public')->put($videoFileName, $videoUrl);
        }

        $this->extraPhotos = [];
        $this->extraVideos = [];
        Cache::forget("product_media_{$product->id}");
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

                $dir = "products/{$product->id}";
                if (! Storage::disk('public')->exists($dir)) {
                    Storage::disk('public')->makeDirectory($dir);
                }

                $fileName = "{$dir}/main.webp";

                ob_start();
                imagewebp($image, null, 80);
                $webpData = ob_get_clean();
                imagedestroy($image);

                if ($webpData) {
                    // Borrar imagen vieja si es distinta y es local
                    if ($product->image_url) {
                        $oldPath = str_replace([Storage::disk('public')->url(''), '/storage/'], '', $product->image_url);
                        if ($oldPath !== $fileName) {
                            Storage::disk('public')->delete($oldPath);
                        }
                    }

                    Storage::disk('public')->put($fileName, $webpData);
                    $product->update([
                        'image_url' => '/storage/'.$fileName,
                    ]);
                }
            }
        } catch (Throwable $e) {
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
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
            {{-- Columna Izquierda (Side): Imagen Principal, Estado y Listas de Precios --}}
            <div class="lg:col-span-4 space-y-6">
                <x-card title="Imagen Principal" separator shadow class="bg-base-100">
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
                                    $previewUrl = null;
                                    if ($photo) {
                                        try {
                                            $previewUrl = $photo->temporaryUrl();
                                        } catch (\Exception $e) {
                                        }
                                    }
                                    if (! $previewUrl) {
                                        $previewUrl = (isset($formData['image_url']) && $formData['image_url']) ? $formData['image_url'] : '/imgs/fallback.webp';
                                    } else {
                                        // Añadir cache-buster para evitar que el navegador se quede con la imagen anterior
                                        $previewUrl .= (str_contains($previewUrl, '?') ? '&' : '?') . 'v=' . $uploadKey;
                                    }
                                @endphp
                                
                                <x-image-proxy :url="$previewUrl" wire:key="main-preview-{{ $uploadKey }}" class="w-full aspect-square object-cover rounded-2xl border-4 border-base-300 shadow-xl" />
                                    
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

                <x-card title="Publicación y Venta" separator shadow class="bg-base-100">
                    <div class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <x-checkbox label="Publicado" wire:model="formData.published" tight />
                            <x-checkbox label="Destacado" wire:model="formData.featured" tight />
                            <x-checkbox label="En Stock" wire:model="formData.in_stock" tight />
                            <x-checkbox label="Permitir Reserva" wire:model="formData.allow_reservation" tight />
                            <x-checkbox label="Venta por Bulto" wire:model="formData.by_bulk" tight />
                        </div>
                        <x-select label="Visibilidad" wire:model="formData.visibility" :options="[
                            ['id' => 'visible', 'name' => 'Visible'],
                            ['id' => 'catalog', 'name' => 'Solo Catálogo'],
                            ['id' => 'hidden', 'name' => 'Oculto']
                        ]" />
                    </div>
                </x-card>

                <x-card separator shadow class="bg-base-100">
                    <x-slot:title>
                        <div class="flex items-center gap-2">
                            <x-icon name="o-currency-dollar" class="w-6 h-6 text-success" />
                            Precios
                        </div>
                    </x-slot:title>
                    
                    <div class="space-y-3 max-h-[600px] overflow-y-auto pr-2 scrollbar-thin scrollbar-thumb-gray-200">
                        <div class="p-3 border-2 border-primary/20 rounded-xl bg-primary/5 mb-4">
                            <x-input label="Precio Base (Default)" 
                                     wire:model="formData.price" 
                                     type="number" 
                                     step="0.01" 
                                     prefix="$"
                                     class="font-extrabold text-primary" />
                        </div>

                        <div class="divider text-[10px] uppercase opacity-40">Listas de Precios</div>

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

            {{-- Columna Derecha (Main): Información General y Multimedia Extra --}}
            <div class="lg:col-span-8 space-y-6">
                <x-card title="Información General" separator shadow class="bg-base-100">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="md:col-span-2">
                            <x-input label="Descripción" wire:model="formData.description" placeholder="Ej: Amortiguador Trasero..." />
                        </div>
                        
                        <x-input label="Marca" wire:model="formData.brand" placeholder="Ej: Monroe" />
                        <x-input label="Modelo" wire:model="formData.model" placeholder="Ej: Adventure" />
                        
                        <x-input label="Categoría" wire:model="formData.category" placeholder="Ej: Suspensión" />
                        <x-input label="Tipo de Producto" wire:model="formData.product_type" placeholder="Ej: Repuesto" />

                        <x-input label="SKU" wire:model="formData.sku" icon="o-hashtag" placeholder="Ej: MON-123" />
                        <x-input label="Código de Barras" wire:model="formData.barcode" icon="o-qr-code" placeholder="Ej: 779..." />

                        <div class="grid grid-cols-3 gap-4 md:col-span-2">
                            <x-input label="Stock Actual" type="number" wire:model="formData.stock" icon="o-cube" />
                            <x-input label="Unidades x Bulto" type="number" wire:model="formData.qtty_package" hint="Empaque" />
                            <x-input label="Unidades Totales" type="number" wire:model="formData.qtty_unit" hint="Contenido" />
                        </div>

                        <div class="md:col-span-2">
                            <x-alert icon="o-information-circle" class="alert-info shadow-sm mb-4"
                                     title="Gestión Masiva Disponible">
                                Atributos como <b>Etiquetas</b>, <b>Descripción HTML</b>, <b>Publicación</b> y <b>Bonificaciones</b> también pueden editarse grupalmente en 
                                <a href="/products/extras" class="link link-primary font-bold">Productos Extras</a>.
                            </x-alert>
                            <x-textarea label="Descripción HTML (Snippet)" wire:model="formData.description_html" placeholder="Ej: <b>Negro</b>..." rows="2" />
                        </div>

                        <div class="md:col-span-2">
                            <x-choices label="Etiquetas del Producto" 
                                    wire:model="selectedTags" 
                                    :options="$this->availableTags()" 
                                    allow-all 
                                    icon="o-tag" 
                                    hint="Seleccione para categorizar el producto" />
                        </div>
                    </div>
                </x-card>

                <x-card title="Dimensiones y Peso" separator shadow class="bg-base-100">
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <x-input label="Peso (kg)" type="number" step="0.001" wire:model="formData.weight" icon="o-scale" />
                        <x-input label="Largo (cm)" type="number" step="0.001" wire:model="formData.lenght" icon="o-arrows-right-left" />
                        <x-input label="Ancho (cm)" type="number" step="0.001" wire:model="formData.width" icon="o-arrows-right-left" />
                        <x-input label="Alto (cm)" type="number" step="0.001" wire:model="formData.height" icon="o-arrows-up-down" />
                    </div>
                </x-card>

                <x-card title="Ofertas y Bonificaciones" separator shadow class="bg-base-100">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <x-input label="Precio de Oferta" type="number" step="0.01" wire:model="formData.offer_price" prefix="$" icon="o-tag" />
                        <x-input label="Estado de Impuestos" wire:model="formData.tax_status" placeholder="Ej: taxable" />
                        
                        <x-input label="Inicio de Oferta" type="date" wire:model="formData.offer_start" />
                        <x-input label="Fin de Oferta" type="date" wire:model="formData.offer_end" />

                        <div class="divider md:col-span-2 text-xs uppercase opacity-50">Bonificación (Ej: 23+1)</div>
                        
                        <x-input label="Umbral de Bono" type="number" wire:model="formData.bonus_threshold" hint="Cantidad requerida" />
                        <x-input label="Cantidad de Bono" type="number" wire:model="formData.bonus_amount" hint="Cantidad regalada" />
                    </div>
                </x-card>

                <x-card title="Multimedia Extra" separator shadow class="bg-base-100">
                    <div class="space-y-6">
                        {{-- Imágenes Existentes --}}
                        <div wire:key="media-list-{{ count($existingMedia) }}-{{ time() }}">
                            @if(count($existingMedia) > 0)
                                <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-4">
                                    @foreach($existingMedia as $index => $media)
                                        <div class="relative group aspect-square shadow-sm"
                                             wire:key="media-item-{{ $index }}-{{ md5($media['url']) }}">
                                            
                                            {{-- Imagen con redondeo propio --}}
                                            <x-image-proxy :url="$media['thumb']" class="w-full h-full object-cover rounded-xl border-2 border-base-300 group-hover:border-primary/50 transition-all" />
                                            
                                            {{-- Overlay con redondeo propio y sin overflow-hidden --}}
                                            <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-3 rounded-xl">
                                                @if($media['type'] === 'video')
                                                    <div class="bg-white/20 backdrop-blur-md rounded-full p-2">
                                                        <x-icon name="s-play" class="w-8 h-8 text-white" />
                                                    </div>
                                                @endif
                                                
                                                {{-- Dropdown con portal o simplemente permitiendo overflow si el padre no tiene overflow-hidden --}}
                                                <x-dropdown icon="o-trash" class="btn-circle btn-sm btn-error shadow-lg" no-x-padding right>
                                                    <x-menu-item title="Confirmar" icon="o-trash" wire:click="deleteMedia('{{ $media['storage_path'] ?? '' }}')" class="text-error font-bold" />
                                                    <x-menu-item title="Cancelar" icon="o-x-mark" />
                                                </x-dropdown>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="p-8 border-2 border-dashed border-base-300 rounded-2xl flex flex-col items-center justify-center bg-base-200/50">
                                    <x-icon name="o-photo" class="w-12 h-12 text-base-content/20" />
                                    <p class="text-xs text-base-content/40 mt-2 font-medium">No hay multimedia extra para este producto</p>
                                </div>
                            @endif
                        </div>

                        <div class="divider text-[10px] font-bold uppercase tracking-widest opacity-50">Gestionar Multimedia</div>

                        <div class="grid grid-cols-1 xl:grid-cols-2 gap-8 w-full">
                            {{-- Upload Fotos Extras --}}
                            <div class="space-y-4 w-full text-center">
                                <label class="label label-text font-bold text-xs uppercase tracking-widest text-base-content/70 justify-center">Fotos Adicionales</label>
                                
                                <label class="flex flex-col items-center justify-center w-full min-h-[160px] bg-base-200 border-dashed border-2 hover:border-primary/50 transition-colors cursor-pointer group/upload rounded-xl relative p-4"
                                       wire:class="{'opacity-50 pointer-events-none': $wire.__instance.effects.returns.extraPhotos}">
                                    <input type="file" wire:model.live="extraPhotos" accept="image/*" multiple class="hidden" />
                                    
                                    <div wire:loading.remove wire:target="extraPhotos" class="w-full">
                                        @if($extraPhotos)
                                            <div class="flex flex-wrap justify-center gap-4 w-full">
                                                @foreach($extraPhotos as $photo)
                                                    <div class="relative w-20 h-20 rounded-lg overflow-hidden border shadow-sm group/item">
                                                        @php
                                                            $tempUrl = null;
                                                            try {
                                                                $tempUrl = $photo->temporaryUrl();
                                                            } catch(\Exception $e) {}
                                                        @endphp
                                                        
                                                        @if($tempUrl)
                                                            <x-image-proxy :url="$tempUrl" class="w-full h-full object-cover" />
                                                        @else                                                            <div class="w-full h-full flex items-center justify-center bg-base-300">
                                                                <x-icon name="o-photo" class="w-6 h-6 text-base-content/20" />
                                                            </div>
                                                        @endif
                                                        
                                                        <div class="absolute inset-0 bg-black/40 opacity-0 group-hover/item:opacity-100 transition-opacity flex items-center justify-center">
                                                            <x-icon name="o-arrow-path" class="w-5 h-5 text-white" />
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                            <div class="w-full text-center mt-4">
                                                <x-badge value="{{ count($extraPhotos) }} fotos (Clic para reemplazar)" class="badge-success text-white font-bold text-[10px]" />
                                            </div>
                                        @else
                                            <div class="flex flex-col items-center px-4 text-center py-6">
                                                <x-icon name="o-cloud-arrow-up" class="w-12 h-12 text-base-content/30 group-hover/upload:text-primary transition-colors" />
                                                <div class="mt-2 text-sm font-bold text-base-content/60 group-hover/upload:text-primary">Clic para subir imágenes</div>
                                                <div class="text-[10px] text-base-content/40 uppercase tracking-widest mt-1">WebP, JPG, PNG</div>
                                            </div>
                                        @endif
                                    </div>

                                    <div wire:loading wire:target="extraPhotos" class="w-full text-center py-6">
                                        <div class="text-xs text-primary flex items-center justify-center gap-2">
                                            <x-loading class="loading-xs" /> Procesando imágenes...
                                        </div>
                                    </div>
                                </label>
                                
                                @error('extraPhotos.*')
                                    <div class="w-full text-center mt-2">
                                        <p class="text-[10px] text-error font-bold">{{ $message }}</p>
                                    </div>
                                @enderror
                                
                                <p class="text-[10px] text-base-content/50 italic text-center">
                                    Click en el área gris para seleccionar fotos. Se procesarán al guardar el producto.
                                </p>
                            </div>

                            {{-- Videos --}}
                            <div class="space-y-4 w-full">
                                <div class="flex justify-between items-center mb-1">
                                    <label class="label label-text font-bold text-xs uppercase tracking-widest text-base-content/70">Videos de YouTube</label>
                                    <x-button label="Agregar Video" icon="o-plus" wire:click="addVideo" class="btn-xs btn-primary btn-outline" type="button" />
                                </div>

                                <div class="space-y-3">
                                    @foreach($extraVideos as $index => $video)
                                        <div class="flex gap-2 items-center group" wire:key="video-{{ $index }}">
                                            <div class="bg-base-200 p-2 rounded-lg flex-grow">
                                                <x-input wire:model="extraVideos.{{ $index }}" placeholder="URL de YouTube..." class="input-sm border-none bg-transparent focus:ring-0" />
                                            </div>
                                            <x-button icon="o-trash" wire:click="removeExtraVideo({{ $index }})" class="btn-sm btn-square btn-ghost text-error opacity-0 group-hover:opacity-100 transition-opacity" type="button" />
                                        </div>
                                    @endforeach
                                    @if(empty($extraVideos))
                                        <p class="text-[10px] text-base-content/40 italic text-center">No hay videos agregados.</p>
                                    @endif
                                </div>
                            </div>
                        </div>
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
