<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Symfony\Component\Process\Process;
use ZipArchive;

class MakeDeployZip extends Command
{
    protected $signature = 'make:deploy-zip 
                            {--name=deploy.zip : Nombre del archivo ZIP resultante}
                            {--engine-only : Excluir temas personalizados de clientes (resources/views/themes/*)}
                            {--full : Incluir todo (deshabilita la exclusión del motor)}
                            {--no_brand : Excluir imágenes de marca y sliders}
                            {--include-assets : Publicar assets de Livewire}';

    protected $description = 'Genera un paquete ZIP para despliegue detectando el mejor método (7-Zip o Nativo)';

    public function handle()
    {
        $engineOnly = $this->option('engine-only');
        $full = $this->option('full');

        if (! $engineOnly && ! $full && ! $this->hasOptionProvided('engine-only')) {
            $engineOnly = $this->confirm('¿Deseas exportar SOLO EL MOTOR (excluir recursos/views/themes/*)?', true);
        }

        $noBrand = $this->option('no_brand');
        if (! $noBrand) {
            $includeBrand = $this->confirm('¿Desea incluir las imágenes de marca por defecto (pueden sobrescribir las de producción)?', false);
            $noBrand = ! $includeBrand;
        }

        $includeAssets = $this->option('include-assets');
        $zipName = $this->option('name');
        $zipPath = base_path($zipName);

        $this->info('🚀 Iniciando proceso de empaquetado unificado...');

        if ($engineOnly) {
            $this->info('⚙️ Modo SOLO MOTOR activo: se excluirá resources/views/themes/*');
        }

        if ($noBrand) {
            $this->info('🚫 Opción --no_brand activa: se excluirán imágenes de marca y sliders.');
        }

        // 1. Preparación de Assets
        $this->info('📦 Ejecutando npm run build...');
        $process = PHP_OS_FAMILY === 'Windows'
            ? Process::fromShellCommandline('npm run build')
            : new Process(['npm', 'run', 'build']);
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
            $this->info('⏩ Omitiendo publicación de assets.');
        }

        // Limpieza de archivos temporales de desarrollo
        if (file_exists(public_path('hot'))) {
            @unlink(public_path('hot'));
            $this->info('🧹 Archivo public/hot eliminado preventivamente.');
        }

        if (file_exists($zipPath)) {
            @unlink($zipPath);
        }

        // 2. Detección de 7-Zip
        $sevenZipPath = $this->getSevenZipPath();

        if ($sevenZipPath) {
            return $this->useSevenZip($sevenZipPath, $zipPath, $noBrand, $engineOnly);
        }

        return $this->useNativeZip($zipPath, $noBrand, $engineOnly);
    }

    protected function hasOptionProvided(string $name): bool
    {
        return $this->input->hasParameterOption('--'.$name);
    }

    private function getSevenZipPath(): ?string
    {
        // 1. Intentar ruta estándar de Windows
        $winPath = 'C:\Program Files\7-Zip\7z.exe';
        if (PHP_OS_FAMILY === 'Windows' && file_exists($winPath)) {
            return $winPath;
        }

        // 2. Intentar rutas estándar de macOS (Homebrew x86_64 y Apple Silicon / MacPorts)
        if (PHP_OS_FAMILY === 'Darwin') {
            $macPaths = [
                '/opt/homebrew/bin/7z',
                '/usr/local/bin/7z',
                '/opt/local/bin/7z',
                '/opt/homebrew/bin/7za',
                '/usr/local/bin/7za',
            ];
            foreach ($macPaths as $macPath) {
                if (file_exists($macPath) && is_executable($macPath)) {
                    return $macPath;
                }
            }
        }

        // 3. Intentar rutas estándar de Linux
        if (PHP_OS_FAMILY === 'Linux') {
            $linuxPaths = [
                '/usr/bin/7z',
                '/usr/local/bin/7z',
                '/usr/bin/7za',
                '/usr/local/bin/7za',
            ];
            foreach ($linuxPaths as $linuxPath) {
                if (file_exists($linuxPath) && is_executable($linuxPath)) {
                    return $linuxPath;
                }
            }
        }

        // 4. Intentar buscar en el PATH del sistema (7z y alternativa p7zip: 7za)
        $binaries = ['7z', '7za'];
        foreach ($binaries as $binary) {
            $command = PHP_OS_FAMILY === 'Windows' ? "where $binary" : "which $binary";
            $process = Process::fromShellCommandline($command);
            $process->run();

            if ($process->isSuccessful()) {
                $lines = array_filter(array_map('trim', explode("\n", $process->getOutput())));
                $firstLine = reset($lines);
                if ($firstLine && (PHP_OS_FAMILY === 'Windows' || (file_exists($firstLine) && is_executable($firstLine)))) {
                    return $firstLine;
                }
            }
        }

        return null;
    }

    private function useSevenZip(string $binary, string $zipPath, bool $noBrand, bool $engineOnly = false): int
    {
        $this->info("💎 Usando 7-Zip para máxima compatibilidad (Motor: $binary)");

        $exclusions = [
            'vendor', 'node_modules', '.git', '.env', 'public/hot',
            'storage/logs/*', 'storage/framework/cache/data/*',
            'storage/framework/sessions/*', 'storage/framework/views/*',
            'storage/app/private/*', 'storage/app/livewire-tmp/*',
            'bootstrap/cache/*', 'public/storage',
            '*.zip', '*.sql', '*.sqlite',
            '.agents', '.claude', '.gemini', '.vscode', '.postman',
            'ignore.me', '.DS_Store', 'Thumbs.db',
        ];

        if ($engineOnly) {
            $exclusions[] = 'resources/views/themes/*';
        }

        if ($noBrand) {
            $exclusions[] = 'public/imgs/*';
        }

        $command = [$binary, 'a', '-tzip', $zipPath, '.'];
        foreach ($exclusions as $exclude) {
            $command[] = "-xr!$exclude";
        }

        $this->info('🤐 Comprimiendo...');
        $process = new Process($command, base_path());
        $process->setTimeout(600);
        $process->run();

        if (! $process->isSuccessful()) {
            $this->error('❌ Error al ejecutar 7-Zip.');

            return 1;
        }

        $this->info('✅ ¡Éxito! Paquete generado con 7-Zip: '.basename($zipPath));

        return 0;
    }

    private function useNativeZip(string $zipPath, bool $noBrand, bool $engineOnly = false): int
    {
        $this->warn('⚠️ 7-Zip no detectado. Usando librería nativa PHP ZipArchive.');

        $zip = new ZipArchive;
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            $this->error('❌ No se pudo crear el archivo ZIP.');

            return 1;
        }

        // Agregar carpetas esenciales vacías
        $essentialDirs = [
            'storage/app/public', 'storage/app/livewire-tmp',
            'storage/framework/cache/data', 'storage/framework/sessions',
            'storage/framework/views', 'storage/logs', 'bootstrap/cache',
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
            $relativePath = ltrim(substr($filePath, strlen($rootPath)), DIRECTORY_SEPARATOR);
            $zipPathInternal = str_replace('\\', '/', $relativePath);

            // Reglas de exclusión (Paridad con 7z)
            if (Str::startsWith($zipPathInternal, 'vendor/') ||
                Str::startsWith($zipPathInternal, 'node_modules/') ||
                Str::startsWith($zipPathInternal, '.git/') ||
                Str::startsWith($zipPathInternal, 'storage/logs/') ||
                Str::startsWith($zipPathInternal, 'storage/framework/') ||
                Str::startsWith($zipPathInternal, 'storage/app/private/') ||
                Str::startsWith($zipPathInternal, 'storage/app/livewire-tmp/') ||
                Str::startsWith($zipPathInternal, 'bootstrap/cache/') ||
                Str::startsWith($zipPathInternal, 'public/storage') ||
                Str::endsWith($zipPathInternal, '.zip') ||
                Str::endsWith($zipPathInternal, '.sql') ||
                Str::endsWith($zipPathInternal, '.sqlite') ||
                Str::startsWith($zipPathInternal, '.agents') ||
                Str::startsWith($zipPathInternal, '.claude') ||
                Str::startsWith($zipPathInternal, '.gemini') ||
                Str::startsWith($zipPathInternal, '.vscode') ||
                Str::startsWith($zipPathInternal, '.postman') ||
                Str::startsWith($zipPathInternal, 'ignore.me/') ||
                basename($zipPathInternal) === '.DS_Store' ||
                basename($zipPathInternal) === 'Thumbs.db' ||
                $zipPathInternal === '.env' ||
                $zipPathInternal === 'public/hot'
            ) {
                // Permitir public/vendor
                if (! Str::startsWith($zipPathInternal, 'public/vendor/')) {
                    continue;
                }
            }

            if ($engineOnly && Str::startsWith($zipPathInternal, 'resources/views/themes/')) {
                continue;
            }

            if ($noBrand && Str::startsWith($zipPathInternal, 'public/imgs/')) {
                continue;
            }

            if ($zipPathInternal === basename($zipPath)) {
                continue;
            }

            $zip->addFile($filePath, $zipPathInternal);
            if (method_exists($zip, 'setEncryptionName')) {
                $zip->setEncryptionName($zipPathInternal, ZipArchive::EM_NONE);
            }
            $count++;
        }

        $this->info("🤐 Comprimiendo {$count} archivos con ZipArchive...");
        $zip->close();

        $this->info('✅ ¡Éxito! Paquete generado (Nativo): '.basename($zipPath));

        return 0;
    }
}
