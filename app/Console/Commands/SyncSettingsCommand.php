<?php

namespace App\Console\Commands;

use App\Models\Setting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class SyncSettingsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:sync-settings {--overwrite : Sobrescribe los valores existentes con los valores por defecto}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sincroniza los parámetros faltantes en la tabla settings según la configuración del sistema.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $defaultSettings = config('default_settings', []);

        if (empty($defaultSettings)) {
            $this->error('No se encontraron parámetros en config/default_settings.php.');

            return 1;
        }

        $overwrite = (bool) $this->option('overwrite');
        $this->info($overwrite ? '🔄 Sincronizando configuraciones (modo sobrescritura)...' : '🔍 Verificando parámetros faltantes en settings...');

        $tableData = [];
        $added = 0;
        $skipped = 0;
        $updated = 0;

        foreach ($defaultSettings as $key => $attributes) {
            $existing = Setting::where('key', $key)->first();

            if (! $existing) {
                Setting::create([
                    'key' => $key,
                    'value' => $attributes['value'] ?? null,
                    'type' => $attributes['type'] ?? 'string',
                    'text' => $attributes['text'] ?? $key,
                    'description' => $attributes['description'] ?? null,
                ]);

                Cache::forget('settings.'.$key);
                $added++;
                $tableData[] = ['<info>Agregado</info>', $key, $attributes['type'] ?? 'string', $this->formatDisplayValue($attributes['value'] ?? null)];
            } elseif ($overwrite) {
                $existing->update([
                    'value' => $attributes['value'] ?? null,
                    'type' => $attributes['type'] ?? $existing->type,
                    'text' => $attributes['text'] ?? $existing->text,
                    'description' => $attributes['description'] ?? $existing->description,
                ]);

                Cache::forget('settings.'.$key);
                $updated++;
                $tableData[] = ['<comment>Actualizado</comment>', $key, $attributes['type'] ?? 'string', $this->formatDisplayValue($attributes['value'] ?? null)];
            } else {
                // Ensure text/description/type are updated if they were missing or null in older dumps without changing value
                $updates = [];
                if (empty($existing->type) && ! empty($attributes['type'])) {
                    $updates['type'] = $attributes['type'];
                }
                if (empty($existing->text) && ! empty($attributes['text'])) {
                    $updates['text'] = $attributes['text'];
                }
                if (empty($existing->description) && ! empty($attributes['description'])) {
                    $updates['description'] = $attributes['description'];
                }

                if (! empty($updates)) {
                    $existing->update($updates);
                }

                $skipped++;
                $tableData[] = ['<fg=gray>Existente</>', $key, $existing->type ?? 'string', $this->formatDisplayValue($existing->value)];
            }
        }

        $this->newLine();
        $this->table(['Estado', 'Clave', 'Tipo', 'Valor Actual'], $tableData);
        $this->newLine();

        $this->info("✨ Proceso completado: <info>{$added} agregados</info>, <comment>{$updated} actualizados</comment>, <fg=gray>{$skipped} existentes conservados</>.");

        return 0;
    }

    /**
     * Format value for concise console table output.
     */
    private function formatDisplayValue(mixed $value): string
    {
        if ($value === null) {
            return '<null>';
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        $str = (string) $value;
        if (strlen($str) > 40) {
            return substr($str, 0, 37).'...';
        }

        return $str;
    }
}
