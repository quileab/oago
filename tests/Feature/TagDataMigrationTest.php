<?php

use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

/**
 * Guards the removal of the legacy `products.tags` column.
 *
 * The audit command reports two kinds of divergence between the legacy column
 * and the `taggables` pivot:
 *   - orphan:  legacy empty, pivot populated -> safe, the pivot wins
 *   - at risk: legacy populated, pivot differs -> real data loss unless fixed
 */

/**
 * Re-create the legacy column so the audit code path can be exercised.
 * The column is already gone once the migration ran, so the test adds it back.
 */
function withLegacyTagsColumn(callable $callback)
{
    if (! Schema::hasColumn('products', 'tags')) {
        Schema::table('products', function ($table) {
            $table->string('tags', 50)->default('');
        });
    }

    try {
        return $callback();
    } finally {
        Schema::table('products', function ($table) {
            $table->dropColumn('tags');
        });
    }
}

it('passes the audit when the pivot fully covers the legacy column', function () {
    withLegacyTagsColumn(function () {
        $product = attachTags(makeProduct(), ['NUEVO', 'OFERTA']);
        DB::table('products')->where('id', $product->id)->update(['tags' => 'NUEVO|OFERTA']);

        $this->artisan('app:resync-product-tags')
            ->expectsOutputToContain('Es seguro eliminar la columna legacy')
            ->assertSuccessful();
    });
});

it('reports tags that live only in the pivot as safe orphans, not as data loss', function () {
    withLegacyTagsColumn(function () {
        attachTags(makeProduct(), ['CANASTAS NAVIDEÑAS']);

        $this->artisan('app:resync-product-tags')
            ->expectsOutputToContain('solo en el pivote')
            ->assertSuccessful();
    });
});

it('fails the audit when the legacy column has tags missing from the pivot', function () {
    withLegacyTagsColumn(function () {
        $product = makeProduct();

        // Simulate legacy data that never reached the pivot.
        DB::table('products')->where('id', $product->id)->update(['tags' => 'NUEVO|OFERTA']);

        $this->artisan('app:resync-product-tags')
            ->expectsOutputToContain('NO coinciden con el pivote')
            ->assertExitCode(1);

        // With --apply the pivot is rebuilt from the legacy column.
        $this->artisan('app:resync-product-tags --apply')->assertSuccessful();

        expect($product->fresh()->tags_array)->toHaveCount(2);
    });
});

it('succeeds as a no-op audit once the legacy column is gone', function () {
    expect(Schema::hasColumn('products', 'tags'))->toBeFalse();

    $this->artisan('app:resync-product-tags')
        ->expectsOutputToContain('ya no existe')
        ->assertSuccessful();
});

it('keeps tag names unique regardless of casing', function () {
    Tag::fromName('CANASTAS NAVIDEÑAS');
    Tag::fromName('canastas navidenas');
    Tag::fromName('CANASTAS NAVIDEÑAS');

    expect(Tag::where('slug', 'canastas-navidenas')->count())->toBe(1);
});

it('no longer exposes a legacy tags column on the products table', function () {
    expect(Schema::hasColumn('products', 'tags'))->toBeFalse();
});
