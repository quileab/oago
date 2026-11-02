<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use RuntimeException;
use Symfony\Component\Process\ExecutableFinder;

class VendorPackageService
{
    /**
     * Patrones que no hacen falta en el hosting (se comparan con fnmatch contra cada segmento de la ruta).
     *
     * @var array<int, string>
     */
    public const EXCLUDES = ['.git*', '.DS_Store', '__MACOSX', 'Thumbs.db'];

    public function __construct(private ZipPackageBuilder $zips) {}

    /**
     * Instala solo las dependencias de producción en un directorio de trabajo aparte,
     * a partir de composer.json y composer.lock, sin tocar el vendor/ local.
     */
    public function install(string $workDir, bool $ignorePlatformReqs = false): void
    {
        File::ensureDirectoryExists($workDir);
        File::copy(base_path('composer.json'), "{$workDir}/composer.json");
        File::copy(base_path('composer.lock'), "{$workDir}/composer.lock");

        $command = [
            ...$this->composerCommand(),
            'install', '--no-dev', '--prefer-dist', '--optimize-autoloader',
            '--no-scripts', '--no-progress', '--no-interaction',
        ];

        if ($ignorePlatformReqs) {
            $command[] = '--ignore-platform-reqs';
        }

        $result = Process::path($workDir)->timeout(600)->run($command);

        if ($result->failed()) {
            $detail = trim($result->errorOutput() ?: $result->output());

            throw new RuntimeException('composer install falló'.($detail !== '' ? ": {$detail}" : '. ¿Composer está instalado y en el PATH?'));
        }
    }

    /**
     * Compara lo instalado con composer.lock: mismas versiones y sin paquetes de desarrollo.
     *
     * @return array{packages: int, mismatched: array<int, string>, dev: array<int, string>}
     */
    public function verify(string $workDir): array
    {
        $lock = json_decode(File::get(base_path('composer.lock')), true);
        $installedFile = "{$workDir}/vendor/composer/installed.json";

        if (! File::exists($installedFile)) {
            throw new RuntimeException('No se generó vendor/composer/installed.json.');
        }

        $installedJson = json_decode(File::get($installedFile), true);
        $installed = collect($installedJson['packages'] ?? $installedJson)
            ->mapWithKeys(fn (array $package) => [$package['name'] => $package['version']]);

        $expected = collect($lock['packages'] ?? [])
            ->mapWithKeys(fn (array $package) => [$package['name'] => $package['version']]);

        $devOnly = collect($lock['packages-dev'] ?? [])->pluck('name')->diff($expected->keys());

        return [
            'packages' => $installed->count(),
            'mismatched' => $expected
                ->filter(fn (string $version, string $name) => $installed->get($name) !== $version)
                ->keys()
                ->values()
                ->all(),
            'dev' => $installed->keys()->intersect($devOnly)->values()->all(),
        ];
    }

    /**
     * Archivos de vendor/ listos para el ZIP, con rutas relativas a la raíz del proyecto.
     * Incluye public/vendor/livewire con los assets de la misma versión instalada (lo que copia
     * `livewire:publish --assets`), para que el JS publicado nunca quede desfasado del servidor.
     *
     * @return array<string, string> ruta en el ZIP => ruta absoluta
     */
    public function files(string $workDir): array
    {
        return [
            ...$this->zips->collect("{$workDir}/vendor", 'vendor', self::EXCLUDES),
            ...$this->zips->collect("{$workDir}/vendor/livewire/livewire/dist", 'public/vendor/livewire', self::EXCLUDES),
        ];
    }

    /**
     * Hash de livewire.js que trae la versión instalada (null si Livewire no está instalado).
     */
    public function livewireAssetsHash(string $workDir): ?string
    {
        $manifest = "{$workDir}/vendor/livewire/livewire/dist/manifest.json";

        if (! File::exists($manifest)) {
            return null;
        }

        return json_decode(File::get($manifest), true)['/livewire.js'] ?? null;
    }

    /**
     * @return array<int, string>
     */
    private function composerCommand(): array
    {
        $binary = (new ExecutableFinder)->find('composer');

        if ($binary !== null) {
            return [$binary];
        }

        $phar = base_path('composer.phar');

        return File::exists($phar) ? [PHP_BINARY, $phar] : ['composer'];
    }
}
