<?php

namespace App\Services;

use App\Helpers\SettingsHelper;
use App\Models\Product;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ImageImportService
{
    public const SETTINGS_KEY = 'image_import_preferences';

    /**
     * Obtiene las preferencias almacenadas o valores por defecto.
     *
     * @return array{
     *     folder: string,
     *     field: string,
     *     separator: string,
     *     pad_length: int,
     *     delete_source: bool,
     *     overwrite: bool,
     *     limit: int
     * }
     */
    public function getPreferences(): array
    {
        $defaults = [
            'folder' => 'imagenes_productos',
            'field' => 'sku',
            'separator' => '_',
            'pad_length' => 0,
            'delete_source' => false,
            'overwrite' => true,
            'limit' => 0,
        ];

        $saved = SettingsHelper::settings(self::SETTINGS_KEY, []);

        if (is_string($saved)) {
            $decoded = json_decode($saved, true);
            $saved = is_array($decoded) ? $decoded : [];
        }

        return array_merge($defaults, is_array($saved) ? $saved : []);
    }

    /**
     * Guarda las preferencias de importación en la tabla settings como JSON.
     */
    public function savePreferences(array $preferences): void
    {
        $current = $this->getPreferences();
        $updated = array_merge($current, $preferences);

        // Guardar vía SettingsHelper (se encarga de json_encode e invalidar la caché)
        SettingsHelper::update_setting(self::SETTINGS_KEY, $updated);
    }

    /**
     * Resuelve la ruta física completa de la carpeta de origen.
     */
    public function resolveSourcePath(string $folder = 'imagenes_productos'): string
    {
        $folder = trim($folder, '/\\');
        $isAbsolute = str_starts_with($folder, '/') || (DIRECTORY_SEPARATOR === '\\' && preg_match('/^[a-zA-Z]:[\\\\\/]/', $folder));

        if ($isAbsolute) {
            return $folder;
        }

        return public_path($folder);
    }

    /**
     * Parsea el nombre de archivo según la configuración para extraer identificador e índice.
     *
     * @return array{identifier: string, raw_code: string, index: int, extension: string}|null
     */
    public function parseFileName(string $filename, array $options = []): ?array
    {
        $field = $options['field'] ?? 'sku';
        $separator = $options['separator'] ?? '_';
        $padLength = (int) ($options['pad_length'] ?? 0);
        $padChar = (string) ($options['pad_char'] ?? '0');

        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $allowedExts = array_map('strtolower', (array) ($options['allowed_extensions'] ?? ['jpg', 'jpeg', 'png', 'webp']));
        if (! in_array($ext, $allowedExts, true)) {
            return null;
        }

        $baseName = pathinfo($filename, PATHINFO_FILENAME);

        $index = 1;
        $rawCode = $baseName;

        if ($separator !== '' && $separator !== 'none' && str_contains($baseName, $separator)) {
            $lastSepPos = strrpos($baseName, $separator);
            if ($lastSepPos !== false) {
                $possibleCode = substr($baseName, 0, $lastSepPos);
                $possibleIndex = substr($baseName, $lastSepPos + strlen($separator));

                if (is_numeric($possibleIndex)) {
                    $rawCode = $possibleCode;
                    $index = (int) $possibleIndex;
                }
            }
        }

        $identifier = $rawCode;
        if ($padLength > 0 && is_numeric($identifier)) {
            $identifier = str_pad($identifier, $padLength, $padChar, STR_PAD_LEFT);
        }

        return [
            'identifier' => $identifier,
            'raw_code' => $rawCode,
            'index' => $index,
            'extension' => $ext,
        ];
    }

    /**
     * Escanea el directorio de origen y realiza un mapeo con la base de datos de productos.
     *
     * @return array{
     *     total_files: int,
     *     matched_count: int,
     *     unmatched_count: int,
     *     items: array<int, array{
     *         filename: string,
     *         path: string,
     *         relative_url: string,
     *         filesize: int,
     *         filesize_formatted: string,
     *         parsed: array{identifier: string, raw_code: string, index: int, extension: string},
     *         product_id: int|null,
     *         product_description: string|null,
     *         is_matched: bool
     *     }>
     * }
     */
    public function scan(string $folder = 'imagenes_productos', array $options = []): array
    {
        $dir = $this->resolveSourcePath($folder);
        if (! File::isDirectory($dir)) {
            return [
                'total_files' => 0,
                'matched_count' => 0,
                'unmatched_count' => 0,
                'items' => [],
            ];
        }

        $field = in_array($options['field'] ?? 'sku', ['sku', 'barcode', 'id', 'product_type'], true)
            ? $options['field']
            : 'sku';

        $allFiles = File::files($dir);
        $parsedItems = [];
        $identifiers = [];

        foreach ($allFiles as $file) {
            $filename = $file->getFilename();
            $parsed = $this->parseFileName($filename, array_merge($options, ['field' => $field]));
            if (! $parsed) {
                continue;
            }

            $identifiers[] = $parsed['identifier'];
            $identifiers[] = $parsed['raw_code'];

            $parsedItems[] = [
                'filename' => $filename,
                'path' => $file->getRealPath(),
                'relative_url' => route('admin.import-images.preview', ['path' => $file->getRealPath()]),
                'filesize' => $file->getSize(),
                'filesize_formatted' => $this->formatBytes($file->getSize()),
                'parsed' => $parsed,
                'product_id' => null,
                'product_description' => null,
                'is_matched' => false,
            ];
        }

        // Búsqueda masiva en BD para optimizar queries (sin N+1)
        $uniqueIdentifiers = array_unique(array_filter($identifiers));
        $productsLookup = [];

        if (! empty($uniqueIdentifiers)) {
            $query = Product::query()->select(['id', 'sku', 'barcode', 'product_type', 'description']);

            if ($field === 'id') {
                $numericIds = array_filter($uniqueIdentifiers, 'is_numeric');
                if (! empty($numericIds)) {
                    $products = $query->whereIn('id', $numericIds)->get();
                } else {
                    $products = collect();
                }
            } else {
                $products = $query->whereIn($field, $uniqueIdentifiers)->get();
            }

            foreach ($products as $prod) {
                $key = (string) $prod->{$field};
                if (! isset($productsLookup[$key])) {
                    $productsLookup[$key] = $prod;
                }
                // Si el campo es id o numérico, asociar también sin pad
                $productsLookup[(string) $prod->id] = $prod;
            }
        }

        $matchedCount = 0;
        $unmatchedCount = 0;

        foreach ($parsedItems as &$item) {
            $identifier = $item['parsed']['identifier'];
            $raw = $item['parsed']['raw_code'];

            $product = $productsLookup[$identifier] ?? $productsLookup[$raw] ?? null;

            if ($product) {
                $item['product_id'] = $product->id;
                $item['product_description'] = $product->description;
                $item['is_matched'] = true;
                $matchedCount++;
            } else {
                $unmatchedCount++;
            }
        }
        unset($item);

        return [
            'total_files' => count($parsedItems),
            'matched_count' => $matchedCount,
            'unmatched_count' => $unmatchedCount,
            'items' => $parsedItems,
        ];
    }

    /**
     * Procesa un único ítem identificado por su ruta y producto.
     * Permite importación granular paso a paso desde Livewire sin timeouts HTTP.
     *
     * @return array{status: string, message: string, product_id: int|null}
     */
    public function importSingleItem(array $item, array $options = []): array
    {
        @set_time_limit(60);

        $overwrite = (bool) ($options['overwrite'] ?? true);
        $deleteSource = (bool) ($options['delete_source'] ?? false);
        $productId = $item['product_id'] ?? null;
        $filename = $item['filename'] ?? '';

        if (! $item['is_matched'] || ! $productId) {
            return [
                'status' => 'skipped',
                'message' => 'Producto no encontrado en catálogo.',
                'product_id' => null,
            ];
        }

        try {
            $result = $this->processSingleImage(
                filePath: $item['path'],
                productId: $productId,
                imageIndex: $item['parsed']['index'] ?? 1,
                overwrite: $overwrite
            );

            if ($result['status'] === 'imported') {
                Cache::forget("product_media_{$productId}");

                if ($deleteSource) {
                    @unlink($item['path']);
                }
            }

            return [
                'status' => $result['status'],
                'message' => $result['message'],
                'product_id' => $productId,
            ];
        } catch (Throwable $e) {
            Log::error("Error importando {$filename}: ".$e->getMessage());

            return [
                'status' => 'error',
                'message' => $e->getMessage(),
                'product_id' => $productId,
            ];
        }
    }

    /**
     * Procesa la importación de archivos hacia WebP y la estructura interna de OAGO.
     *
     * @param  array{
     *     folder?: string,
     *     field?: string,
     *     separator?: string,
     *     pad_length?: int,
     *     pad_char?: string,
     *     delete_source?: bool,
     *     overwrite?: bool,
     *     limit?: int
     * }  $options
     * @param  callable|null  $callback  fn(int $current, int $total, string $message, string $type)
     * @return array{
     *     processed: int,
     *     imported: int,
     *     skipped: int,
     *     errors: int,
     *     details: array<int, array{filename: string, product_id: int|null, status: string, message: string}>
     * }
     */
    public function import(array $options = [], ?callable $callback = null): array
    {
        @set_time_limit(0);

        $folder = $options['folder'] ?? 'imagenes_productos';
        $deleteSource = (bool) ($options['delete_source'] ?? false);
        $overwrite = (bool) ($options['overwrite'] ?? true);
        $limit = (int) ($options['limit'] ?? 0);

        $scanResult = $this->scan($folder, $options);
        $items = $scanResult['items'];

        if ($limit > 0) {
            $items = array_slice($items, 0, $limit);
        }

        $total = count($items);
        $stats = [
            'processed' => 0,
            'imported' => 0,
            'skipped' => 0,
            'errors' => 0,
            'details' => [],
        ];

        $touchedProductIds = [];

        foreach ($items as $index => $item) {
            $stats['processed']++;
            $currentNum = $index + 1;
            $filename = $item['filename'];
            $productId = $item['product_id'];

            if (! $item['is_matched'] || ! $productId) {
                $stats['skipped']++;
                $stats['details'][] = [
                    'filename' => $filename,
                    'product_id' => null,
                    'status' => 'skipped',
                    'message' => "No se encontró producto con {$options['field']}: '{$item['parsed']['identifier']}'",
                ];
                if ($callback) {
                    $callback($currentNum, $total, "Omitido {$filename} (Producto no encontrado)", 'warning');
                }

                continue;
            }

            try {
                $importResult = $this->processSingleImage(
                    filePath: $item['path'],
                    productId: $productId,
                    imageIndex: $item['parsed']['index'],
                    overwrite: $overwrite
                );

                if ($importResult['status'] === 'imported') {
                    $stats['imported']++;
                    $touchedProductIds[$productId] = true;

                    if ($deleteSource) {
                        @unlink($item['path']);
                    }

                    $stats['details'][] = [
                        'filename' => $filename,
                        'product_id' => $productId,
                        'status' => 'imported',
                        'message' => $importResult['message'],
                    ];

                    if ($callback) {
                        $callback($currentNum, $total, "Importado {$filename} -> Prod #{$productId}", 'success');
                    }
                } else {
                    $stats['skipped']++;
                    $stats['details'][] = [
                        'filename' => $filename,
                        'product_id' => $productId,
                        'status' => 'skipped',
                        'message' => $importResult['message'],
                    ];
                    if ($callback) {
                        $callback($currentNum, $total, "Omitido {$filename}: {$importResult['message']}", 'info');
                    }
                }
            } catch (Throwable $e) {
                $stats['errors']++;
                Log::error("Error importando imagen {$filename}: ".$e->getMessage(), ['trace' => $e->getTraceAsString()]);
                $stats['details'][] = [
                    'filename' => $filename,
                    'product_id' => $productId,
                    'status' => 'error',
                    'message' => $e->getMessage(),
                ];
                if ($callback) {
                    $callback($currentNum, $total, "Error en {$filename}: {$e->getMessage()}", 'error');
                }
            }
        }

        // Purgar cachés de productos afectados
        foreach (array_keys($touchedProductIds) as $pId) {
            Cache::forget("product_media_{$pId}");
        }

        return $stats;
    }

    /**
     * Calcula la ruta segmentada por hash MD5 (image_cache/{part1}/{part2}/{hash}.webp).
     *
     * @return array{
     *     directory: string,
     *     filename: string,
     *     storage_path: string,
     *     public_url: string,
     *     hash: string
     * }
     */
    public function computeHashedPath(string $key, ?int $extraIndex = null): array
    {
        $hash = md5($key);
        $part1 = substr($hash, 0, 2);
        $part2 = substr($hash, 2, 2);
        $directory = "image_cache/{$part1}/{$part2}";

        $filename = $extraIndex !== null
            ? "{$hash}-{$extraIndex}.webp"
            : "{$hash}.webp";

        $storagePath = "{$directory}/{$filename}";

        return [
            'directory' => $directory,
            'filename' => $filename,
            'storage_path' => $storagePath,
            'public_url' => "/storage/{$storagePath}",
            'hash' => $hash,
        ];
    }

    /**
     * Procesa una sola imagen, convirtiéndola a WebP y guardándola en image_cache/{p1}/{p2}/
     * con compatibilidad para Product::getMediaAttribute().
     */
    public function processSingleImage(string $filePath, int $productId, int $imageIndex, bool $overwrite): array
    {
        $product = Product::find($productId);
        if (! $product) {
            return [
                'status' => 'skipped',
                'message' => "Producto #{$productId} no encontrado.",
            ];
        }

        // Clave base para el hash: preferir sku o id único del producto
        $baseKey = $product->sku ?: (string) $product->id;
        $extraIdx = $imageIndex > 1 ? ($imageIndex - 1) : null;
        $hashed = $this->computeHashedPath($baseKey, $extraIdx);

        if (! Storage::disk('public')->exists($hashed['directory'])) {
            Storage::disk('public')->makeDirectory($hashed['directory']);
        }

        if (! $overwrite && Storage::disk('public')->exists($hashed['storage_path'])) {
            return [
                'status' => 'skipped',
                'message' => 'El archivo destino ya existe y sobrescribir está desactivado.',
            ];
        }

        // Convertir y guardar en WebP
        $webpData = $this->convertToWebp($filePath);
        if (! $webpData) {
            throw new \RuntimeException("No se pudo generar la versión WebP para: {$filePath}");
        }

        // Guardar en la estructura segmentada image_cache/{p1}/{p2}/
        Storage::disk('public')->put($hashed['storage_path'], $webpData);

        // También guardar en products/{id}/ para máxima redundancia y descubrimiento directo
        $stableDir = "products/{$productId}";
        if (! Storage::disk('public')->exists($stableDir)) {
            Storage::disk('public')->makeDirectory($stableDir);
        }
        $stableName = $imageIndex <= 1 ? 'main.webp' : "extra-{$extraIdx}.webp";
        Storage::disk('public')->put("{$stableDir}/{$stableName}", $webpData);

        // Si es la imagen principal, actualizar la columna products.image_url
        if ($imageIndex <= 1) {
            $product->image_url = $hashed['public_url'];
            $product->save();
        }

        return [
            'status' => 'imported',
            'message' => "Guardado en {$hashed['public_url']}",
        ];
    }

    /**
     * Convierte el archivo dado a datos WebP optimizados en memoria.
     */
    public function convertToWebp(string $filePath, int $quality = 80): ?string
    {
        if (! function_exists('imagewebp')) {
            throw new \RuntimeException('La extensión GD con soporte WebP (imagewebp) no está disponible en este entorno.');
        }

        // Elevar temporalmente el límite de memoria para procesamiento de fotos de alta resolución si es menor a 512M
        $memoryLimit = ini_get('memory_limit');
        if ($memoryLimit !== '-1' && (int) $memoryLimit < 512) {
            @ini_set('memory_limit', '512M');
        }

        $imageContent = file_get_contents($filePath);
        if ($imageContent === false) {
            return null;
        }

        $image = @imagecreatefromstring($imageContent);
        if ($image === false) {
            return null;
        }

        imagepalettetotruecolor($image);
        imagealphablending($image, true);
        imagesavealpha($image, true);

        ob_start();
        imagewebp($image, null, $quality);
        $webpData = ob_get_clean();
        imagedestroy($image);

        return $webpData ?: null;
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes <= 0) {
            return '0 B';
        }
        $units = ['B', 'KB', 'MB', 'GB'];
        $power = min((int) floor(log($bytes, 1024)), count($units) - 1);

        return number_format($bytes / pow(1024, $power), 2).' '.$units[$power];
    }
}
