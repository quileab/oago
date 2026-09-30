<?php

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Livewire\Volt\Volt;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

function makeThemeZip(string $theme, string $path): void
{
    $zip = new ZipArchive;
    $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
    $zip->addFromString("css/{$theme}.css", '@import "tailwindcss";');
    $zip->addFromString('views/livewire/sample.blade.php', '<div>sample</div>');
    $zip->addFromString('public/images/logo.png', 'fake-png-bytes');
    $zip->close();
}

function cleanupTheme(string $theme): void
{
    foreach ([
        resource_path("views/themes/{$theme}"),
        public_path("themes/{$theme}"),
    ] as $dir) {
        if (File::isDirectory($dir)) {
            File::deleteDirectory($dir);
        }
    }

    $css = resource_path("css/themes/{$theme}.css");

    if (File::exists($css)) {
        File::delete($css);
    }
}

it('imports a theme from a valid zip', function () {
    $admin = User::factory()->create(['role' => Role::ADMIN]);
    actingAs($admin, 'web');

    $theme = 'testimporttheme';
    cleanupTheme($theme);

    $zipPath = sys_get_temp_dir().'/theme-import-test.zip';
    makeThemeZip($theme, $zipPath);

    try {
        Volt::test('themes.index')
            ->set('importFile', UploadedFile::fake()->createWithContent('theme.zip', file_get_contents($zipPath)))
            ->call('importTheme')
            ->assertHasNoErrors();

        expect(File::exists(resource_path("css/themes/{$theme}.css")))->toBeTrue()
            ->and(File::exists(resource_path("views/themes/{$theme}/livewire/sample.blade.php")))->toBeTrue()
            ->and(File::exists(public_path("themes/{$theme}/images/logo.png")))->toBeTrue();
    } finally {
        cleanupTheme($theme);
        @unlink($zipPath);
    }
});

it('rejects non-zip files on import', function () {
    $admin = User::factory()->create(['role' => Role::ADMIN]);
    actingAs($admin, 'web');

    Volt::test('themes.index')
        ->set('importFile', UploadedFile::fake()->create('theme.txt', 1, 'text/plain'))
        ->call('importTheme')
        ->assertHasErrors('importFile');
});
