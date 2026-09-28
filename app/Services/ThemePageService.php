<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ThemePageService
{
    /**
     * Get the active theme name.
     */
    protected function activeTheme(): string
    {
        return config('app.theme', 'default');
    }

    /**
     * Returns ordered list of view directories to search for pages.
     *
     * @return string[]
     */
    protected function searchPaths(): array
    {
        $theme = $this->activeTheme();
        $variant = config('app.theme_variant');

        $paths = [];

        if ($theme !== 'default') {
            $themePath = resource_path("views/themes/{$theme}/pages");

            if ($variant) {
                $paths[] = resource_path("views/themes/{$theme}/{$variant}/pages");
            }

            $paths[] = $themePath;
        }

        // Fallback: base pages directory
        $paths[] = resource_path('views/pages');

        return $paths;
    }

    /**
     * Detect all available static pages for the active theme.
     *
     * Returns an array of page definitions with slug, title, and URL.
     *
     * @return array<int, array{slug: string, title: string, url: string}>
     */
    public function getAvailablePages(): array
    {
        $pages = [];
        $seen = [];

        foreach ($this->searchPaths() as $path) {
            if (! File::isDirectory($path)) {
                continue;
            }

            foreach (File::files($path) as $file) {
                if ($file->getExtension() !== 'php') {
                    continue;
                }

                $slug = Str::before($file->getFilename(), '.blade.php');

                // Skip non-blade files and already-found slugs (theme takes priority over fallback)
                if (! Str::endsWith($file->getFilename(), '.blade.php') || isset($seen[$slug])) {
                    continue;
                }

                $seen[$slug] = true;
                $pages[] = [
                    'slug' => $slug,
                    'title' => Str::headline($slug),
                    'url' => route('theme.page', ['slug' => $slug]),
                ];
            }
        }

        return $pages;
    }

    /**
     * Resolve the Blade view name for a given slug, respecting theme hierarchy.
     * Returns null if the page does not exist in any search path.
     */
    public function resolvePageView(string $slug): ?string
    {
        $theme = $this->activeTheme();
        $variant = config('app.theme_variant');

        $candidates = [];

        if ($theme !== 'default') {
            if ($variant) {
                $candidates[] = "themes.{$theme}.{$variant}.pages.{$slug}";
            }
            $candidates[] = "themes.{$theme}.pages.{$slug}";
        }

        $candidates[] = "pages.{$slug}";

        foreach ($candidates as $viewName) {
            if (view()->exists($viewName)) {
                return $viewName;
            }
        }

        return null;
    }
}
