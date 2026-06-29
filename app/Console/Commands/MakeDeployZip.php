<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Symfony\Component\Process\Process;
use ZipArchive;

use function Laravel\Prompts\confirm;

class MakeDeployZip extends Command
{
    protected $signature = 'make:deploy-zip {--name=deploy.zip} {--include-branding} {--include-assets}';

    protected $description = 'Genera un paquete ZIP para despliegue detectando el mejor método (7-Zip o Nativo)';

    public function handle()
    {
        $includeBranding = $this->option('include-branding') ?: confirm(
            label: '¿Deseas incluir las imágenes de branding (public/imgs y sliders)?',
            default: false
        );
        $noBrand = ! $includeBranding;

        $includeAssets = $this->option('include-assets');
        $zipName = $this->option('name');

        // Usaremos un nombre temporal único
        $finalPath = base_path($zipName);
        $tempZipName = 'temp_'.time().'_'.$zipName;
        $tempPath = base_path($tempZipName);

        $this->info('🚀 Iniciando proceso de empaquetado unificado...');

        if ($noBrand) {
            $this->info('🚫 Se excluirán imágenes de marca y sliders.');
        }

        // 1. Preparación de Assets
        $this->info('📦 Ejecutando npm run build...');
        $process = new Process(['npm', 'run', 'build']);
        $process->setTimeout(300);
        $process->run();

        if (! $process->isSuccessful()) {
            $this->error('❌ Error en npm run build.');

            return 1;
        }

        if ($includeAssets) {
            $this->info('📡 Publicando assets de Livewire...');
            $this->call('livewire:publish', ['--assets' => true]);
        } else {
            $this->info('⏩ Omitiendo publicación de assets. (Usa --include-assets para publicarlos)');
        }

        // Limpieza previa
        if (file_exists($finalPath)) {
            @unlink($finalPath);
        }
        if (file_exists($tempPath)) {
            @unlink($tempPath);
        }

        // 2. Detección de 7-Zip
        $sevenZipPath = $this->getSevenZipPath();

        if ($sevenZipPath) {
            return $this->useSevenZip($sevenZipPath, $tempZipName, $tempPath, $zipName, $finalPath, $noBrand);
        }

        return $this->useNativeZip($tempZipName, $tempPath, $zipName, $finalPath, $noBrand);
    }

    private function getSevenZipPath(): ?string
    {
        // 1. Intentar ruta estándar de Windows
        $winPath = 'C:\Program Files\7-Zip\7z.exe';
        if (PHP_OS_FAMILY === 'Windows' && file_exists($winPath)) {
            return $winPath;
        }

        // 2. Intentar buscar en el PATH (Linux o Windows con PATH configurado)
        $command = PHP_OS_FAMILY === 'Windows' ? 'where 7z' : 'which 7z';
        $process = Process::fromShellCommandline($command);
        $process->run();

        if ($process->isSuccessful()) {
            return trim($process->getOutput());
        }

        return null;
    }

    private function useSevenZip(string $binary, string $tempZipName, string $tempPath, string $zipName, string $finalPath, bool $noBrand): int
    {
        $this->info("💎 Usando 7-Zip para máxima compatibilidad (Motor: $binary)");

        $exclusions = [
            'vendor', 'node_modules', '.git', '.env',
            'storage/logs/*', 'storage/framework/cache/data/*',
            'storage/framework/sessions/*', 'storage/framework/views/*',
            'storage/app/private/*', 'storage/app/livewire-tmp/*',
            'bootstrap/cache/*', 'public/storage',
            '*.zip', '*.sql', '*.sqlite',
            '.agents', '.claude', '.gemini', '.vscode', '.postman',
            '.DS_Store', 'Thumbs.db', 'image_cache*',
        ];

        if ($noBrand) {
            $exclusions[] = 'public/imgs/*';
            $exclusions[] = 'storage/app/public/slider/*';
        }

        $command = [$binary, 'a', '-tzip', $tempPath, '.'];
        foreach ($exclusions as $exclude) {
            $command[] = "-xr!$exclude";
        }

        // Add an exclusion for the temp zip itself
        $command[] = "-xr!$tempZipName";
        $command[] = "-xr!$zipName";

        $this->info('🤐 Comprimiendo...');
        $process = new Process($command, base_path());
        $process->setTimeout(600);
        $process->run();

        if (! $process->isSuccessful()) {
            $this->error('❌ Error al ejecutar 7-Zip.');

            if (file_exists($tempPath)) {
                @unlink($tempPath);
            }

            return 1;
        }

        // Renombrar al nombre final
        if (! @rename($tempPath, $finalPath)) {
            $this->error("❌ No se pudo renombrar el archivo a {$zipName}, pero se creó como {$tempZipName}");

            return 1;
        }

        $this->info("✅ ¡Éxito! Paquete generado con 7-Zip: {$zipName}");
        $this->line('🚀 Listo para subir a tu hosting compartido.');

        return 0;
    }

    private function useNativeZip(string $tempZipName, string $tempPath, string $zipName, string $finalPath, bool $noBrand): int
    {
        $this->warn('⚠️ 7-Zip no detectado. Usando librería nativa PHP ZipArchive.');

        $zip = new ZipArchive;
        if ($zip->open($tempPath, ZipArchive::CREATE) !== true) {
            $this->error('❌ No se pudo crear el archivo ZIP en la raíz.');

            return 1;
        }

        $this->info('📂 Analizando y agregando archivos...');

        // Agregar carpetas esenciales vacías
        $essentialDirs = [
            'storage/app/public',
            'storage/app/livewire-tmp',
            'storage/framework/cache/data',
            'storage/framework/sessions',
            'storage/framework/views',
            'storage/logs',
            'bootstrap/cache',
        ];
        foreach ($essentialDirs as $dir) {
            $zip->addEmptyDir($dir);
        }

        $rootPath = realpath(base_path());
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($rootPath, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        $count = 0;
        foreach ($files as $name => $file) {
            $filePath = $file->getRealPath();
            $relativePath = ltrim(str_replace($rootPath, '', $filePath), DIRECTORY_SEPARATOR);

            // Convert Windows separators to Linux separators for ZIP internal structure
            $zipPath = str_replace(DIRECTORY_SEPARATOR, '/', $relativePath);

            // --- REGLAS DE EXCLUSIÓN ESTRICTAS ---

            // 1. Excluir carpetas pesadas, cache de imágenes y de desarrollo
            if ($zipPath === 'vendor' || Str::startsWith($zipPath, 'vendor/') ||
                Str::startsWith($zipPath, 'node_modules/') ||
                Str::contains($zipPath, 'image_cache/') ||
                Str::startsWith($zipPath, 'public/storage') || // EXCLUIR el link de storage
                Str::startsWith($zipPath, '.git/')) {
                // EXCEPCIÓN: Permitir public/vendor (donde se publican assets como Livewire)
                if (! Str::startsWith($zipPath, 'public/vendor/')) {
                    continue;
                }
            }

            // 1.1 Exclusión de Branding (si se solicita)
            if ($noBrand) {
                if (Str::startsWith($zipPath, 'public/imgs/') || Str::startsWith($zipPath, 'storage/app/public/slider/')) {
                    continue;
                }
            }

            // 2. Excluir carpeta storage (excepto app/public que es lo que se vincula) y bootstrap/cache
            // Nota: El código actual excluye TODO storage/.
            if (Str::startsWith($zipPath, 'storage/')) {
                // Permitir archivos en storage/app/public/ para que el link simbólico funcione en destino
                if (! Str::startsWith($zipPath, 'storage/app/public/')) {
                    continue;
                }
            }

            if (Str::startsWith($zipPath, 'bootstrap/cache/')) {
                continue;
            }

            // 3. Excluir archivos ocultos, PERO permitir los de public/build (como .vite/)
            $pathParts = explode('/', $zipPath);
            if (collect($pathParts)->contains(function ($part) use ($zipPath) {
                if (Str::startsWith($zipPath, 'public/build/')) {
                    return false; // No excluir nada dentro de public/build
                }

                return Str::startsWith($part, '.') && $part !== '.htaccess';
            })) {
                continue;
            }

            // 4. Excluir archivos de base de datos local (sqlite) y otros archivos ZIP
            if (Str::endsWith($zipPath, '.sqlite') || Str::endsWith($zipPath, '.zip')) {
                continue;
            }

            // 5. No incluirse a sí mismo
            if ($zipPath === $tempZipName || $zipPath === $zipName) {
                continue;
            }

            $zip->addFile($filePath, $zipPath);
            $count++;
        }

        $this->info("🤐 Comprimiendo {$count} archivos... (esto puede tardar)");

        // El error Permission Denied suele ser aquí. Intentamos capturarlo.
        try {
            $closed = $zip->close();
        } catch (\Exception $e) {
            $closed = false;
        }

        if (! $closed) {
            $this->error('❌ Error: Windows o un Antivirus bloqueó el cierre del archivo ZIP.');
            $this->line('💡 Intenta desactivar temporalmente el Antivirus o cerrar programas que usen la carpeta.');
            if (file_exists($tempPath)) {
                @unlink($tempPath);
            }

            return 1;
        }

        // 3. Renombrar al nombre final
        if (! @rename($tempPath, $finalPath)) {
            $this->error("❌ No se pudo renombrar el archivo a {$zipName}, pero se creó como {$tempZipName}");

            return 1;
        }

        $this->info("✅ ¡Éxito! Paquete generado (Nativo): {$zipName}");
        $this->line('🚀 Listo para subir a tu hosting compartido.');

        return 0;
    }
}
