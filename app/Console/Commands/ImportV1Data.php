<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class ImportV1Data extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migrate:v1-data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import data from oagos-v1.sql (Version 1) into the new database structure';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $filePath = base_path('oagos-v1.sql');

        if (!File::exists($filePath)) {
            $this->error("File not found: $filePath");
            return 1;
        }

        if (!$this->confirm("This will wipe existing data in the target tables and import data from oagos-v1.sql. Do you want to continue?", true)) {
            $this->info("Operation cancelled.");
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
            'guest_users', // Map this to alt_users
        ];

        $this->info("Truncating tables...");
        foreach ($targetTables as $table) {
            $truncateTable = ($table === 'guest_users') ? 'alt_users' : $table;
            
            if (\Illuminate\Support\Facades\Schema::hasTable($truncateTable)) {
                DB::table($truncateTable)->truncate();
                $this->line("Truncated: $truncateTable");
            }
        }

        $this->info("Importing data...");
        
        $handle = fopen($filePath, "r");
        if ($handle) {
            $buffer = '';
            $insideInsert = false;
            $currentTable = '';

            while (($line = fgets($handle)) !== false) {
                $trimmedLine = trim($line);

                // Start of a new statement
                if (!$insideInsert) {
                    if (str_starts_with($trimmedLine, 'INSERT INTO')) {
                        // Extract table name
                        if (preg_match('/INSERT INTO `?(\w+)`?/', $trimmedLine, $matches)) {
                            $table = $matches[1];
                            if (in_array($table, $targetTables)) {
                                $insideInsert = true;
                                $currentTable = $table;
                                $buffer = $line;
                            }
                        }
                    }
                } else {
                    // Continuing an INSERT statement
                    $buffer .= $line;
                }

                // Check for end of statement (;)
                // Only if we are inside a relevant INSERT and the line ends with ;
                // Note: This is a simple heuristic. It assumes the statement ends with ; followed by newline.
                if ($insideInsert && str_ends_with($trimmedLine, ';')) {
                    // Map guest_users to alt_users
                    if ($currentTable === 'guest_users') {
                        $buffer = str_replace('INSERT INTO `guest_users`', 'INSERT INTO `alt_users`', $buffer);
                        $buffer = str_replace('INSERT INTO guest_users', 'INSERT INTO alt_users', $buffer);
                    }

                    try {
                        DB::unprepared($buffer);
                        $this->line("Imported data for: " . ($currentTable === 'guest_users' ? 'alt_users' : $currentTable));
                    } catch (\Exception $e) {
                        $this->error("Error importing $currentTable: " . $e->getMessage());
                    }
                    
                    // Reset
                    $buffer = '';
                    $insideInsert = false;
                    $currentTable = '';
                }
            }

            fclose($handle);
        } else {
            $this->error("Error opening file.");
            return 1;
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $this->info("Migration completed successfully.");
        return 0;
    }
}
