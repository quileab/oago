<?php

use App\Helpers\SettingsHelper;
use App\Services\ProductSearchService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Volt\Volt;

use function Pest\Laravel\mock;

uses(RefreshDatabase::class);

/**
 * La búsqueda devuelve un paginador solo si se le pide paginar; si no, una colección.
 * Con $alwaysCollection simula un motor que ignora el pedido de paginar.
 */
function fakeCatalogSearch(bool $alwaysCollection = false): void
{
    mock(ProductSearchService::class)
        ->shouldReceive('searchProducts')
        ->andReturnUsing(fn ($params, $items, $featured, $paginate) => ($paginate && ! $alwaysCollection)
            ? new LengthAwarePaginator([], 0, $items)
            : new Collection);
}

beforeEach(function () {
    expect(config('app.theme'))->toBe('encendidorqta');
});

it('renders the catalog for any display mode value without calling links() on a collection', function (string $mode, string $expected) {
    SettingsHelper::update_setting('catalog_display_mode', $mode);
    fakeCatalogSearch();

    Volt::test('webproductsmain', ['filter' => ['published' => true]])
        ->assertOk()
        ->assertSet('displayMode', $expected);
})->with([
    'paginado exacto' => ['paginated', 'paginated'],
    'mayúsculas y espacios' => [' Paginated ', 'paginated'],
    'typo paginate' => ['paginate', 'infinite_scroll'],
    'en castellano' => ['paginado', 'infinite_scroll'],
    'scroll infinito' => ['infinite_scroll', 'infinite_scroll'],
    'vacío' => ['', 'infinite_scroll'],
]);

it('does not break when the search ignores pagination even in paginated mode', function () {
    SettingsHelper::update_setting('catalog_display_mode', 'paginated');
    fakeCatalogSearch(alwaysCollection: true);

    Volt::test('webproductsmain', ['filter' => ['published' => true]])->assertOk();
});
