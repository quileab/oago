<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use ZipArchive;

use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\select;

#[Signature('theme:export')]
#[Description('Export a theme as a .zip file ready to be extracted in production')]
class ThemeExport extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $viewsPath = resource_path('views/themes');

        if (! File::isDirectory($viewsPath)) {
            error('No themes directory found at resources/views/themes');

            return Command::FAILURE;
        }

        $directories = File::directories($viewsPath);
        $themes = array_map(function ($dir) {
            return basename($dir);
        }, $directories);

        if (empty($themes)) {
            error('No themes found to export.');

            return Command::FAILURE;
        }

        $selectedTheme = select(
            label: 'Which theme do you want to export?',
            options: $themes
        );

        $exportDir = base_path('theme-exports');
        File::ensureDirectoryExists($exportDir);

        $zipName = "{$selectedTheme}-theme-".now()->format('Ymd_His').'.zip';
        $zipPath = "{$exportDir}/{$zipName}";

        $zip = new ZipArchive;
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            error('Could not create ZIP file.');

            return Command::FAILURE;
        }

        $filesAdded = 0;

        // 1. Theme Views -> resources/views/themes/{$theme}
        $themeViewsPath = resource_path("views/themes/{$selectedTheme}");
        if (File::isDirectory($themeViewsPath)) {
            foreach (File::allFiles($themeViewsPath) as $file) {
                $relativePath = "resources/views/themes/{$selectedTheme}/".$file->getRelativePathname();
                $zip->addFile($file->getRealPath(), $relativePath);
                $filesAdded++;
            }
        }

        // 2. Theme CSS -> resources/css/themes/{$theme}.css
        $cssFile = resource_path("css/themes/{$selectedTheme}.css");
        if (File::exists($cssFile)) {
            $zip->addFile($cssFile, "resources/css/themes/{$selectedTheme}.css");
            $filesAdded++;
        }

        // 3. Public Assets -> public/themes/{$theme}
        $assetsPath = public_path("themes/{$selectedTheme}");
        if (File::isDirectory($assetsPath)) {
            foreach (File::allFiles($assetsPath) as $file) {
                $relativePath = "public/themes/{$selectedTheme}/".$file->getRelativePathname();
                $zip->addFile($file->getRealPath(), $relativePath);
                $filesAdded++;
            }
        }

        $zip->close();

        if ($filesAdded === 0) {
            unlink($zipPath);
            error('No files were found for the selected theme.');

            return Command::FAILURE;
        }

        info("Theme '{$selectedTheme}' successfully exported!");
        info("Saved at: {$zipPath}");
        info('You can now upload this .zip to production and extract it in the root of the project.');

        return Command::SUCCESS;
    }
}
