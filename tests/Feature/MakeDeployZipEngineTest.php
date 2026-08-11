<?php

use Illuminate\Support\Facades\File;

afterEach(function () {
    if (File::exists(base_path('test_engine.zip'))) {
        File::delete(base_path('test_engine.zip'));
    }
    if (File::exists(resource_path('views/themes/client_test/test.blade.php'))) {
        File::deleteDirectory(resource_path('views/themes/client_test'));
    }
});

test('make:deploy-zip --engine-only excludes resources/views/themes folder', function () {
    // Crear un archivo ficticio dentro de themes
    $themeFile = resource_path('views/themes/client_test/test.blade.php');
    File::makeDirectory(dirname($themeFile), 0755, true, true);
    File::put($themeFile, '<div>Theme View</div>');

    $this->artisan('make:deploy-zip', [
        '--name' => 'test_engine.zip',
        '--engine-only' => true,
        '--no_brand' => true,
    ])->assertExitCode(0);

    expect(File::exists(base_path('test_engine.zip')))->toBeTrue();

    // Inspeccionar contenido del ZIP
    $zip = new ZipArchive;
    expect($zip->open(base_path('test_engine.zip')))->toBeTrue();

    $hasThemeFiles = false;
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $filename = $zip->getNameIndex($i);
        if (str_contains($filename, 'resources/views/themes/client_test')) {
            $hasThemeFiles = true;
            break;
        }
    }

    $zip->close();

    expect($hasThemeFiles)->toBeFalse();
});
