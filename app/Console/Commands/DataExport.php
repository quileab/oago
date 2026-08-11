<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\info;
use function Laravel\Prompts\progress;

class DataExport extends Command
{
    protected $signature = 'db:export 
                            {filename=export.sql : Nombre del archivo de salida SQL}
                            {--drop : Incluir DROP TABLE IF EXISTS}
                            {--if-not-exists : Usar CREATE TABLE IF NOT EXISTS}
                            {--nodata : Exportar solo la estructura de las tablas, sin datos}';

    protected $description = 'Exporta la base de datos a un archivo SQL con opciones configurables de estructura e inserciones';

    public function handle(): int
    {
        $filename = $this->argument('filename');
        if (! str_ends_with($filename, '.sql')) {
            $filename .= '.sql';
        }

        $dropTable = $this->option('drop');
        $ifNotExists = $this->option('if-not-exists');
        $noData = $this->option('nodata');

        if (! $dropTable && ! $this->hasOptionProvided('drop')) {
            $dropTable = confirm(
                label: '¿Deseas incluir "DROP TABLE IF EXISTS" antes de cada tabla?',
                default: true
            );
        }

        if (! $ifNotExists && ! $this->hasOptionProvided('if-not-exists')) {
            $ifNotExists = confirm(
                label: '¿Deseas incluir "IF NOT EXISTS" en las sentencias CREATE TABLE?',
                default: true
            );
        }

        $tables = DB::connection()->getSchemaBuilder()->getTableListing();

        if (empty($tables)) {
            $this->error('✘ No se encontraron tablas en la base de datos.');

            return 1;
        }

        $filePath = base_path($filename);
        $handle = fopen($filePath, 'w');

        if (! $handle) {
            $this->error("✘ No se pudo crear o escribir en el archivo: {$filePath}");

            return 1;
        }

        $driver = DB::getDriverName();
        $this->writeHeader($handle, $driver);

        info("🚀 Iniciando exportación de la base de datos hacia {$filename}...");

        $progress = progress(label: 'Exportando tablas...', steps: count($tables));
        $progress->start();

        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        foreach ($tables as $table) {
            $this->exportTableSchema($handle, $table, $dropTable, $ifNotExists, $driver);

            if (! $noData) {
                $this->exportTableData($handle, $table);
            }

            $progress->advance();
        }

        $progress->finish();

        fwrite($handle, "\nSET FOREIGN_KEY_CHECKS=1;\n");
        fclose($handle);

        $this->newLine();
        $this->info("✔ Exportación completada exitosamente: {$filePath}");

        return 0;
    }

    protected function hasOptionProvided(string $name): bool
    {
        return $this->input->hasParameterOption('--'.$name) || $this->input->hasParameterOption('-'.$this->getDefinition()->getOption($name)->getShortcut());
    }

    protected function writeHeader($handle, string $driver): void
    {
        $date = date('Y-m-d H:i:s');
        $header = "-- ========================================================\n";
        $header .= "-- Database Export\n";
        $header .= "-- Date: {$date}\n";
        $header .= "-- Driver: {$driver}\n";
        $header .= "-- ========================================================\n\n";
        $header .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

        fwrite($handle, $header);
    }

    protected function exportTableSchema($handle, string $table, bool $dropTable, bool $ifNotExists, string $driver): void
    {
        fwrite($handle, "-- --------------------------------------------------------\n");
        fwrite($handle, "-- Table structure for `{$table}`\n");
        fwrite($handle, "-- --------------------------------------------------------\n");

        if ($dropTable) {
            fwrite($handle, "DROP TABLE IF EXISTS `{$table}`;\n");
        }

        if ($driver === 'mysql' || $driver === 'mariadb') {
            $row = DB::selectOne("SHOW CREATE TABLE `{$table}`");
            if ($row) {
                $createSql = ((array) $row)['Create Table'] ?? array_values((array) $row)[1] ?? null;
                if ($createSql) {
                    if ($ifNotExists && ! str_contains($createSql, 'CREATE TABLE IF NOT EXISTS')) {
                        $createSql = preg_replace('/CREATE TABLE/i', 'CREATE TABLE IF NOT EXISTS', $createSql, 1);
                    }
                    fwrite($handle, $createSql.";\n\n");

                    return;
                }
            }
        }

        if ($ifNotExists) {
            fwrite($handle, "CREATE TABLE IF NOT EXISTS `{$table}` (\n");
        } else {
            fwrite($handle, "CREATE TABLE `{$table}` (\n");
        }
        fwrite($handle, ");\n\n");
    }

    protected function exportTableData($handle, string $table): void
    {
        $count = DB::table($table)->count();
        if ($count === 0) {
            return;
        }

        fwrite($handle, "-- Dumping data for table `{$table}`\n");

        DB::table($table)->orderBy(DB::raw('1'))->chunk(500, function ($rows) use ($handle, $table) {
            foreach ($rows as $row) {
                $arrayRow = (array) $row;
                $columns = array_keys($arrayRow);
                $escapedColumns = array_map(fn ($col) => "`{$col}`", $columns);

                $values = array_map(function ($value) {
                    if ($value === null) {
                        return 'NULL';
                    }
                    if (is_bool($value)) {
                        return $value ? '1' : '0';
                    }
                    if (is_numeric($value)) {
                        return $value;
                    }

                    $escaped = addslashes((string) $value);
                    $escaped = str_replace("\n", '\n', $escaped);
                    $escaped = str_replace("\r", '\r', $escaped);

                    return "'{$escaped}'";
                }, array_values($arrayRow));

                $sql = sprintf(
                    "INSERT INTO `%s` (%s) VALUES (%s);\n",
                    $table,
                    implode(', ', $escapedColumns),
                    implode(', ', $values)
                );

                fwrite($handle, $sql);
            }
        });

        fwrite($handle, "\n");
    }
}
