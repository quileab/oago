<?php

use App\Services\ThemePackageService;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    $this->theme = 'zz-deploy-test';
    $this->zipName = 'zz-deploy-test.zip';

    $viewsDir = resource_path("views/themes/{$this->theme}");
    $publicDir = public_path("themes/{$this->theme}");

    File::ensureDirectoryExists("{$viewsDir}/pages");
    File::ensureDirectoryExists("{$viewsDir}/correcciones");
    File::ensureDirectoryExists("{$publicDir}/images");

    File::put("{$viewsDir}/index.blade.php", '<div>index</div>');
    File::put("{$viewsDir}/pages/locales.blade.php", '<div>locales</div>');
    File::put("{$viewsDir}/.DS_Store", 'junk');
    File::put("{$viewsDir}/notas.docx", 'junk');
    File::put("{$viewsDir}/correcciones/pedido.txt", 'cliente');
    File::put("{$publicDir}/images/logo.png", 'png');
    File::put("{$publicDir}/images/.DS_Store", 'junk');
    File::put(resource_path("css/themes/{$this->theme}.css"), '@import "tailwindcss";');
});

afterEach(function () {
    File::deleteDirectory(resource_path("views/themes/{$this->theme}"));
    File::deleteDirectory(public_path("themes/{$this->theme}"));
    File::delete(resource_path("css/themes/{$this->theme}.css"));
    File::delete(base_path($this->zipName));
});

function zipEntries(string $path): array
{
    $zip = new ZipArchive;
    $zip->open($path);
    $entries = [];
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $entries[] = $zip->getNameIndex($i);
    }
    $zip->close();
    sort($entries);

    return $entries;
}

it('packages a theme with root-relative forward-slash paths', function () {
    $this->artisan('make:theme-deploy', ['theme' => $this->theme, '--name' => $this->zipName])
        ->assertSuccessful();

    $entries = zipEntries(base_path($this->zipName));

    expect($entries)->toBe([
        "public/themes/{$this->theme}/images/logo.png",
        "resources/css/themes/{$this->theme}.css",
        "resources/views/themes/{$this->theme}/correcciones/pedido.txt",
        "resources/views/themes/{$this->theme}/index.blade.php",
        "resources/views/themes/{$this->theme}/pages/locales.blade.php",
    ])->and(implode('', $entries))->not->toContain('\\');
});

it('leaves out hidden files, office documents and extra excludes', function () {
    $this->artisan('make:theme-deploy', [
        'theme' => $this->theme,
        '--name' => $this->zipName,
        '--exclude' => ['correcciones'],
    ])->assertSuccessful();

    $entries = implode("\n", zipEntries(base_path($this->zipName)));

    expect($entries)
        ->not->toContain('.DS_Store')
        ->not->toContain('.docx')
        ->not->toContain('correcciones');
});

it('lets the user pick the theme from a list', function () {
    $this->artisan('make:theme-deploy', ['--name' => $this->zipName])
        ->expectsChoice('¿Qué tema querés empaquetar?', $this->theme, app(ThemePackageService::class)->themes())
        ->assertSuccessful();

    expect(base_path($this->zipName))->toBeFile();
});

it('fails with a clear message when the theme does not exist', function () {
    $this->artisan('make:theme-deploy', ['theme' => 'no-existe', '--name' => $this->zipName])
        ->expectsOutputToContain("El tema 'no-existe' no existe")
        ->assertFailed();

    expect(base_path($this->zipName))->not->toBeFile();
});

it('requires the theme argument when not interactive', function () {
    $this->artisan('make:theme-deploy', ['--no-interaction' => true])
        ->expectsOutputToContain('Indicá el tema como argumento')
        ->assertFailed();
});

it('stamps entries with the current time so Blade recompiles the views', function () {
    $this->artisan('make:theme-deploy', ['theme' => $this->theme, '--name' => $this->zipName])
        ->assertSuccessful();

    $zip = new ZipArchive;
    $zip->open(base_path($this->zipName));
    $stat = $zip->statName("resources/views/themes/{$this->theme}/index.blade.php");
    $zip->close();

    expect($stat['mtime'])->toBeGreaterThan(time() - 60);
});
