<?php

namespace App\Services;

use Illuminate\Support\Facades\File;

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
}
