<?php

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
