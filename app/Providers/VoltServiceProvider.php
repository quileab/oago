<?php

namespace App\Providers;

use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;
use Livewire\Volt\Volt;

class VoltServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        Volt::mount($this->mountedPaths());

        $this->registerThemeComponentOverrides();
    }

    /**
     * Volt component directories, in override priority order:
     * variant theme → theme → base → pages.
     *
     * @return array<int, string>
     */
    protected function mountedPaths(): array
    {
        return array_merge(
            array_reverse($this->themeComponentDirectories()),
            [
                config('livewire.view_path', resource_path('views/livewire')),
                resource_path('views/pages'),
            ],
        );
    }

    /**
     * Theme Volt component directories, in ascending priority:
     * base theme first, variant last so it wins on the last write.
     *
     * @return array<int, string>
     */
    protected function themeComponentDirectories(): array
    {
        $directories = [];

        $theme = config('app.theme', 'default');
        $variant = config('app.theme_variant');

        if ($theme !== 'default') {
            $baseThemePath = resource_path("views/themes/{$theme}");

            if (is_dir("{$baseThemePath}/livewire")) {
                $directories[] = "{$baseThemePath}/livewire";
            }

            if ($variant && is_dir("{$baseThemePath}/{$variant}/livewire")) {
                $directories[] = "{$baseThemePath}/{$variant}/livewire";
            }
        }

        return $directories;
    }

    /**
     * Register every theme single-file component with Livewire's Finder so the
     * theme class wins over the base view scan. Without this, Livewire resolves
     * the component class from views/livewire (base) while the template is
     * rendered from the theme (volt-livewire namespace), mixing both.
     */
    protected function registerThemeComponentOverrides(): void
    {
        $finder = app('livewire.finder');

        foreach ($this->themeComponentDirectories() as $directory) {
            foreach (File::allFiles($directory) as $file) {
                if (! str_ends_with($file->getFilename(), '.blade.php')) {
                    continue;
                }

                $relativeName = str_replace(['/', '\\'], '.', $file->getRelativePathname());

                $finder->addComponent(
                    name: preg_replace('/\.blade\.php$/', '', $relativeName),
                    viewPath: $file->getPathname(),
                );
            }
        }
    }
}
