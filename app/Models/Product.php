<?php

namespace App\Models;

use App\Helpers\SettingsHelper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class Product extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected static function booted(): void
    {
        static::saved(function (Product $product) {
            Cache::forget("product_media_{$product->id}");
        });

        static::deleted(function (Product $product) {
            Cache::forget("product_media_{$product->id}");
        });
    }

    public function getMediaAttribute(): array
    {
        if (! $this->image_url) {
            return [];
        }

        return Cache::remember("product_media_{$this->id}", now()->addDay(), function () {
            $media = [];

            // 1. Imagen principal
            if ($this->image_url) {
                $media[] = [
                    'type' => 'image',
                    'url' => $this->image_url,
                    'thumb' => $this->image_url,
                    'storage_path' => str_replace(['/storage/', Storage::disk('public')->url('')], '', $this->image_url),
                ];
            }

            // 2. Buscar archivos en la carpeta estable del producto
            $stableDirectory = "products/{$this->id}";
            $stableFiles = [];
            if (Storage::disk('public')->exists($stableDirectory)) {
                $stableFiles = Storage::disk('public')->files($stableDirectory);
            }

            // 3. Fallback: buscar archivos en la carpeta antigua (si la imagen principal es local)
            $oldRelatedFiles = [];
            if ($this->image_url && ! str_starts_with($this->image_url, '/storage/products/'.$this->id.'/')) {
                $path = str_replace(['/storage/', Storage::disk('public')->url('')], '', $this->image_url);
                $directory = dirname($path);
                $filename = basename($path);
                $baseName = pathinfo($filename, PATHINFO_FILENAME);

                if ($directory !== '.' && Storage::disk('public')->exists($directory)) {
                    $files = Storage::disk('public')->files($directory);
                    $oldRelatedFiles = array_filter($files, function ($file) use ($baseName, $filename) {
                        $name = basename($file);

                        return str_starts_with($name, $baseName.'-') && $name !== $filename;
                    });
                }
            }

            // Combinar archivos (excluyendo la imagen principal si ya está en stableFiles)
            $allRelatedFiles = array_unique(array_merge($stableFiles, $oldRelatedFiles));

            // Si la imagen principal es local y está en la carpeta estable, ya está en el primer lugar
            // Debemos evitar duplicarla en la lista de extras
            $mainStoragePath = $this->image_url ? str_replace(['/storage/', Storage::disk('public')->url('')], '', $this->image_url) : null;

            $allRelatedFiles = array_filter($allRelatedFiles, fn ($file) => $file !== $mainStoragePath);
            sort($allRelatedFiles);

            foreach ($allRelatedFiles as $file) {
                $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                $url = '/storage/'.str_replace('\\', '/', $file);

                if (in_array($extension, ['url', 'txt'])) {
                    $content = trim(Storage::disk('public')->get($file));
                    if (filter_var($content, FILTER_VALIDATE_URL)) {
                        $media[] = [
                            'type' => 'video',
                            'url' => $content,
                            'thumb' => $this->getYoutubeThumbnail($content),
                            'storage_path' => $file,
                        ];
                    }
                } else {
                    $media[] = [
                        'type' => 'image',
                        'url' => $url,
                        'thumb' => $url,
                        'storage_path' => $file,
                    ];
                }
            }

            return $media;
        });
    }

    private function getYoutubeThumbnail(string $url): string
    {
        $videoId = null;
        if (preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/i', $url, $match)) {
            $videoId = $match[1];
        }

        return $videoId ? "https://img.youtube.com/vi/{$videoId}/mqdefault.jpg" : asset('imgs/fallback.webp');
    }

    public function listPrices(): HasMany
    {
        return $this->hasMany(ListPrice::class); // Un producto puede tener múltiples precios en diferentes listas
    }

    public static function getTags()
    {
        return SettingsHelper::getProductTags();
    }

    public function hasBonus(): bool
    {
        return $this->bonus_threshold > 0 && $this->bonus_amount > 0;
    }

    public function getBonusLabelAttribute(): string
    {
        if (! $this->hasBonus()) {
            return '';
        }

        return "{$this->bonus_threshold} + {$this->bonus_amount} off !!";
    }
}
