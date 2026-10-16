<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\Tag;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Pre-flight safety net for the removal of the legacy `products.tags` column.
 *
 * Compares the legacy pipe-delimited column against the `taggables` pivot and
 * reports every discrepancy, so no tag can be silently lost when the column is
 * dropped. Defaults to a read-only audit; pass --apply to write.
 */
class ResyncProductTagsCommand extends Command
{
    protected $signature = 'app:resync-product-tags
                            {--apply : Write the missing pivot rows from the legacy column}';

    protected $description = 'Audit and repair product tags before/after removing the legacy products.tags column';

    public function handle(): int
    {
        if (! Schema::hasColumn('products', 'tags')) {
            $this->info('La columna legacy products.tags ya no existe. Nada que sincronizar.');
            $this->line('  Productos con tags en el pivote: '.Product::query()->whereHas('tags')->count());
            $this->line('  Tags registrados: '.Tag::count());

            return self::SUCCESS;
        }

        $this->info('Auditando tags legacy vs. pivote taggables...');

        [$orphans, $atRisk] = $this->findMismatches();

        $this->line('  Productos totales: '.Product::query()->count());
        $this->line('  Productos con tags en el pivote: '.Product::query()->whereHas('tags')->count());
        $this->line('  Productos con columna legacy vacía: '.Product::query()
            ->where(fn ($q) => $q->whereNull('tags')->orWhere('tags', ''))->count());

        $duplicates = $this->duplicatePivotGroups();
        if ($duplicates->isNotEmpty()) {
            $extras = $duplicates->sum(fn ($g) => $g->c - 1);
            $this->warn("  Filas duplicadas en taggables: {$extras} (se purgan al agregar el índice único)");
        }

        if ($orphans->isNotEmpty()) {
            $this->info(sprintf(
                '  ℹ %d producto(s) tienen tags solo en el pivote (legacy vacío): dato seguro, se conserva el pivote.',
                $orphans->count()
            ));
        }

        if ($atRisk->isEmpty()) {
            $this->info('✔ Ningún producto tiene tags en la columna legacy que falten en el pivote.');
            $this->info('✔ Es seguro eliminar la columna legacy.');

            return self::SUCCESS;
        }

        $this->error("✘ {$atRisk->count()} producto(s) con tags en la columna legacy que NO coinciden con el pivote:");

        $this->table(
            ['id', 'descripción', 'legacy', 'pivote'],
            $atRisk->take(50)->map(fn (array $row) => [
                $row['id'],
                mb_substr((string) $row['description'], 0, 40),
                implode('|', $row['legacy']) ?: '(vacío)',
                implode('|', $row['pivot']) ?: '(vacío)',
            ])->all()
        );

        if ($atRisk->count() > 50) {
            $this->line('  … y '.($atRisk->count() - 50).' más.');
        }

        if (! $this->option('apply')) {
            $this->comment('Auditoría solamente. Ejecutá con --apply para copiar la columna legacy al pivote.');

            return self::FAILURE;
        }

        return $this->apply($atRisk);
    }

    /**
     * @return array{0: Collection, 1: Collection}
     */
    protected function findMismatches(): array
    {
        $pivotNames = $this->allPivotNames();

        $rows = Product::query()
            ->select('id', 'description', 'tags')
            ->orderBy('id')
            ->get()
            ->map(function (Product $product) use ($pivotNames) {
                return [
                    'id' => $product->id,
                    'description' => $product->description,
                    'legacy' => $this->parseLegacy($product->tags),
                    'pivot' => $pivotNames[$product->id] ?? [],
                ];
            });

        $orphans = [];
        $atRisk = [];

        foreach ($rows as $row) {
            if ($this->normalize($row['legacy']) === $this->normalize($row['pivot'])) {
                continue;
            }

            // Legacy vacío + pivote poblado: el pivote manda, no hay pérdida.
            if ($row['legacy'] === []) {
                $orphans[] = $row;

                continue;
            }

            $atRisk[] = $row;
        }

        return [collect($orphans), collect($atRisk)];
    }

    /**
     * All product tag names keyed by product id, in a single query.
     *
     * @return array<int, array<int, string>>
     */
    protected function allPivotNames(): array
    {
        $morpType = (new Product)->getMorphClass();

        return Tag::query()
            ->join('taggables', 'taggables.tag_id', '=', 'tags.id')
            ->where('taggables.taggable_type', $morpType)
            ->orderBy('tags.sort_order')
            ->get(['tags.name', 'taggables.taggable_id'])
            ->groupBy('taggable_id')
            ->map(fn (Collection $rows) => $rows->pluck('name')->all())
            ->all();
    }

    /**
     * @return Collection<int, object>
     */
    protected function duplicatePivotGroups(): Collection
    {
        return DB::table('taggables')
            ->selectRaw('tag_id, taggable_id, taggable_type, count(*) as c')
            ->groupBy('tag_id', 'taggable_id', 'taggable_type')
            ->havingRaw('count(*) > 1')
            ->get();
    }

    /**
     * @param  Collection<int, array{id: int, legacy: array}>  $mismatches
     */
    protected function apply(Collection $mismatches): int
    {
        $repaired = 0;

        foreach ($mismatches as $row) {
            $product = Product::find($row['id']);
            if (! $product) {
                continue;
            }

            $product->syncTagsById(
                collect($row['legacy'])->map(fn (string $name) => Tag::fromName($name)->id)->all()
            );

            $repaired++;
        }

        Tag::clearCache();
        $this->info("✔ {$repaired} producto(s) reparados desde la columna legacy.");

        return self::SUCCESS;
    }

    /**
     * @return array<int, string>
     */
    protected function parseLegacy(?string $tags): array
    {
        if ($tags === null || trim($tags) === '') {
            return [];
        }

        return array_values(array_unique(array_filter(array_map('trim', explode('|', $tags)))));
    }

    /**
     * @param  array<int, string>  $names
     * @return array<int, string>
     */
    protected function normalize(array $names): array
    {
        $names = array_map('strtoupper', $names);
        $names = array_values(array_unique($names));
        sort($names);

        return $names;
    }
}
