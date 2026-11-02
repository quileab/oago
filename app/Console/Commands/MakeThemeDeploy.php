<?php

namespace App\Console\Commands;

use App\Services\ThemePackageService;
use App\Services\ZipPackageBuilder;
use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

use function Laravel\Prompts\select;

class MakeThemeDeploy extends Command
{
    protected $signature = 'make:theme-deploy
                            {theme? : Nombre del tema (si se omite se muestra la lista)}
                            {--name= : Nombre del ZIP resultante (por defecto deploy-theme-{tema}.zip)}
                            {--with-build : Ejecutar npm run build e incluir public/build (clases Tailwind nuevas)}
                            {--exclude=* : Patrones extra a excluir, por ejemplo --exclude=correcciones}';

    protected $description = 'Genera un ZIP solo con un tema, listo para subir y descomprimir en la raíz del hosting';

    public function handle(ThemePackageService $packager, ZipPackageBuilder $zips): int
    {
        $themes = $packager->themes();

        if ($themes === []) {
            $this->error('❌ No hay temas en resources/views/themes.');

            return self::FAILURE;
        }

        $theme = $this->argument('theme');

        if (! $theme) {
            if (! $this->input->isInteractive()) {
                $this->error('❌ Indicá el tema como argumento. Disponibles: '.implode(', ', $themes));

                return self::FAILURE;
            }

            $theme = select(label: '¿Qué tema querés empaquetar?', options: $themes);
        }

        if (! in_array($theme, $themes, true)) {
            $this->error("❌ El tema '{$theme}' no existe. Disponibles: ".implode(', ', $themes));

            return self::FAILURE;
        }

        $withBuild = (bool) $this->option('with-build');

        if ($withBuild && ! $this->runBuild()) {
            return self::FAILURE;
        }

        $files = $packager->files($theme, (array) $this->option('exclude'), $withBuild);

        if ($files === []) {
            $this->error("❌ No se encontraron archivos para el tema '{$theme}'.");

            return self::FAILURE;
        }

        $zipName = $this->option('name') ?: "deploy-theme-{$theme}.zip";
        $zipPath = base_path($zipName);

        $this->info("🤐 Comprimiendo el tema '{$theme}'...");
        $count = $zips->build($zipPath, $files);

        $size = number_format(filesize($zipPath) / 1048576, 2, ',', '.');
        $this->info("✅ ¡Éxito! {$zipName}: {$count} archivos, {$size} MB");

        $this->newLine();
        $this->line('Para publicarlo:');
        $this->line('  1. Subí el ZIP a la raíz del proyecto en el hosting y descomprimilo ahí (sobrescribiendo).');
        $this->line('  2. Si el hosting cachea vistas, vaciá storage/framework/views.');
        $this->line('  3. Los settings de la base de datos no viajan en el ZIP: cargalos desde /settings.');
        if (! $withBuild) {
            $this->line('  * Si agregaste clases Tailwind nuevas, regenerá con --with-build.');
        }

        return self::SUCCESS;
    }

    private function runBuild(): bool
    {
        $this->info('📦 Ejecutando npm run build...');

        $process = new Process(['npm', 'run', 'build'], base_path());
        $process->setTimeout(300);
        $process->run();

        if (! $process->isSuccessful()) {
            $this->error('❌ Error en npm run build.');

            return false;
        }

        return true;
    }
}
