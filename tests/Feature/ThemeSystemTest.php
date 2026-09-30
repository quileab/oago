<?php

use App\Providers\VoltServiceProvider;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\View;

test('theme system loads base theme and seasonal variant in cascade order', function () {
    $basePath = resource_path('views/themes/empresa_test/components');
    $variantPath = resource_path('views/themes/empresa_test/navidad/components');

    File::makeDirectory($basePath, 0755, true, true);
    File::makeDirectory($variantPath, 0755, true, true);

    File::put($basePath.'/test-brand.blade.php', '<div>Base Logo</div>');
    File::put($basePath.'/test-footer.blade.php', '<div>Base Footer</div>');

    File::put($variantPath.'/test-brand.blade.php', '<div>Navidad Logo</div>');

    config([
        'app.theme' => 'empresa_test',
        'app.theme_variant' => 'navidad',
    ]);

    // Simular boot del AppServiceProvider
    $theme = config('app.theme');
    $variant = config('app.theme_variant');

    $pathsToRegister = [];
    if ($theme !== 'default') {
        $baseThemePath = resource_path("views/themes/{$theme}");
        $pathsToRegister[] = $baseThemePath;

        if ($variant) {
            $pathsToRegister[] = "{$baseThemePath}/{$variant}";
        }
    }

    foreach ($pathsToRegister as $path) {
        if (file_exists($path)) {
            View::prependLocation($path);
        }
    }

    // El logo debe ser sobreescrito por la variante 'navidad'
    expect(view('components.test-brand')->render())->toContain('Navidad Logo');

    // El footer debe hacer fallback al tema base de empresa_test
    expect(view('components.test-footer')->render())->toContain('Base Footer');

    // Cleanup
    File::deleteDirectory(resource_path('views/themes/empresa_test'));
});

test('theme single-file volt components take priority over base in the livewire finder', function () {
    $originalTheme = config('app.theme');
    $originalVariant = config('app.theme_variant');

    $themeLivewirePath = resource_path('views/themes/empresa_test/livewire');

    File::makeDirectory($themeLivewirePath, 0755, true, true);
    File::put(
        $themeLivewirePath.'/about.blade.php',
        '<?php use Livewire\Volt\Component; new class extends Component { public array $timeline = []; }; ?> <div>Tema About</div>'
    );

    config(['app.theme' => 'empresa_test', 'app.theme_variant' => null]);

    (new VoltServiceProvider(app()))->boot();

    // The Finder must resolve the theme file, not the base views/livewire one,
    // otherwise the component class comes from base while the template comes
    // from the theme (mixed rendering).
    $resolved = app('livewire.finder')->resolveSingleFileComponentPath('about');

    expect($resolved)->toContain('empresa_test')->toEndWith('about.blade.php');

    // Cleanup: restore real theme configuration and provider registrations
    File::deleteDirectory(resource_path('views/themes/empresa_test'));
    config(['app.theme' => $originalTheme, 'app.theme_variant' => $originalVariant]);
    (new VoltServiceProvider(app()))->boot();
});
