<?php

namespace App\Console\Commands;

use App\Services\ImageImportService;
use Illuminate\Console\Command;

class ImportProductImages extends Command
{
    protected $signature = 'app:import-product-images 
                            {--folder= : Carpeta de origen dentro de public/ (default: de preferencias)}
                            {--field= : Campo identificador de producto: sku, barcode, id, product_type}
                            {--separator= : Carácter separador de índice (_, -, o none)}
                            {--pad= : Longitud de relleno str_pad con ceros a la izquierda}
                            {--delete-source : Eliminar los archivos originales tras importarlos}
                            {--no-overwrite : No sobrescribir imágenes si ya existen en destino}
                            {--limit= : Limitar cantidad máxima de archivos a procesar}';

    protected $description = 'Importa y convierte imágenes a WebP en la estructura de productos de OAGO';

    public function handle(ImageImportService $service): int
    {
        $prefs = $service->getPreferences();

        $folder = $this->option('folder') !== null ? (string) $this->option('folder') : ($prefs['folder'] ?? 'imagenes_productos');
        $field = $this->option('field') !== null ? (string) $this->option('field') : ($prefs['field'] ?? 'sku');
        $separator = $this->option('separator') !== null ? (string) $this->option('separator') : ($prefs['separator'] ?? '_');
        $pad = $this->option('pad') !== null ? (int) $this->option('pad') : (int) ($prefs['pad_length'] ?? 0);
        $deleteSource = $this->option('delete-source') ? true : (bool) ($prefs['delete_source'] ?? false);
        $overwrite = $this->option('no-overwrite') ? false : (bool) ($prefs['overwrite'] ?? true);
        $limit = $this->option('limit') !== null ? (int) $this->option('limit') : (int) ($prefs['limit'] ?? 0);

        $this->info('🔍 Escaneando carpeta: '.public_path($folder));

        $scan = $service->scan($folder, [
            'field' => $field,
            'separator' => $separator,
            'pad_length' => $pad,
        ]);

        if ($scan['total_files'] === 0) {
            $this->warn('No se encontraron imágenes compatibles para procesar.');

            return self::SUCCESS;
        }

        $this->table(
            ['Total Archivos', 'Coincidencias BD', 'Sin Coincidencia'],
            [[$scan['total_files'], $scan['matched_count'], $scan['unmatched_count']]]
        );

        if ($this->input->isInteractive()) {
            if (! $this->confirm('¿Desea iniciar la importación ahora?', true)) {
                $this->info('Operación cancelada.');

                return self::SUCCESS;
            }
        }

        $this->newLine();
        $this->info('⚙️ Procesando e importando a WebP...');

        $bar = $this->output->createProgressBar($limit > 0 ? min($limit, $scan['total_files']) : $scan['total_files']);
        $bar->start();

        $result = $service->import([
            'folder' => $folder,
            'field' => $field,
            'separator' => $separator,
            'pad_length' => $pad,
            'delete_source' => $deleteSource,
            'overwrite' => $overwrite,
            'limit' => $limit,
        ], function () use ($bar) {
            $bar->advance();
        });

        $bar->finish();
        $this->newLine(2);

        $this->info('✅ Proceso completado:');
        $this->line("   - Procesadas: {$result['processed']}");
        $this->line("   - Importadas: {$result['imported']}");
        $this->line("   - Omitidas:   {$result['skipped']}");
        if ($result['errors'] > 0) {
            $this->error("   - Errores:    {$result['errors']}");
        }

        return self::SUCCESS;
    }
}
