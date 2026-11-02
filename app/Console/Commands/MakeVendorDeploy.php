<?php

namespace App\Console\Commands;

use App\Services\VendorPackageService;
use App\Services\ZipPackageBuilder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use RuntimeException;

class MakeVendorDeploy extends Command
{
    protected $signature = 'make:vendor-deploy
                            {--name=deploy-vendor.zip : Nombre del ZIP resultante}
                            {--ignore-platform-reqs : Ignorar extensiones/versión de PHP de esta máquina al instalar}';

    protected $description = 'Genera un ZIP con vendor/ solo de producción (composer install --no-dev), listo para descomprimir en la raíz del hosting';

    public function handle(VendorPackageService $vendor, ZipPackageBuilder $zips): int
    {
        foreach (['composer.json', 'composer.lock'] as $required) {
            if (! File::exists(base_path($required))) {
                $this->error("❌ Falta {$required} en la raíz del proyecto.");

                return self::FAILURE;
            }
        }

        $workDir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'vendor-deploy-'.uniqid();

        try {
            $this->info('📦 Instalando dependencias de producción (composer install --no-dev)...');
            $vendor->install($workDir, (bool) $this->option('ignore-platform-reqs'));

            $report = $vendor->verify($workDir);

            if ($report['mismatched'] !== [] || $report['dev'] !== []) {
                $this->error('❌ Lo instalado no coincide con composer.lock.');
                $this->line('   Versiones distintas o faltantes: '.implode(', ', $report['mismatched']));
                $this->line('   Paquetes de desarrollo incluidos: '.implode(', ', $report['dev']));

                return self::FAILURE;
            }

            $zipName = $this->option('name');
            $zipPath = base_path($zipName);

            $livewireHash = $vendor->livewireAssetsHash($workDir);

            $this->info("🤐 Comprimiendo {$report['packages']} paquetes...");
            $count = $zips->build($zipPath, $vendor->files($workDir));
        } catch (RuntimeException $e) {
            $this->error('❌ '.$e->getMessage());

            return self::FAILURE;
        } finally {
            File::deleteDirectory($workDir);
        }

        $size = number_format(filesize($zipPath) / 1048576, 2, ',', '.');
        $this->info("✅ ¡Éxito! {$zipName}: {$report['packages']} paquetes, {$count} archivos, {$size} MB");

        if ($livewireHash) {
            $this->info("🔗 Incluye public/vendor/livewire (livewire.js?id={$livewireHash}), alineado con la versión instalada.");
        }

        $this->newLine();
        $this->line('Para publicarlo:');
        $this->line('  1. Subí el ZIP a la raíz del proyecto en el hosting.');
        $this->line('  2. Borrá vendor/ y public/vendor/livewire/ actuales y descomprimí el ZIP ahí, para no dejar archivos viejos.');
        $this->line('  3. Vaciá bootstrap/cache/*.php (packages.php y services.php) para que Laravel los regenere.');
        $this->line('  * Requiere el mismo composer.lock en el hosting y PHP '.$this->requiredPhp().' o superior.');

        return self::SUCCESS;
    }

    private function requiredPhp(): string
    {
        $composer = json_decode(File::get(base_path('composer.json')), true);

        return ltrim($composer['require']['php'] ?? '', '^>=~ ') ?: '8.x';
    }
}
