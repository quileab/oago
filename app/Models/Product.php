<?php

namespace App\Models;

use App\Services\FreightService;
use App\Traits\HasTags;
use Illuminate\Database\Eloquent\Builder;
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
     * @var array<string, mixed>
     */
    protected $attributes = [
        'in_stock' => true,
        'deduct_stock' => true,
        'allow_reservation' => false,
    ];

    /**
     * `tags_string` is appended to keep the API response shape stable
     * after the legacy `tags` column was dropped in favour of the pivot table.
     * It is also guarded so mass assignment cannot try to write it as a column.
     *
     * @var array<int, string>
     */
    protected $appends = ['tags_string'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'in_stock' => 'boolean',
            'deduct_stock' => 'boolean',
            'allow_reservation' => 'boolean',
            'published' => 'boolean',
            'featured' => 'boolean',
        ];
    }

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

        foreach (Tag::divisionsCached()->keys() as $slug) {
            Cache::forget("catalog.categories.{$slug}");
            Cache::forget("catalog.brands.{$slug}");
        }
    }

    /**
     * Filter products belonging to a business division (`division` Tag slug or configured alias).
     * Prioritizes division tags and uses Tag-configured fallback keywords for untagged products.
     */
    public function scopeForDivision(Builder $query, ?string $division): Builder
    {
        $slug = Tag::normalizeDivisionSlug($division);
        if ($slug === null) {
            return $query;
        }

        $divisions = Tag::divisionsCached();
        $targetTag = $divisions->get($slug);
        $targetKeywords = $targetTag ? $targetTag->fallbackMatchKeywords() : [];

        $otherKeywords = [];
        foreach ($divisions as $candidateSlug => $candidateTag) {
            if ($candidateSlug === $slug) {
                continue;
            }
            foreach ($candidateTag->fallbackMatchKeywords() as $kw) {
                $otherKeywords[] = $kw;
            }
        }
        $otherKeywords = array_values(array_unique($otherKeywords));

        if ($targetKeywords !== []) {
            return $query->where(function (Builder $outer) use ($slug, $targetKeywords) {
                $outer->whereHas('tags', fn (Builder $q) => $q->where('slug', $slug))
                    ->orWhere(function (Builder $fallback) use ($targetKeywords) {
                        $fallback->whereDoesntHave('tags', fn (Builder $q) => $q->where('type', Tag::TYPE_DIVISION))
                            ->where(function (Builder $heuristic) use ($targetKeywords) {
                                foreach ($targetKeywords as $keyword) {
                                    $heuristic->orWhere('products.brand', 'like', "%{$keyword}%")
                                        ->orWhere('products.description', 'like', "%{$keyword}%");
                                }
                            });
                    });
            });
        }

        if ($targetTag !== null && $otherKeywords !== []) {
            return $query->where(function (Builder $outer) use ($slug, $otherKeywords) {
                $outer->whereHas('tags', fn (Builder $q) => $q->where('slug', $slug))
                    ->orWhere(function (Builder $fallback) use ($otherKeywords) {
                        $fallback->whereDoesntHave('tags', fn (Builder $q) => $q->where('type', Tag::TYPE_DIVISION));

                        foreach ($otherKeywords as $keyword) {
                            $fallback->where(function (Builder $brandGuard) use ($keyword) {
                                $brandGuard->whereNull('products.brand')
                                    ->orWhere('products.brand', 'not like', "%{$keyword}%");
                            })->where(function (Builder $descGuard) use ($keyword) {
                                $descGuard->whereNull('products.description')
                                    ->orWhere('products.description', 'not like', "%{$keyword}%");
                            });
                        }
                    });
            });
        }

        return $query->whereHas('tags', fn (Builder $q) => $q->where('slug', $slug));
    }

    /**
     * Resolve division appearance/metadata from either an Eloquent Product, stdClass, or raw array.
     *
     * @return array{slug: string, store_key: string, name: string, color: string, fallback_image: string}
     */
    public static function resolveDivisionFromRaw(object|array $product): array
    {
        if ($product instanceof self) {
            return $product->resolveDivision();
        }

        $data = (array) $product;

        return Tag::resolveProductDivision(
            null,
            (array) ($data['tags_array'] ?? []),
            (string) ($data['brand'] ?? ''),
            (string) ($data['description'] ?? '')
        );
    }

    /**
     * @return array<int, string>
     */
    public static function getCachedCategories(?string $division = null): array
    {
        $slug = Tag::normalizeDivisionSlug($division);
        $cacheKey = $slug ? "catalog.categories.{$slug}" : 'catalog.categories';

        return Cache::remember($cacheKey, 3600, function () use ($slug) {
            return self::query()
                ->where('published', 1)
                ->where('visibility', '!=', 'hidden')
                ->where('category', '!=', '')
                ->when($slug, fn (Builder $q) => $q->forDivision($slug))
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
    public static function getCachedBrands(?string $division = null): array
    {
        $slug = Tag::normalizeDivisionSlug($division);
        $cacheKey = $slug ? "catalog.brands.{$slug}" : 'catalog.brands';

        return Cache::remember($cacheKey, 3600, function () use ($slug) {
            return self::query()
                ->where('published', 1)
                ->where('visibility', '!=', 'hidden')
                ->where('brand', '!=', '')
                ->when($slug, fn (Builder $q) => $q->forDivision($slug))
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

    public function tracksStock(): bool
    {
        return (bool) ($this->deduct_stock ?? true);
    }

    public function hasAvailableStock(int $quantity = 1): bool
    {
        if (! $this->tracksStock()) {
            return true;
        }

        if ((bool) $this->allow_reservation) {
            return true;
        }

        return (bool) ($this->in_stock ?? true) && $this->stock >= $quantity;
    }

    public function isOutOfStock(): bool
    {
        return ! $this->hasAvailableStock(1);
    }

    /**
     * @return array{label: string, class: string, icon: string}
     */
    public function stockBadge(): array
    {
        if (! $this->tracksStock()) {
            return ['label' => 'DISPONIBLE', 'class' => 'text-green-600 bg-green-50 border-green-100', 'icon' => 's-check-circle'];
        }

        if ($this->isOutOfStock()) {
            return ['label' => 'AGOTADO', 'class' => 'text-red-600 bg-red-50 border-red-100', 'icon' => 's-x-circle'];
        }

        if ($this->stock <= 0 && (bool) $this->allow_reservation) {
            return ['label' => 'A PEDIDO', 'class' => 'text-blue-600 bg-blue-50 border-blue-100', 'icon' => 's-clock'];
        }

        if ($this->stock < 10) {
            return ['label' => 'STOCK BAJO', 'class' => 'text-red-600 bg-red-50 border-red-100', 'icon' => 's-bolt'];
        }

        if ($this->stock < 100) {
            return ['label' => 'STOCK MEDIO', 'class' => 'text-amber-600 bg-amber-50 border-amber-100', 'icon' => 's-bolt'];
        }

        return ['label' => 'EN STOCK', 'class' => 'text-green-600 bg-green-50 border-green-100', 'icon' => 's-check-circle'];
    }

    public function isSystemProduct(): bool
    {
        return strcasecmp((string) $this->model, 'consumo interno') === 0
            || app(FreightService::class)->isFreightProduct($this->id);
    }
}
