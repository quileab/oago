<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RuntimeException;
use ZipArchive;

class ThemePackageService
{
    public function __construct(private ZipPackageBuilder $zips) {}

    /**
     * Patrones que nunca viajan al hosting (se comparan con fnmatch contra cada segmento de la ruta).
     *
     * @var array<int, string>
     */
    public const DEFAULT_EXCLUDES = [
        '.*', '__MACOSX', 'Thumbs.db',
        '*.zip', '*.docx', '*.doc', '*.xls', '*.xlsx', '*.sql', '*.bak', '*~',
    ];

    /**
     * Nombres reservados que no pueden sobrescribirse mediante importación.
     *
     * @var array<int, string>
     */
    public const RESERVED_THEMES = [
        'default', 'livewire', 'components', 'layouts', 'vendor', 'emails', 'auth', 'errors',
    ];

    /**
     * Extensiones permitidas para archivos públicos del tema.
     *
     * @var array<int, string>
     */
    public const ALLOWED_PUBLIC_EXTENSIONS = [
        'png', 'jpg', 'jpeg', 'webp', 'svg', 'gif', 'ico',
        'woff', 'woff2', 'ttf', 'eot', 'webmanifest',
    ];

    /**
     * Temas disponibles: los directorios de resources/views/themes.
     *
     * @return array<int, string>
     */
    public function themes(): array
    {
        $viewsPath = resource_path('views/themes');

        if (! File::isDirectory($viewsPath)) {
            return [];
        }

        $themes = collect(File::directories($viewsPath))
            ->map(fn (string $dir) => basename($dir))
            ->reject(fn (string $name) => str_starts_with($name, '.'))
            ->sort()
            ->values()
            ->all();

        return $themes;
    }

    /**
     * Archivos de un tema, con la ruta que tendrán dentro del ZIP (relativa a la raíz del proyecto).
     *
     * @param  array<int, string>  $exclude  Patrones extra a excluir.
     * @return array<string, string> ruta en el ZIP => ruta absoluta
     */
    public function files(string $theme, array $exclude = [], bool $withBuild = false): array
    {
        $patterns = [...self::DEFAULT_EXCLUDES, ...$exclude];
        $files = [
            ...$this->zips->collect(resource_path("views/themes/{$theme}"), "resources/views/themes/{$theme}", $patterns),
            ...$this->zips->collect(public_path("themes/{$theme}"), "public/themes/{$theme}", $patterns),
        ];

        $css = resource_path("css/themes/{$theme}.css");
        if (File::exists($css)) {
            $files["resources/css/themes/{$theme}.css"] = $css;
        }

        if ($withBuild) {
            $files += $this->zips->collect(public_path('build'), 'public/build', $patterns);
        }

        ksort($files);

        return $files;
    }

    /**
     * Exporta un tema en formato portable del gestor web (`views/`, `css/`, `public/`) excluyendo archivos ocultos y temporales.
     */
    public function exportToZip(string $theme): string
    {
        $theme = trim($theme);
        if (! $this->isValidThemeName($theme) || in_array(strtolower($theme), self::RESERVED_THEMES, true)) {
            throw new RuntimeException('Selecciona un tema válido para exportar.');
        }

        $exportDir = storage_path('app/private/theme-exports');
        File::ensureDirectoryExists($exportDir);

        $zipPath = "{$exportDir}/{$theme}-theme-".now()->format('Ymd_His').'.zip';

        $files = [
            ...$this->zips->collect(resource_path("views/themes/{$theme}"), 'views', self::DEFAULT_EXCLUDES),
            ...$this->zips->collect(public_path("themes/{$theme}"), 'public', self::DEFAULT_EXCLUDES),
        ];

        $cssFile = resource_path("css/themes/{$theme}.css");
        if (File::exists($cssFile)) {
            $files["css/{$theme}.css"] = $cssFile;
        }

        if ($files === []) {
            throw new RuntimeException("No se encontraron archivos para exportar en el tema '{$theme}'.");
        }

        ksort($files);
        $this->zips->build($zipPath, $files);

        return $zipPath;
    }

    /**
     * Valida de forma estricta e importa un tema desde un ZIP portable.
     * Previene Zip Slip (`..`, rutas absolutas, backslashes, bytes nulos) y filtra por lista blanca de extensiones.
     */
    public function importFromZip(string $zipPath): string
    {
        if (! is_file($zipPath)) {
            throw new RuntimeException('El archivo ZIP no existe o no es legible.');
        }

        $zip = new ZipArchive;
        if ($zip->open($zipPath) !== true) {
            throw new RuntimeException('No se pudo abrir el archivo ZIP.');
        }

        try {
            $detectedTheme = null;
            $plan = [];

            for ($i = 0; $i < $zip->numFiles; $i++) {
                $entry = $zip->getNameIndex($i);
                if (! is_string($entry)) {
                    continue;
                }

                $this->assertSafeZipPath($entry);

                if (str_ends_with($entry, '/')) {
                    continue;
                }

                if ($this->isIgnoredMetadataEntry($entry)) {
                    continue;
                }

                if (str_starts_with($entry, 'views/')) {
                    $relative = substr($entry, strlen('views/'));
                    if ($relative === '' || ! $this->isAllowedViewFile($relative)) {
                        throw new RuntimeException("Archivo o extensión no permitida en vistas: {$entry}");
                    }

                    $plan[] = ['index' => $i, 'bucket' => 'views', 'relative' => $relative];
                } elseif (str_starts_with($entry, 'css/')) {
                    $relative = substr($entry, strlen('css/'));
                    if (strtolower(pathinfo($relative, PATHINFO_EXTENSION)) !== 'css') {
                        throw new RuntimeException("Archivo o extensión no permitida en CSS: {$entry}");
                    }

                    if ($detectedTheme === null) {
                        $detectedTheme = Str::before(basename($relative), '.css');
                    }

                    $plan[] = ['index' => $i, 'bucket' => 'css', 'relative' => $relative];
                } elseif (str_starts_with($entry, 'public/')) {
                    $relative = substr($entry, strlen('public/'));
                    $extension = strtolower(pathinfo($relative, PATHINFO_EXTENSION));

                    if ($relative === '' || ! in_array($extension, self::ALLOWED_PUBLIC_EXTENSIONS, true)) {
                        throw new RuntimeException("Archivo o extensión no permitida en assets públicos: {$entry}");
                    }

                    $plan[] = ['index' => $i, 'bucket' => 'public', 'relative' => $relative];
                }
            }

            if ($detectedTheme === null || ! $this->isValidThemeName($detectedTheme)) {
                throw new RuntimeException('No se pudo detectar un nombre de tema válido en el ZIP. Asegúrate de exportar desde este gestor.');
            }

            if (in_array(strtolower($detectedTheme), self::RESERVED_THEMES, true)) {
                throw new RuntimeException("El nombre de tema '{$detectedTheme}' está reservado y no puede importarse.");
            }

            $viewsBase = $this->normalizePath(resource_path("views/themes/{$detectedTheme}"));
            $publicBase = $this->normalizePath(public_path("themes/{$detectedTheme}"));
            $cssDest = $this->normalizePath(resource_path("css/themes/{$detectedTheme}.css"));

            foreach ($plan as $item) {
                $content = $zip->getFromIndex($item['index']);
                if ($content === false) {
                    continue;
                }

                $dest = match ($item['bucket']) {
                    'views' => $this->normalizePath("{$viewsBase}/{$item['relative']}"),
                    'css' => $cssDest,
                    'public' => $this->normalizePath("{$publicBase}/{$item['relative']}"),
                };

                $expectedRoot = match ($item['bucket']) {
                    'views' => $viewsBase.'/',
                    'css' => dirname($cssDest).'/',
                    'public' => $publicBase.'/',
                };

                if (! str_starts_with($dest, $expectedRoot)) {
                    throw new RuntimeException("Ruta de destino fuera del directorio del tema: {$dest}");
                }

                File::ensureDirectoryExists(dirname($dest));
                File::put($dest, $content);
            }

            return $detectedTheme;
        } finally {
            $zip->close();
        }
    }

    /**
     * Valida que una ruta interna del ZIP no intente escape de directorio o uso de caracteres inseguros.
     */
    private function assertSafeZipPath(string $entry): void
    {
        if (
            $entry === ''
            || str_contains($entry, "\0")
            || str_contains($entry, '\\')
            || str_starts_with($entry, '/')
            || preg_match('/^[a-zA-Z]:/', $entry)
        ) {
            throw new RuntimeException("Ruta insegura detectada en el archivo ZIP: {$entry}");
        }

        foreach (explode('/', rtrim($entry, '/')) as $segment) {
            if ($segment === '..' || $segment === '.') {
                throw new RuntimeException("Intento de traversal detectado en el archivo ZIP: {$entry}");
            }
        }
    }

    private function isIgnoredMetadataEntry(string $entry): bool
    {
        if (str_starts_with($entry, '__MACOSX/')) {
            return true;
        }

        $base = basename($entry);

        return str_starts_with($base, '.') || in_array($base, ['Thumbs.db', 'desktop.ini'], true);
    }

    private function isAllowedViewFile(string $relative): bool
    {
        $lower = strtolower($relative);

        return str_ends_with($lower, '.blade.php')
            || pathinfo($lower, PATHINFO_EXTENSION) === 'json';
    }

    private function isValidThemeName(string $theme): bool
    {
        return $theme !== '' && (bool) preg_match('/^[a-zA-Z0-9_-]+$/', $theme);
    }

    private function normalizePath(string $path): string
    {
        return str_replace('\\', '/', $path);
    }
}
