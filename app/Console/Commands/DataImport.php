<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class DataImport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:import {file=source.sql}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import data with live migration of logistics from orders to shipping_details';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $fileName = $this->argument('file');
        $filePath = base_path($fileName);

        if (!File::exists($filePath)) {
            $this->error("✘ Archivo no encontrado: $filePath");
            return 1;
        }

        $this->newLine();
        $this->info("🚀 Iniciando migración de datos integral desde: $fileName");
        $this->newLine();

        if (!$this->confirm("⚠️  Esto borrará los datos actuales de las tablas destino. ¿Continuar?", true)) {
            $this->info("Operación cancelada.");
            return 0;
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        $targetTables = [
            'users',
            'products',
            'list_names',
            'list_prices',
            'orders',
            'order_items',
            'shipping_details',
            'alt_users',
            'alt_orders',
            'alt_order_items'
        ];

        // 1. Limpieza de tablas
        $this->warn("🧹 Limpiando tablas destino...");
        foreach ($targetTables as $table) {
            if (Schema::hasTable($table)) {
                DB::table($table)->truncate();
                $this->line("   ✔ Truncado: <info>$table</info>");
            }
        }
        $this->newLine();

        // 2. Importación
        $this->info("📥 Procesando archivo SQL con trasvase de logística...");
        
        $handle = fopen($filePath, "r");
        if ($handle) {
            $buffer = '';
            $insideInsert = false;
            $currentTable = '';
            $chunksProcessed = 0;
            $logisticsMigrated = 0;
            $errors = 0;

            while (($line = fgets($handle)) !== false) {
                $trimmedLine = trim($line);

                if (!$insideInsert) {
                    if (stripos($trimmedLine, 'INSERT INTO') === 0) {
                        if (preg_match('/INSERT INTO `?(\w+)`?/i', $trimmedLine, $matches)) {
                            $sourceTable = $matches[1];
                            $destinationTable = ($sourceTable === 'guest_users') ? 'alt_users' : $sourceTable;

                            if (in_array($destinationTable, $targetTables)) {
                                $insideInsert = true;
                                $currentTable = $destinationTable;
                                $buffer = $line;
                                
                                if ($sourceTable !== $destinationTable) {
                                    $buffer = preg_replace('/INSERT INTO `?'.$sourceTable.'`?/i', "INSERT INTO `$destinationTable`", $buffer);
                                }
                                
                                $this->output->write("\r   ⚡ Importando: <comment>$currentTable</comment>... ");
                            }
                        }
                    }
                } else {
                    $buffer .= $line;
                }

                if ($insideInsert && str_ends_with($trimmedLine, ';')) {
                    
                    if ($currentTable === 'orders') {
                        $migrationResult = $this->migrateOrderLogistics($buffer);
                        $buffer = $migrationResult['cleanSql'];
                        $logisticsMigrated += $migrationResult['count'];
                    }

                    try {
                        DB::unprepared($buffer);
                        $chunksProcessed++;
                        $this->output->write("<info>.</info>");
                    } catch (\Exception $e) {
                        $errors++;
                        $this->newLine();
                        $this->error("   ✘ Error en $currentTable: " . substr($e->getMessage(), 0, 150));
                    }
                    
                    $buffer = '';
                    $insideInsert = false;
                    $this->newLine();
                }
            }

            fclose($handle);
        } else {
            $this->error("✘ Error al abrir el archivo.");
            return 1;
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $this->newLine();
        $this->info("✨ Proceso finalizado.");
        $this->line("   📦 Bloques procesados: <info>$chunksProcessed</info>");
        $this->line("   🚚 Registros de logística migrados: <info>$logisticsMigrated</info>");
        if ($errors > 0) $this->line("   ⚠️  Errores encontrados: <error>$errors</error>");
        else $this->line("   ✅ Importación completada con éxito.");
        $this->newLine();

        return 0;
    }

    /**
     * Mueve los datos de logística de 'orders' a 'shipping_details' al vuelo
     */
    private function migrateOrderLogistics(string $sql): array
    {
        $logisticsCount = 0;
        
        if (preg_match('/INSERT INTO `?orders`? VALUES\s*(.*);/is', $sql, $matches)) {
            $valuesSection = $matches[1];
            preg_match_all('/\((.*?)\)(?:,|$)/s', $valuesSection, $rows);
            
            $newOrderRows = [];
            foreach ($rows[1] as $row) {
                $parts = str_getcsv($row, ',', "'");
                
                if (count($parts) >= 15) {
                    $orderId = $parts[0];
                    $addr = $parts[4];
                    $city = $parts[5];
                    $cName = $parts[6];
                    $phone = $parts[7];

                    // Solo migrar si hay algún dato útil
                    if ($addr !== 'NULL' || $city !== 'NULL' || $cName !== 'NULL' || $phone !== 'NULL') {
                        $this->saveToShippingDetails($orderId, $addr, $city, $cName, $phone);
                        $logisticsCount++;
                    }

                    // Eliminar columnas de logística para la tabla 'orders'
                    unset($parts[4], $parts[5], $parts[6], $parts[7]);
                    
                    $sanitizedParts = array_map(function($val) {
                        if ($val === 'NULL' || $val === null) return 'NULL';
                        return "'" . str_replace("'", "''", $val) . "'";
                    }, array_values($parts));
                    
                    $newOrderRows[] = "(" . implode(',', $sanitizedParts) . ")";
                } else {
                    $newOrderRows[] = "($row)";
                }
            }
            
            return [
                'cleanSql' => "INSERT INTO `orders` VALUES " . implode(',', $newOrderRows) . ";",
                'count' => $logisticsCount
            ];
        }
        
        return ['cleanSql' => $sql, 'count' => 0];
    }

    /**
     * Inserta directamente en la tabla de logística
     */
    private function saveToShippingDetails($orderId, $addr, $city, $cName, $phone)
    {
        $addr = ($addr === 'NULL') ? null : $addr;
        $city = ($city === 'NULL') ? null : $city;
        $cName = ($cName === 'NULL') ? null : $cName;
        $phone = ($phone === 'NULL') ? null : $phone;

        DB::table('shipping_details')->insert([
            'order_id' => $orderId,
            'address' => $addr,
            'city' => $city,
            'contact_name' => $cName,
            'phone' => $phone,
            'shipping_status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
