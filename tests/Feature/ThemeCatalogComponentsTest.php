<?php

use App\Services\ThemeBuilderCompilerService;
use App\Services\ThemeCatalogComponentRegistry;
use App\View\Components\ProductCard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\File;

uses(RefreshDatabase::class);

it('compiles a catalog component AOT, backs up the native override and restores it', function (): void {
    $theme = 'catalog_aot_test';
    $viewsRoot = resource_path("views/themes/{$theme}");

    /** @var ThemeBuilderCompilerService $compiler */
    $compiler = app(ThemeBuilderCompilerService::class);

    try {
        File::ensureDirectoryExists("{$viewsRoot}/livewire");
        $native = '<div>NATIVA_ORIGINAL_CARD</div>';
        File::put("{$viewsRoot}/livewire/web-product-card.blade.php", $native);

        $result = $compiler->compileAndSaveCatalogComponent($theme, '', 'product_card', [
            ['id' => 'cat_card_style', 'type' => 'card_style', 'enabled' => true, 'props' => ['layout' => 'compact']],
            ['id' => 'cat_card_title', 'type' => 'card_title', 'enabled' => true, 'props' => ['font_size' => 'sm']],
            ['id' => 'inventado', 'type' => 'bloque_inventado', 'enabled' => true, 'props' => []],
        ]);

        $backupPath = "{$viewsRoot}/builder/backups/components/product_card.bak.blade.php";
        $compiled = File::get($result['blade_path']);

        expect($result['backup_created'])->toBeTrue()
            ->and(File::get($backupPath))->toBe($native)
            ->and($compiled)->toContain(ThemeBuilderCompilerService::COMPILED_MARKER)
            ->and($compiled)->toContain('x-theme-catalog.product-card')
            ->and($compiled)->toContain("'layout' => 'compact'")
            ->and($compiled)->not->toContain('bloque_inventado')
            ->and($compiler->isCatalogComponentCompiled($theme, '', 'product_card'))->toBeTrue()
            ->and(File::exists($result['json_path']))->toBeTrue();

        $blueprint = json_decode(File::get($result['json_path']), true);
        $product = makeProduct([
            'description' => 'Bobina de Encendido Compilada',
            'price' => 1500,
        ]);

        $html = Blade::render(
            '<x-theme-catalog.product-card :blocks="$blocks" :product="$product" :display-price="1500" :cart-payload="$payload" />',
            [
                'blocks' => $compiler->normalizeCatalogBlocks('product_card', $blueprint['blocks']),
                'product' => $product,
                'payload' => ProductCard::resolveViewData($product)['cartPayload'],
            ]
        );

        expect($html)->toContain('Bobina de Encendido Compilada');

        expect($compiler->restoreCatalogComponentBackup($theme, '', 'product_card'))->toBeTrue()
            ->and(File::get("{$viewsRoot}/livewire/web-product-card.blade.php"))->toBe($native)
            ->and(File::exists($backupPath))->toBeFalse()
            ->and(File::exists($result['json_path']))->toBeFalse()
            ->and($compiler->isCatalogComponentCompiled($theme, '', 'product_card'))->toBeFalse();
    } finally {
        File::deleteDirectory($viewsRoot);
    }
});

it('refuses to compile catalog components over the core default theme', function (): void {
    /** @var ThemeBuilderCompilerService $compiler */
    $compiler = app(ThemeBuilderCompilerService::class);

    $coreCard = resource_path('views/livewire/web-product-card.blade.php');
    $before = File::get($coreCard);

    expect(fn () => $compiler->compileAndSaveCatalogComponent('default', '', 'product_card', []))
        ->toThrow(InvalidArgumentException::class);

    expect(File::get($coreCard))->toBe($before)
        ->and($compiler->isCatalogComponentCompiled('default', '', 'product_card'))->toBeFalse()
        ->and($compiler->restoreCatalogComponentBackup('default', '', 'product_card'))->toBeFalse()
        ->and(File::exists(resource_path('views/builder/components/product_card.json')))->toBeFalse();
});

it('normalizes catalog blocks by dropping unknown types, de-duplicating and pinning style and related blocks', function (): void {
    /** @var ThemeBuilderCompilerService $compiler */
    $compiler = app(ThemeBuilderCompilerService::class);
    /** @var ThemeCatalogComponentRegistry $registry */
    $registry = app(ThemeCatalogComponentRegistry::class);

    $normalized = $compiler->normalizeCatalogBlocks('product_detail', [
        ['id' => 'desc', 'type' => 'detail_description', 'enabled' => true, 'props' => ['show_tags_checklist' => false]],
        ['id' => 'style_a', 'type' => 'detail_style', 'enabled' => false, 'props' => ['accent_color' => '#111111']],
        ['id' => 'style_b', 'type' => 'detail_style', 'enabled' => true, 'props' => ['accent_color' => '#222222']],
        ['id' => 'inventado', 'type' => 'bloque_inventado', 'enabled' => true, 'props' => []],
        ['id' => 'rel', 'type' => 'detail_related', 'enabled' => false, 'props' => []],
    ]);

    $byType = collect($normalized)->keyBy('type');
    $types = array_column($normalized, 'type');

    expect($types[0])->toBe('detail_style')
        ->and(end($types))->toBe('detail_related')
        ->and($types)->not->toContain('bloque_inventado')
        ->and(count($normalized))->toBe(count($registry->definitions('product_detail')))
        ->and($byType['detail_style']['props']['accent_color'])->toBe('#111111')
        ->and($byType['detail_style']['enabled'])->toBeTrue()
        ->and($byType['detail_related']['enabled'])->toBeFalse()
        ->and($byType['detail_description']['props']['show_tags_checklist'])->toBeFalse()
        ->and($byType['detail_description']['props']['show_html_description'])->toBeTrue();
});
