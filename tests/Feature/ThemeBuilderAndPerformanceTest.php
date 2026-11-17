<?php

use App\Helpers\SettingsHelper;
use App\Models\Setting;
use App\Models\User;
use App\Services\ThemeBuilderCompilerService;
use App\Services\ThemePackageService;
use App\Services\ThemeService;
use App\Services\ThemeVariableInterpolator;
use App\View\Components\ProductCard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\File;

uses(RefreshDatabase::class);

it('persists active theme and variant in database settings without mutating .env', function (): void {
    $envPath = base_path('.env');
    $envBefore = File::exists($envPath) ? File::get($envPath) : null;

    /** @var ThemeService $themeService */
    $themeService = app(ThemeService::class);
    $themeService->activateTheme('encendidorqta', 'navidad');

    expect(Setting::where('key', 'active_theme')->value('value'))->toBe('encendidorqta')
        ->and(Setting::where('key', 'active_theme_variant')->value('value'))->toBe('navidad')
        ->and(config('app.theme'))->toBe('encendidorqta')
        ->and(config('app.theme_variant'))->toBe('navidad');

    $themeService->syncRuntimeConfig();

    expect($themeService->activeTheme())->toBe('encendidorqta')
        ->and($themeService->activeVariant())->toBe('navidad');

    if ($envBefore !== null) {
        expect(File::get($envPath))->toBe($envBefore);
    }
});

it('rejects zip slip traversal and dangerous extensions during theme import while allowing clean round-trips', function (): void {
    /** @var ThemePackageService $packageService */
    $packageService = app(ThemePackageService::class);

    $tmpDir = storage_path('framework/testing/theme-zips');
    File::ensureDirectoryExists($tmpDir);

    $slipZip = "{$tmpDir}/slip.zip";
    $zip = new ZipArchive;
    $zip->open($slipZip, ZipArchive::CREATE | ZipArchive::OVERWRITE);
    $zip->addFromString('css/testtheme.css', '@import "tailwindcss";');
    $zip->addFromString('../evil.txt', 'pwned');
    $zip->close();

    try {
        expect(fn (): string => $packageService->importFromZip($slipZip))
            ->toThrow(RuntimeException::class);

        $phpZip = "{$tmpDir}/public-php.zip";
        $zip = new ZipArchive;
        $zip->open($phpZip, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString('css/testtheme.css', '@import "tailwindcss";');
        $zip->addFromString('public/shell.php', '<?php phpinfo();');
        $zip->close();

        expect(fn (): string => $packageService->importFromZip($phpZip))
            ->toThrow(RuntimeException::class);

        $testTheme = 'studio_roundtrip_test';
        $viewsDir = resource_path("views/themes/{$testTheme}/pages");
        $publicDir = public_path("themes/{$testTheme}/images");
        $cssFile = resource_path("css/themes/{$testTheme}.css");
        $exportedZip = null;

        try {
            File::ensureDirectoryExists($viewsDir);
            File::ensureDirectoryExists($publicDir);
            File::put("{$viewsDir}/demo.blade.php", '<x-layouts.clean title="Demo"><p>Hola</p></x-layouts.clean>');
            File::put("{$publicDir}/pixel.png", 'fake-png-bytes');
            File::put($cssFile, "/* {$testTheme} */\n");

            $exportedZip = $packageService->exportToZip($testTheme);
            expect(File::exists($exportedZip))->toBeTrue();

            File::deleteDirectory(resource_path("views/themes/{$testTheme}"));
            File::deleteDirectory(public_path("themes/{$testTheme}"));
            File::delete($cssFile);

            $detected = $packageService->importFromZip($exportedZip);

            expect($detected)->toBe($testTheme)
                ->and(File::exists("{$viewsDir}/demo.blade.php"))->toBeTrue()
                ->and(File::exists("{$publicDir}/pixel.png"))->toBeTrue()
                ->and(File::exists($cssFile))->toBeTrue();
        } finally {
            File::deleteDirectory(resource_path("views/themes/{$testTheme}"));
            File::deleteDirectory(public_path("themes/{$testTheme}"));
            File::delete($cssFile);
            if ($exportedZip !== null && File::exists($exportedZip)) {
                File::delete($exportedZip);
            }
        }
    } finally {
        File::deleteDirectory($tmpDir);
    }
});

it('interpolates whitelisted theme variables and neutralizes template injection payloads', function (): void {
    /** @var ThemeVariableInterpolator $interpolator */
    $interpolator = app(ThemeVariableInterpolator::class);

    Setting::updateOrCreate(
        ['key' => 'company_name'],
        ['value' => 'OAGO Distribuidora', 'type' => 'string', 'text' => 'Nombre', 'description' => 'Empresa']
    );
    SettingsHelper::clearCache('company_name');

    $user = User::factory()->create([
        'name' => 'Lucia',
        'lastname' => 'Gomez',
    ]);
    $this->actingAs($user);

    $bladeOutput = $interpolator->compileToBlade(
        'Hola {{ user.full_name }} en {{ company.name }} @php(system("id")) <?php echo 1; ?> {{ exec("ls") }}'
    );

    expect($bladeOutput)
        ->toContain('current_user()?->fullName')
        ->toContain("SettingsHelper::settings('company_name'")
        ->not->toContain('@php')
        ->not->toContain('<?php')
        ->not->toContain('exec(');

    $richOutput = $interpolator->compileToBlade(
        '<p onclick="alert(1)">Bienvenido</p><script>alert(2)</script><img src="{{ theme.image(\'hero.webp\') }}">',
        false
    );

    expect($richOutput)
        ->toContain('<p>Bienvenido</p>')
        ->toContain("themes/' . config('app.theme', 'default') . '/images/")
        ->not->toContain('<script>')
        ->not->toContain('onclick');

    $previewText = $interpolator->interpolatePreview(
        'Catálogo de {{ company.name }} - {{ user.full_name }}',
        'encendidorqta'
    );

    expect($previewText)->toBe('Catálogo de OAGO Distribuidora - Gomez, Lucia');
});

it('compiles static pages AOT with native backups, renders routes, and compiles theme zones', function (): void {
    $theme = 'studio_aot_test';
    $viewsRoot = resource_path("views/themes/{$theme}");
    $slug = 'historia-aot';

    Setting::updateOrCreate(
        ['key' => 'company_name'],
        ['value' => 'Encendido Central', 'type' => 'string', 'text' => 'Nombre', 'description' => 'Empresa']
    );
    SettingsHelper::clearCache('company_name');

    /** @var ThemeService $themeService */
    $themeService = app(ThemeService::class);
    /** @var ThemeBuilderCompilerService $compiler */
    $compiler = app(ThemeBuilderCompilerService::class);

    try {
        File::ensureDirectoryExists("{$viewsRoot}/pages");
        $nativeBlade = '<x-layouts.clean title="Nativa"><div>CONTENIDO_NATIVO_ORIGINAL</div></x-layouts.clean>';
        File::put("{$viewsRoot}/pages/{$slug}.blade.php", $nativeBlade);

        $result = $compiler->compileAndSavePage($theme, '', $slug, 'Historia - {{ company.name }}', [
            [
                'id' => 'blk_hero_1',
                'type' => 'hero_banner',
                'enabled' => true,
                'props' => [
                    'badge' => 'Institucional',
                    'title' => 'Trayectoria de {{ company.name }}',
                    'subtitle' => 'Compromiso comercial @php(die())',
                ],
            ],
        ]);

        expect($result['backup_created'])->toBeTrue()
            ->and(File::exists("{$viewsRoot}/builder/backups/pages/{$slug}.bak.blade.php"))->toBeTrue()
            ->and(File::exists($result['json_path']))->toBeTrue()
            ->and(File::get($result['blade_path']))->toContain(ThemeBuilderCompilerService::COMPILED_MARKER)
            ->and(File::get($result['blade_path']))->not->toContain('@php(die())');

        $themeService->activateTheme($theme, '');

        $response = $this->get(route('theme.page', ['slug' => $slug]));
        $response->assertSuccessful()
            ->assertSee('Trayectoria de Encendido Central')
            ->assertSee('Institucional');

        $compiler->compileAndSaveZone($theme, '', 'topbar', [
            [
                'id' => 'blk_top_1',
                'type' => 'topbar_announcement',
                'enabled' => true,
                'props' => [
                    'badge' => 'AVISO',
                    'message' => 'Envíos activos desde {{ company.name }}',
                ],
            ],
        ]);

        $zoneHtml = Blade::render('<x-theme-zone name="topbar" />');
        expect($zoneHtml)->toContain('Envíos activos desde Encendido Central');

        $restored = $compiler->restoreNativeBackup($theme, '', $slug);
        expect($restored)->toBeTrue()
            ->and(File::get("{$viewsRoot}/pages/{$slug}.blade.php"))->toBe($nativeBlade)
            ->and(File::exists($result['json_path']))->toBeFalse();
    } finally {
        File::deleteDirectory($viewsRoot);
        $themeService->activateTheme('default', '');
    }
});

it('renders stateless x-product-card with resolved pricing, stock badge, and alpine addToCart without Livewire snapshots', function (): void {
    // Los usuarios reales siempre traen rol desde la base; el factory no lo asigna por defecto.
    $user = User::factory()->create(['role' => 'customer']);
    $this->actingAs($user);

    $inStockProduct = makeProduct([
        'description' => 'Bobina de Encendido Pro',
        'price' => 2500.50,
        'stock' => 120,
        'qtty_package' => 2,
        'deduct_stock' => true,
    ]);

    $data = ProductCard::resolveViewData($inStockProduct);
    expect($data['isOutOfStock'])->toBeFalse()
        ->and($data['stockBadge']['label'])->toBe('EN STOCK')
        ->and($data['cartPayload']['qtty_package'])->toBe(2);

    $html = Blade::render('<x-product-card :product="$product" />', ['product' => $inStockProduct]);

    expect($html)
        ->toContain('Bobina de Encendido Pro')
        ->toContain('EN STOCK')
        ->toContain('addToCart')
        ->not->toContain('wire:snapshot');

    $outOfStockProduct = makeProduct([
        'description' => 'Sensor Agotado',
        'price' => 1500,
        'stock' => 0,
        'deduct_stock' => true,
        'allow_reservation' => false,
    ]);

    $outData = ProductCard::resolveViewData($outOfStockProduct);
    expect($outData['isOutOfStock'])->toBeTrue()
        ->and($outData['stockBadge']['label'])->toBe('AGOTADO');
});
