<?php

namespace App\Console\Commands;

use App\Models\ListName;
use App\Models\ListPrice;
use Illuminate\Console\Command;

class NormalizePriceListsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:normalize-price-lists';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Normaliza las listas de precios moviendo los precios "$ U" a la columna unit_price de la lista base y eliminando las listas duplicadas.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Buscamos listas que terminan en "U"
        $unitLists = \App\Models\ListName::where('name', 'LIKE', '%U')->get();

        if ($unitLists->isEmpty()) {
            $this->info('No se encontraron listas de precios que terminen en "U".');
            return;
        }

        foreach ($unitLists as $unitList) {
            $name = trim($unitList->name);
            $baseName = preg_replace('/ U$/', '', $name);

            // Buscamos la lista base con coincidencia parcial para manejar espacios extras si los hay
            $baseList = \App\Models\ListName::where('name', 'LIKE', $baseName . '%')
                ->where('id', '!=', $unitList->id)
                ->first();

            if (!$baseList) {
                $this->warn("No se encontró una lista base para: {$unitList->name} (buscando algo similar a '{$baseName}')");
                continue;
            }

            $this->info("Normalizando: {$unitList->name} -> {$baseList->name}");

            // 1. Mover precios a la lista base
            $pricesToMove = ListPrice::where('list_id', $unitList->id)->get();
            $count = 0;
            foreach ($pricesToMove as $priceRecord) {
                $record = ListPrice::firstOrNew([
                    'product_id' => $priceRecord->product_id,
                    'list_id' => $baseList->id,
                ]);

                if (!$record->exists) {
                    $record->price = 0;
                }

                $record->unit_price = $priceRecord->price;
                $record->save();

                $priceRecord->delete();
                $count++;
            }

            // 2. Reasignar usuarios que pudieran estar vinculados a la lista "$ U"
            $userCount = $unitList->users()->count();
            if ($userCount > 0) {
                $unitList->users()->update(['list_id' => $baseList->id]);
            }

            $altUserCount = $unitList->altUsers()->count();
            if ($altUserCount > 0) {
                $unitList->altUsers()->update(['list_id' => $baseList->id]);
            }

            // IMPORTANTE: No eliminamos la lista obsoleta porque el legacy la usa como puntero
            // $unitList->delete();

            $this->info("  - Precios movidos: {$count}");
            if ($userCount > 0 || $altUserCount > 0) {
                $this->info('  - Usuarios reasignados: '.($userCount + $altUserCount));
            }
        }

        $this->info('Proceso de normalización completado con éxito.');
    }
}
