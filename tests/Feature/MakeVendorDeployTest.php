<?php

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;

beforeEach(function () {
    $this->zipName = 'zz-vendor-deploy-test.zip';
    $this->tempDirsBefore = glob(sys_get_temp_dir().'/vendor-deploy-*') ?: [];
});

afterEach(function () {
    File::delete(base_path($this->zipName));
    File::delete(base_path('deploy-vendor.zip'));
});

/**
 * Simula `composer install --no-dev`: arma un vendor/ a partir del composer.lock real.
 *
 * @param  array<int, string>  $extraInstalled  Nombres de paquetes extra (por ejemplo de desarrollo).
 * @param  array<string, string>  $versionOverrides  nombre => versión distinta a la del lock.
 */
function fakeComposerInstall(array $extraInstalled = [], array $versionOverrides = [], bool $fails = false): void
{
    Process::fake(function ($process) use ($extraInstalled, $versionOverrides, $fails) {
        if ($fails) {
            return Process::result(errorOutput: 'Your requirements could not be resolved', exitCode: 2);
        }

        $lock = json_decode(File::get(base_path('composer.lock')), true);
        $packages = collect($lock['packages'])
            ->map(fn (array $p) => ['name' => $p['name'], 'version' => $versionOverrides[$p['name']] ?? $p['version']])
            ->all();

        foreach ($extraInstalled as $name) {
            $packages[] = ['name' => $name, 'version' => '1.0.0'];
        }

        $vendor = $process->path.'/vendor';
        File::ensureDirectoryExists("{$vendor}/composer");
        File::ensureDirectoryExists("{$vendor}/laravel/framework/src");
        File::ensureDirectoryExists("{$vendor}/laravel/framework/.git");
        File::ensureDirectoryExists("{$vendor}/__MACOSX");
        File::ensureDirectoryExists("{$vendor}/livewire/livewire/dist");

        File::put("{$vendor}/composer/installed.json", json_encode(['packages' => $packages]));
        File::put("{$vendor}/autoload.php", '<?php');
        File::put("{$vendor}/laravel/framework/src/Application.php", '<?php');
        File::put("{$vendor}/laravel/framework/.git/HEAD", 'ref');
        File::put("{$vendor}/laravel/framework/.gitattributes", '* text=auto');
        File::put("{$vendor}/.DS_Store", 'junk');
        File::put("{$vendor}/__MACOSX/resource", 'junk');
        File::put("{$vendor}/livewire/livewire/dist/livewire.js", '/* livewire */');
        File::put("{$vendor}/livewire/livewire/dist/manifest.json", json_encode(['/livewire.js' => 'abc12345']));

        return Process::result(output: 'ok');
    });
}

function vendorZipEntries(string $path): array
{
    $zip = new ZipArchive;
    $zip->open($path);
    $entries = [];
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $entries[] = $zip->getNameIndex($i);
    }
    $zip->close();

    return $entries;
}

it('packages a production-only vendor folder with root-relative paths', function () {
    fakeComposerInstall();

    $this->artisan('make:vendor-deploy', ['--name' => $this->zipName])->assertSuccessful();

    $entries = vendorZipEntries(base_path($this->zipName));

    expect($entries)
        ->toContain('vendor/autoload.php')
        ->toContain('vendor/composer/installed.json')
        ->toContain('vendor/laravel/framework/src/Application.php')
        ->and(collect($entries)->every(fn (string $e) => str_starts_with($e, 'vendor/') || str_starts_with($e, 'public/vendor/livewire/')))->toBeTrue()
        ->and(implode("\n", $entries))
        ->not->toContain('.git')
        ->not->toContain('.DS_Store')
        ->not->toContain('__MACOSX');
});

it('ships public/vendor/livewire assets matching the installed Livewire version', function () {
    fakeComposerInstall();

    $this->artisan('make:vendor-deploy', ['--name' => $this->zipName])
        ->expectsOutputToContain('livewire.js?id=abc12345')
        ->assertSuccessful();

    $zip = new ZipArchive;
    $zip->open(base_path($this->zipName));
    $manifest = $zip->getFromName('public/vendor/livewire/manifest.json');
    $hasScript = $zip->locateName('public/vendor/livewire/livewire.js') !== false;
    $zip->close();

    expect($hasScript)->toBeTrue()
        ->and(json_decode($manifest, true))->toBe(['/livewire.js' => 'abc12345']);
});

it('installs without dev dependencies and without running scripts', function () {
    fakeComposerInstall();

    $this->artisan('make:vendor-deploy', ['--name' => $this->zipName])->assertSuccessful();

    Process::assertRan(function ($process) {
        $command = (array) $process->command;

        return in_array('install', $command, true)
            && in_array('--no-dev', $command, true)
            && in_array('--no-scripts', $command, true)
            && ! in_array('--ignore-platform-reqs', $command, true);
    });
});

it('passes --ignore-platform-reqs through to composer', function () {
    fakeComposerInstall();

    $this->artisan('make:vendor-deploy', ['--name' => $this->zipName, '--ignore-platform-reqs' => true])
        ->assertSuccessful();

    Process::assertRan(fn ($process) => in_array('--ignore-platform-reqs', (array) $process->command, true));
});

it('fails when a dev package ends up in the install', function () {
    $lock = json_decode(File::get(base_path('composer.lock')), true);
    $devOnly = collect($lock['packages-dev'])->pluck('name')
        ->diff(collect($lock['packages'])->pluck('name'))->first();

    fakeComposerInstall(extraInstalled: [$devOnly]);

    $this->artisan('make:vendor-deploy', ['--name' => $this->zipName])
        ->expectsOutputToContain('Lo instalado no coincide con composer.lock')
        ->assertFailed();

    expect(base_path($this->zipName))->not->toBeFile();
});

it('fails when an installed version differs from composer.lock', function () {
    $lock = json_decode(File::get(base_path('composer.lock')), true);
    $name = $lock['packages'][0]['name'];

    fakeComposerInstall(versionOverrides: [$name => 'v0.0.1']);

    $this->artisan('make:vendor-deploy', ['--name' => $this->zipName])
        ->expectsOutputToContain($name)
        ->assertFailed();

    expect(base_path($this->zipName))->not->toBeFile();
});

it('reports composer failures and creates no zip', function () {
    fakeComposerInstall(fails: true);

    $this->artisan('make:vendor-deploy', ['--name' => $this->zipName])
        ->expectsOutputToContain('composer install falló')
        ->assertFailed();

    expect(base_path($this->zipName))->not->toBeFile();
});

it('defaults to deploy-vendor.zip and leaves an existing vendor.zip alone', function () {
    fakeComposerInstall();

    $manualZip = base_path('vendor.zip');
    $existed = File::exists($manualZip);
    $mtime = $existed ? filemtime($manualZip) : null;

    $this->artisan('make:vendor-deploy')->assertSuccessful();

    expect(base_path('deploy-vendor.zip'))->toBeFile()
        ->and(File::exists($manualZip))->toBe($existed)
        ->and($existed ? filemtime($manualZip) : null)->toBe($mtime);
});

it('cleans its temporary work directory, success or failure', function () {
    fakeComposerInstall();
    $this->artisan('make:vendor-deploy', ['--name' => $this->zipName])->assertSuccessful();

    fakeComposerInstall(fails: true);
    $this->artisan('make:vendor-deploy', ['--name' => $this->zipName])->assertFailed();

    expect(glob(sys_get_temp_dir().'/vendor-deploy-*') ?: [])->toBe($this->tempDirsBefore);
});
