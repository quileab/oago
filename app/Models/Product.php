<?php

namespace App\Models;

use App\Traits\HasTags;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class Product extends Model
{
    use HasFactory;
    use HasTags;

    protected $guarded = ['tags_string'];

    /**
     * `tags_string` is appended to keep the API response shape stable
     * after the legacy `tags` column was dropped in favour of the pivot table.
     * It is also guarded so mass assignment cannot try to write it as a column.
     *
     * @var array<int, string>
     */
    protected $appends = ['tags_string'];

    protected static function booted(): void
    {
        static::saved(function (Product $product) {
            Cache::forget("product_media_{$product->id}");
            static::clearCatalogFiltersCache();
        });

        static::deleted(function (Product $product) {
            Cache::forget("product_media_{$product->id}");
            static::clearCatalogFiltersCache();
        });
    }

    public static function clearCatalogFiltersCache(): void
    {
        Cache::forget('catalog.categories');
        Cache::forget('catalog.brands');
    }

    /**
     * @return array<int, string>
     */
    public static function getCachedCategories(): array
    {
        return Cache::remember('catalog.categories', 3600, function () {
            return self::query()
                ->where('published', 1)
                ->where('visibility', '!=', 'hidden')
                ->where('category', '!=', '')
                ->distinct()
                ->orderBy('category')
                ->pluck('category')
                ->filter()
                ->values()
                ->all();
        });
    }

    /**
     * @return array<int, string>
     */
    public static function getCachedBrands(): array
    {
        return Cache::remember('catalog.brands', 3600, function () {
            return self::query()
                ->where('published', 1)
                ->where('visibility', '!=', 'hidden')
                ->where('brand', '!=', '')
                ->distinct()
                ->orderBy('brand')
                ->pluck('brand')
                ->filter()
                ->values()
                ->all();
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

            // 2. Buscar archivos adicionales relacionados en image_cache ({hash}-1.webp, {hash}-2.url, etc.)
            $relatedFiles = [];
            $path = str_replace(['/storage/', Storage::disk('public')->url('')], '', $this->image_url);
            $directory = dirname($path);
            $filename = basename($path);
            $baseName = pathinfo($filename, PATHINFO_FILENAME);

            if ($directory !== '.' && Storage::disk('public')->exists($directory)) {
                $files = Storage::disk('public')->files($directory);
                $relatedFiles = array_filter($files, function ($file) use ($baseName, $filename) {
                    $name = basename($file);

                    return str_starts_with($name, $baseName.'-') && $name !== $filename;
                });
            }

            // Excluir la imagen principal si ya está en la lista de extras
            $mainStoragePath = $this->image_url ? str_replace(['/storage/', Storage::disk('public')->url('')], '', $this->image_url) : null;

            $allRelatedFiles = array_filter($relatedFiles, fn ($file) => $file !== $mainStoragePath);
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

    /**
     * Get the list of configured product tags from the Tag model.
     *
     * @return array<string>
     */
    public static function getTags(): array
    {
        return Tag::allCached()->pluck('name')->all();
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

    public function getBrandAttribute(?string $value): string
    {
        return $value ?? '';
    }

    public function setBrandAttribute(?string $value): void
    {
        $this->attributes['brand'] = $value ?? '';
    }

    public function getModelAttribute(?string $value): string
    {
        return $value ?? '';
    }

    public function setModelAttribute(?string $value): void
    {
        $this->attributes['model'] = $value ?? '';
    }

    public function getCategoryAttribute(?string $value): string
    {
        return $value ?? '';
    }

    public function setCategoryAttribute(?string $value): void
    {
        $this->attributes['category'] = $value ?? '';
    }
}
