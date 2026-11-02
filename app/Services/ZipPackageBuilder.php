<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use RuntimeException;
use ZipArchive;

class ZipPackageBuilder
{
    /**
     * Junta los archivos de un directorio con la ruta que tendrán dentro del ZIP.
     *
     * @param  array<int, string>  $patterns  Patrones fnmatch, comparados contra cada segmento de la ruta.
     * @return array<string, string> ruta en el ZIP => ruta absoluta
     */
    public function collect(string $directory, string $zipBase, array $patterns = []): array
    {
        if (! File::isDirectory($directory)) {
            return [];
        }

        $root = rtrim(str_replace('\\', '/', $directory), '/');
        $files = [];

        foreach (File::allFiles($directory, hidden: true) as $file) {
            $relative = str_replace('\\', '/', $file->getRelativePathname());

            if ($this->isExcluded($relative, $patterns)) {
                continue;
            }

            $files["{$zipBase}/{$relative}"] = "{$root}/{$relative}";
        }

        return $files;
    }

    /**
     * Crea el ZIP con ZipArchive (sin binarios externos, apto para hosting compartido).
     *
     * @param  array<string, string>  $files  ruta en el ZIP => ruta absoluta
     * @return int Cantidad de archivos agregados.
     */
    public function build(string $zipPath, array $files): int
    {
        if (File::exists($zipPath)) {
            File::delete($zipPath);
        }

        $zip = new ZipArchive;
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('No se pudo crear el archivo ZIP.');
        }

        $now = time();
        foreach ($files as $zipEntry => $absolute) {
            $zip->addFile($absolute, $zipEntry);

            // Fecha actual: así Blade recompila las vistas aunque el hosting tenga copias compiladas más nuevas.
            if (method_exists($zip, 'setMtimeName')) {
                $zip->setMtimeName($zipEntry, $now);
            }
        }

        $zip->close();

        return count($files);
    }

    /**
     * @param  array<int, string>  $patterns
     */
    private function isExcluded(string $relativePath, array $patterns): bool
    {
        $segments = explode('/', $relativePath);

        foreach ($patterns as $pattern) {
            $pattern = trim($pattern, '/');

            if ($pattern === '') {
                continue;
            }

            if (fnmatch($pattern, $relativePath)) {
                return true;
            }

            foreach ($segments as $segment) {
                if (fnmatch($pattern, $segment)) {
                    return true;
                }
            }
        }

        return false;
    }
}
