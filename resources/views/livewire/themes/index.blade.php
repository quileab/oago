<?php

use App\Services\ThemeBuilderCompilerService;
use App\Services\ThemeCatalogComponentRegistry;
use App\Services\ThemePackageService;
use App\Services\ThemeService;
use App\Services\ThemeWidgetRegistry;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Livewire\Attributes\Validate;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Mary\Traits\Toast;

new class extends Component
{
    use Toast;
    use WithFileUploads;

    public string $selectedTheme = '';

    public string $selectedVariant = '';

    public array $themes = [];

    public array $variants = [];

    public string $search = '';

    public string $selectedCategory = 'all';

    public string $statusFilter = 'all';

    public string $newThemeName = '';

    public string $newVariantName = '';

    public string $newPageSlug = '';

    /** @var TemporaryUploadedFile|null */
    #[Validate('required|file|mimes:zip|max:51200')]
    public $importFile = null;

    /** @var array<int, string>|null */
    private ?array $scannedBaseViews = null;

    /** @var array<string, bool> */
    private array $studioMarkerCache = [];

    public function mount()
    {
        $themeService = app(ThemeService::class);
        $this->selectedTheme = $themeService->activeTheme();
        $this->selectedVariant = $themeService->activeVariant();
        $this->loadThemes();
        $this->loadVariants();
    }

    public function updatedSelectedTheme()
    {
        $this->selectedVariant = '';
        $this->loadVariants();
    }

    public function loadThemes()
    {
        $themesPath = resource_path('views/themes');
        if (! File::exists($themesPath)) {
            File::makeDirectory($themesPath, 0755, true);
        }

        $directories = File::directories($themesPath);
        $this->themes = array_map(fn ($dir) => basename($dir), $directories);

        if (! in_array('default', $this->themes)) {
            array_unshift($this->themes, 'default');
        }
    }

    public function loadVariants()
    {
        $this->variants = [];
        if (empty($this->selectedTheme)) {
            return;
        }

        $themePath = resource_path("views/themes/{$this->selectedTheme}");
        if (! File::exists($themePath)) {
            return;
        }

        $directories = File::directories($themePath);
        $exclude = ['livewire', 'components', 'layouts', 'vendor', 'emails', 'auth', 'errors', 'pages', 'partials', 'builder'];

        foreach ($directories as $dir) {
            $name = basename($dir);
            if (! in_array($name, $exclude)) {
                $this->variants[] = $name;
            }
        }
    }

    public function createTheme()
    {
        $name = trim($this->newThemeName);
        if (empty($name)) {
            $this->error('El nombre del tema no puede estar vacío.');

            return;
        }

        $themePath = resource_path("views/themes/{$name}");
        if (File::exists($themePath)) {
            $this->warning('El tema ya existe.');

            return;
        }

        File::makeDirectory($themePath, 0755, true);

        // --- INICIO: Crear archivo CSS para el tema automáticamente ---
        $cssDir = resource_path('css/themes');
        if (! File::exists($cssDir)) {
            File::makeDirectory($cssDir, 0755, true);
        }

        $cssFile = resource_path("css/themes/{$name}.css");
        if (! File::exists($cssFile)) {
            $cssTemplate = <<<CSS
@import "tailwindcss";

@plugin "daisyui" {
    /* backend follows the device color scheme; frontend stays light via [data-theme="light"] below */
    themes: light --default, dark --prefersdark;
}
@plugin "daisyui/theme" {
    default: true;
}

:root,
[data-theme="light"] {
    --color-primary: #002b6b;
    --color-secondary: #8a053c;
    --color-success: #3c8e26;
}
[data-theme="light"] {
    color-scheme: light;
}
@media (prefers-color-scheme: dark) {
    :root:not([data-theme="light"]) {
        color-scheme: dark;
    }
}

@source "../../../vendor/robsontenorio/mary/src/View/Components/**/*.php";
@source "../../../vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php";
@source "../../../storage/framework/views/*.php";
@source "../../**/*.blade.php";
@source "../../**/*.js";

@custom-variant dark (&:where(.dark, .dark *));

/* Agrega aquí los estilos personalizados para el tema {$name} */
CSS;
            File::put($cssFile, $cssTemplate);
        }
        // --- FIN CSS ---

        // --- INICIO: Crear carpeta pública de assets del tema ---
        $publicAssetsPath = public_path("themes/{$name}/images");
        if (! File::exists($publicAssetsPath)) {
            File::makeDirectory($publicAssetsPath, 0755, true);
        }
        // --- FIN ---

        $this->success("Tema '{$name}' creado: vistas, CSS y carpeta de assets lista.");
        $this->newThemeName = '';
        $this->loadThemes();
        $this->selectedTheme = $name;
        $this->updatedSelectedTheme();
    }

    public function createVariant()
    {
        $name = trim($this->newVariantName);
        if (empty($name) || empty($this->selectedTheme)) {
            $this->error('El nombre de la variante o tema es inválido.');

            return;
        }

        $exclude = ['livewire', 'components', 'layouts', 'vendor', 'emails', 'auth', 'errors', 'pages', 'partials', 'builder'];
        if (in_array(strtolower($name), $exclude)) {
            $this->error('Ese nombre está reservado para directorios de vistas.');

            return;
        }

        $variantPath = resource_path("views/themes/{$this->selectedTheme}/{$name}");
        if (File::exists($variantPath)) {
            $this->warning('La variante ya existe.');

            return;
        }

        File::makeDirectory($variantPath, 0755, true);
        $this->success("Variante '{$name}' creada correctamente.");
        $this->newVariantName = '';
        $this->loadVariants();
        $this->selectedVariant = $name;
    }

    public function activateTheme(): void
    {
        if (empty($this->selectedTheme)) {
            $this->error('Selecciona un tema primero.');

            return;
        }

        try {
            app(ThemeService::class)->activateTheme($this->selectedTheme, $this->selectedVariant);
            $this->success('Tema activado exitosamente en la base de datos.');
        } catch (InvalidArgumentException $e) {
            $this->error($e->getMessage());
        }
    }

    private function getCurrentOverridePrefix()
    {
        return $this->selectedVariant
            ? "{$this->selectedTheme}/{$this->selectedVariant}"
            : $this->selectedTheme;
    }

    public function overrideView($file)
    {
        if (empty($this->selectedTheme)) {
            $this->error('Selecciona un tema primero.');

            return;
        }

        if ($this->selectedTheme === 'default') {
            $this->error('No se pueden sobrescribir archivos en el tema default. Usa otro tema o modifica los archivos base (Core).');

            return;
        }

        $basePath = resource_path("views/{$file}");
        $prefix = $this->getCurrentOverridePrefix();
        $themePath = resource_path("views/themes/{$prefix}/{$file}");

        if (! File::exists($basePath)) {
            $this->error('El archivo base no existe.');

            return;
        }

        if (File::exists($themePath)) {
            $this->warning('El archivo ya está sobrescrito en este nivel.');

            return;
        }

        $directory = dirname($themePath);
        if (! File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        File::copy($basePath, $themePath);
        $this->success("Vista sobrescrita en '{$prefix}'.");
    }

    public function revertView($file)
    {
        if (empty($this->selectedTheme)) {
            $this->error('Selecciona un tema primero.');

            return;
        }

        $prefix = $this->getCurrentOverridePrefix();
        $themePath = resource_path("views/themes/{$prefix}/{$file}");

        if (! File::exists($themePath)) {
            $this->warning('El archivo no está sobrescrito.');

            return;
        }

        File::delete($themePath);

        // Clean up empty directories
        $directory = dirname($themePath);
        $themeRoot = resource_path("views/themes/{$prefix}");

        while ($directory !== $themeRoot && File::isDirectory($directory) && empty(File::allFiles($directory)) && empty(File::directories($directory))) {
            File::deleteDirectory($directory);
            $directory = dirname($directory);
        }

        $this->success('Vista revertida. Ahora usa el fallback.');
    }

    private function isIgnoredThemeView(string $relativePath): bool
    {
        return str_starts_with($relativePath, 'themes/')
            || str_starts_with($relativePath, 'builder/')
            || str_starts_with($relativePath, 'livewire/themes/')
            || str_starts_with($relativePath, 'components/theme-catalog/')
            || $relativePath === 'components/layouts/studio.blade.php';
    }

    private function categorizeView(string $path): array
    {
        if (str_starts_with($path, 'errors/')) {
            return ['id' => 'errors', 'label' => 'Pág. HTTP', 'icon' => 'o-shield-exclamation', 'bg' => 'bg-base-200/30 hover:bg-base-200/60'];
        }
        if (str_starts_with($path, 'emails/')) {
            return ['id' => 'emails', 'label' => 'Email', 'icon' => 'o-envelope', 'bg' => 'bg-info/5 hover:bg-info/10'];
        }
        if (str_starts_with($path, 'components/')) {
            return ['id' => 'components', 'label' => 'Layout / UI', 'icon' => 'o-rectangle-group', 'bg' => 'bg-secondary/5 hover:bg-secondary/10'];
        }
        if (preg_match('#^livewire/(products|users|tags|settings|admin|achievements)/#', $path)) {
            return ['id' => 'admin', 'label' => 'Admin', 'icon' => 'o-cog-6-tooth', 'bg' => 'bg-warning/5 hover:bg-warning/10'];
        }
        if (str_starts_with($path, 'livewire/')) {
            return ['id' => 'store', 'label' => 'Tienda', 'icon' => 'o-shopping-bag', 'bg' => 'bg-primary/5 hover:bg-primary/10'];
        }

        return ['id' => 'root', 'label' => 'Base / Página', 'icon' => 'o-document-text', 'bg' => 'bg-base-200/30 hover:bg-base-200/60'];
    }

    /**
     * @return array<int, string>
     */
    private function getScannedBaseViews(): array
    {
        if ($this->scannedBaseViews !== null) {
            return $this->scannedBaseViews;
        }

        $views = [];
        foreach (File::allFiles(resource_path('views')) as $file) {
            $relativePath = str_replace('\\', '/', $file->getRelativePathname());
            if ($this->isIgnoredThemeView($relativePath)) {
                continue;
            }

            $views[] = $relativePath;
        }

        return $this->scannedBaseViews = $views;
    }

    public function getStatusCountsProperty(): array
    {
        if (empty($this->selectedTheme)) {
            return ['all' => 0, 'overridden' => 0, 'fallback' => 0];
        }

        $prefix = $this->getCurrentOverridePrefix();
        $counts = ['all' => 0, 'overridden' => 0, 'fallback' => 0];

        foreach ($this->getScannedBaseViews() as $relativePath) {
            $counts['all']++;
            $themePath = resource_path("views/themes/{$prefix}/{$relativePath}");
            if (File::exists($themePath)) {
                $counts['overridden']++;
            } else {
                $counts['fallback']++;
            }
        }

        return $counts;
    }

    public function getCategoriesCountProperty(): array
    {
        if (empty($this->selectedTheme)) {
            return [];
        }

        $counts = [
            'all' => 0,
            'store' => 0,
            'admin' => 0,
            'components' => 0,
            'emails' => 0,
            'errors' => 0,
            'root' => 0,
        ];

        foreach ($this->getScannedBaseViews() as $relativePath) {
            $counts['all']++;
            $category = $this->categorizeView($relativePath);
            if (isset($counts[$category['id']])) {
                $counts[$category['id']]++;
            }
        }

        return $counts;
    }

    public function getViewsProperty()
    {
        if (empty($this->selectedTheme)) {
            return [];
        }

        $viewsList = [];
        $prefix = $this->getCurrentOverridePrefix();

        foreach ($this->getScannedBaseViews() as $relativePath) {
            $category = $this->categorizeView($relativePath);

            if ($this->selectedCategory !== 'all' && $category['id'] !== $this->selectedCategory) {
                continue;
            }

            if ($this->search && stripos($relativePath, $this->search) === false) {
                continue;
            }

            $themeRelativePath = "themes/{$prefix}/{$relativePath}";
            $themePath = resource_path("views/{$themeRelativePath}");
            $isOverridden = File::exists($themePath);

            if ($this->statusFilter === 'overridden' && ! $isOverridden) {
                continue;
            }
            if ($this->statusFilter === 'fallback' && $isOverridden) {
                continue;
            }

            $directory = dirname($relativePath);
            $fileName = basename($relativePath);

            $viewsList[] = [
                'file' => $relativePath,
                'directory' => $directory === '.' ? '' : $directory . '/',
                'filename' => $fileName,
                'theme_path' => $themeRelativePath,
                'category' => $category,
                'overridden' => $isOverridden,
                'source' => $isOverridden ? ($this->isStudioGeneratedOverride($themePath) ? 'studio' : 'manual') : 'core',
                'studio_url' => $isOverridden ? $this->studioUrlFor($relativePath) : null,
            ];
        }

        usort(
            $viewsList,
            fn (array $a, array $b): int => ($b['overridden'] <=> $a['overridden']) ?: strcmp($a['file'], $b['file'])
        );

        return $viewsList;
    }

    /**
     * Determine whether a theme override file was generated by Studio (block compiler)
     * instead of being a hand-written Blade file.
     */
    private function isStudioGeneratedOverride(string $themePath): bool
    {
        if (! array_key_exists($themePath, $this->studioMarkerCache)) {
            $this->studioMarkerCache[$themePath] = File::exists($themePath)
                && str_contains((string) File::get($themePath), ThemeBuilderCompilerService::COMPILED_MARKER);
        }

        return $this->studioMarkerCache[$themePath];
    }

    /**
     * Builder URL for overrides that Studio can actually edit (static pages and theme zones).
     */
    private function studioUrlFor(string $relativePath): ?string
    {
        $compiler = app(ThemeBuilderCompilerService::class);

        if (preg_match('#^pages/([A-Za-z0-9_-]+)\.blade\.php$#', $relativePath, $matches) === 1) {
            $slug = $matches[1];

            if ($compiler->isPageManagedByBuilder($this->selectedTheme, $this->selectedVariant, $slug)) {
                return route('themes.builder', [
                    'theme' => $this->selectedTheme,
                    'variant' => $this->selectedVariant,
                    'target' => 'page',
                    'slug' => $slug,
                ]);
            }

            return null;
        }

        if (preg_match('#^partials/zones/([A-Za-z0-9_-]+)\.blade\.php$#', $relativePath, $matches) === 1) {
            $zone = $matches[1];
            $zones = app(ThemeWidgetRegistry::class)->zones();

            if (isset($zones[$zone])) {
                return route('themes.builder', [
                    'theme' => $this->selectedTheme,
                    'variant' => $this->selectedVariant,
                    'target' => 'zone',
                    'zone' => $zone,
                ]);
            }
        }

        $catalogMap = [];

        foreach (app(ThemeCatalogComponentRegistry::class)->components() as $componentKey => $meta) {
            $catalogMap[$meta['target_view']] = $componentKey;
        }

        if (isset($catalogMap[$relativePath])) {
            $componentKey = $catalogMap[$relativePath];

            if ($compiler->isCatalogComponentCompiled($this->selectedTheme, $this->selectedVariant, $componentKey)) {
                return route('themes.builder', [
                    'theme' => $this->selectedTheme,
                    'variant' => $this->selectedVariant,
                    'target' => 'catalog',
                    'component' => $componentKey,
                ]);
            }
        }

        return null;
    }

    public function getThemePagesProperty()
    {
        if (empty($this->selectedTheme)) {
            return [];
        }

        $prefix = $this->getCurrentOverridePrefix();
        $pagesPath = resource_path("views/themes/{$prefix}/pages");

        if (! File::isDirectory($pagesPath)) {
            return [];
        }

        $compiler = app(ThemeBuilderCompilerService::class);
        $pages = [];

        foreach (File::files($pagesPath) as $file) {
            if (Str::endsWith($file->getFilename(), '.blade.php')) {
                $slug = Str::before($file->getFilename(), '.blade.php');
                $pages[] = [
                    'slug' => $slug,
                    'file' => $file->getRelativePathname(),
                    'title' => Str::headline($slug),
                    'url' => route('theme.page', ['slug' => $slug]),
                    'is_managed' => $compiler->isPageManagedByBuilder($this->selectedTheme, $this->selectedVariant, $slug),
                    'builder_url' => route('themes.builder', [
                        'theme' => $this->selectedTheme,
                        'variant' => $this->selectedVariant,
                        'target' => 'page',
                        'slug' => $slug,
                    ]),
                ];
            }
        }

        return $pages;
    }

    public function getThemeZonesProperty(): array
    {
        if (empty($this->selectedTheme)) {
            return [];
        }

        $registry = app(ThemeWidgetRegistry::class);
        $compiler = app(ThemeBuilderCompilerService::class);
        $zones = [];

        foreach ($registry->zones() as $zoneKey => $zoneMeta) {
            $zones[] = [
                'key' => $zoneKey,
                'label' => $zoneMeta['label'],
                'description' => $zoneMeta['description'],
                'badge' => $zoneMeta['badge'],
                'is_compiled' => $compiler->isZoneCompiled($this->selectedTheme, $this->selectedVariant, $zoneKey),
                'has_active' => $compiler->zoneHasActiveBlocks($this->selectedTheme, $this->selectedVariant, $zoneKey),
                'builder_url' => route('themes.builder', [
                    'theme' => $this->selectedTheme,
                    'variant' => $this->selectedVariant,
                    'target' => 'zone',
                    'zone' => $zoneKey,
                ]),
            ];
        }

        return $zones;
    }

    public function getThemeCatalogComponentsProperty(): array
    {
        if (empty($this->selectedTheme)) {
            return [];
        }

        $registry = app(ThemeCatalogComponentRegistry::class);
        $compiler = app(ThemeBuilderCompilerService::class);
        $components = [];

        foreach ($registry->components() as $componentKey => $meta) {
            $blueprint = $compiler->getCatalogComponentBlueprint($this->selectedTheme, $this->selectedVariant, $componentKey);

            $components[] = [
                'key' => $componentKey,
                'label' => $meta['label'],
                'short_label' => $meta['short_label'],
                'description' => $meta['description'],
                'target_view' => $meta['target_view'],
                'is_compiled' => $compiler->isCatalogComponentCompiled($this->selectedTheme, $this->selectedVariant, $componentKey),
                'has_native_file' => (bool) ($blueprint['has_native_file'] ?? false),
                'has_backup' => (bool) ($blueprint['has_backup'] ?? false),
                'builder_url' => route('themes.builder', [
                    'theme' => $this->selectedTheme,
                    'variant' => $this->selectedVariant,
                    'target' => 'catalog',
                    'component' => $componentKey,
                ]),
            ];
        }

        return $components;
    }

    public function createThemePage()
    {
        if (empty($this->selectedTheme)) {
            $this->error('Selecciona un tema primero.');

            return;
        }

        $name = trim($this->newPageSlug);
        if (empty($name)) {
            $this->error('El nombre de la página no puede estar vacío.');

            return;
        }

        $slug = Str::slug($name);
        $prefix = $this->getCurrentOverridePrefix();
        $pagesPath = resource_path("views/themes/{$prefix}/pages");

        if (! File::isDirectory($pagesPath)) {
            File::makeDirectory($pagesPath, 0755, true);
        }

        $filePath = "{$pagesPath}/{$slug}.blade.php";
        if (File::exists($filePath)) {
            $this->warning("La página '{$slug}' ya existe.");

            return;
        }

        $title = Str::headline($slug);
        $exportedTitle = var_export($title, true);
        $phpClose = '?' . '>';

        $template = <<<BLADE
        <?php

        use Livewire\Attributes\Layout;
        use Livewire\Attributes\Title;
        use Livewire\Volt\Component;

        new #[Layout('components.layouts.clean')] #[Title({$exportedTitle})] class extends Component {
        }; {$phpClose}

        <div class="bg-white min-h-screen dark:bg-gray-950 px-4 py-8 text-gray-900 dark:text-gray-100">
            <div class="mx-auto max-w-4xl space-y-6">
                <h1 class="text-3xl font-bold">{$title}</h1>
                <p>Contenido de la página va aquí...</p>
            </div>
        </div>

        BLADE;

        File::put($filePath, $template);
        $this->success("Página '{$slug}' creada correctamente.");
        $this->newPageSlug = '';
    }

    public function deleteThemePage($slug)
    {
        if (empty($this->selectedTheme)) {
            $this->error('Selecciona un tema primero.');

            return;
        }

        $prefix = $this->getCurrentOverridePrefix();
        $filePath = resource_path("views/themes/{$prefix}/pages/{$slug}.blade.php");
        $jsonPath = resource_path("views/themes/{$prefix}/builder/pages/{$slug}.json");

        if (File::exists($filePath)) {
            File::delete($filePath);
            if (File::exists($jsonPath)) {
                File::delete($jsonPath);
            }
            $this->success("Página '{$slug}' eliminada.");
        } else {
            $this->error("La página '{$slug}' no existe.");
        }
    }

    public function exportTheme(): mixed
    {
        try {
            $zipPath = app(ThemePackageService::class)->exportToZip($this->selectedTheme);

            return response()->download($zipPath, "{$this->selectedTheme}-theme.zip")->deleteFileAfterSend(true);
        } catch (RuntimeException $e) {
            $this->error($e->getMessage());

            return null;
        }
    }

    public function importTheme(): void
    {
        $this->validate(
            ['importFile' => 'required|file|mimes:zip|max:51200'],
            [
                'importFile.required' => 'Selecciona un archivo ZIP para importar.',
                'importFile.file' => 'El archivo no se pudo subir. Verifica que no supere el límite de PHP (upload_max_filesize).',
                'importFile.mimes' => 'El archivo debe ser un ZIP exportado desde este gestor.',
                'importFile.max' => 'El ZIP supera los 50 MB permitidos.',
            ],
            ['importFile' => 'archivo']
        );

        $zipPath = $this->importFile?->getRealPath();

        if (! $zipPath || ! is_file($zipPath)) {
            $this->error('El archivo no se pudo subir. Verifica el límite de subida de PHP (public/.user.ini).');

            return;
        }

        try {
            $detectedTheme = app(ThemePackageService::class)->importFromZip($zipPath);

            $this->importFile = null;
            $this->loadThemes();
            $this->selectedTheme = $detectedTheme;
            $this->updatedSelectedTheme();
            $this->success("Tema '{$detectedTheme}' importado correctamente.");
        } catch (RuntimeException $e) {
            $this->error($e->getMessage());
        }
    }
}
?>

<div class="mx-1">
    <x-header title="Gestor de Temas" subtitle="Administra las vistas personalizadas para cada tema" separator>
        <x-slot:actions>
            <a
                href="{{ route('themes.builder', ['theme' => $selectedTheme ?: 'default', 'variant' => $selectedVariant]) }}"
                class="btn btn-secondary btn-sm gap-1.5"
            >
                <x-icon name="o-sparkles" class="w-4 h-4" />
                Studio
            </a>

            <div class="flex items-center gap-2 ml-2 border-l pl-3 border-base-300">
                <x-input wire:model="newThemeName" placeholder="Nuevo tema..." class="input-sm" />
                <x-button wire:click="createTheme" icon="o-plus" class="btn-primary btn-sm" tooltip="Crear tema" />
            </div>
            
            <div class="flex items-center gap-2 ml-2 border-l pl-3 border-base-300">
                <x-input wire:model="newVariantName" placeholder="Nueva variante..." class="input-sm" />
                <x-button wire:click="createVariant" icon="o-plus" class="btn-primary btn-outline btn-sm" tooltip="Crear variante en tema actual" />
            </div>
        </x-slot:actions>
    </x-header>

    <div class="mb-6 flex flex-col md:flex-row items-end gap-4 bg-base-200 p-4 rounded-lg">
        <div class="w-full md:w-1/3">
            <x-select 
                label="Tema Seleccionado" 
                wire:model.live="selectedTheme" 
                :options="collect($themes)->map(fn($t) => ['id' => $t, 'name' => $t])->toArray()" 
                placeholder="Selecciona un tema..." 
            />
        </div>
        
        @if($selectedTheme)
            <div class="w-full md:w-1/3">
                <x-select 
                    label="Variante (Opcional)" 
                    wire:model.live="selectedVariant" 
                    :options="collect($variants)->map(fn($v) => ['id' => $v, 'name' => $v])->toArray()" 
                    placeholder="Ninguna (Raíz del tema)" 
                />
            </div>
            
            <div class="w-full md:w-1/3 pb-1">
                <x-button wire:click="activateTheme" icon="o-check-circle" class="btn-success w-full" spinner>
                    Activar Tema
                </x-button>
            </div>
        @endif
    </div>

    @if($selectedTheme)
        @if($selectedTheme === 'default')
            <div class="text-center text-gray-500 py-8">
                <x-icon name="o-shield-check" class="w-12 h-12 mx-auto mb-4 text-success opacity-80" />
                <h3 class="text-xl font-bold mb-2">Tema Base (Core)</h3>
                <p>El tema <strong>default</strong> carga directamente las vistas originales del sistema.</p>
                <p class="text-sm mt-2 opacity-75">Para sobrescribir vistas Blade manualmente crea un tema nuevo, o edita zonas visuales y páginas estáticas desde Studio.</p>
                <p class="text-xs mt-2 opacity-60">Los componentes de catálogo (card y vista única) se editan desde un tema activo: compilar sobre el tema base sobrescribiría las vistas del core.</p>
                <div class="mt-4 flex flex-wrap justify-center gap-2">
                    <a href="{{ route('themes.builder', ['theme' => 'default']) }}" class="btn btn-primary btn-sm">
                        <x-icon name="o-sparkles" class="w-4 h-4" />
                        Abrir Studio en Tema Base
                    </a>
                </div>
            </div>
        @else
            <div class="mb-4 flex flex-col gap-2 p-4 bg-base-100 border border-base-300 rounded-lg text-sm text-base-content/80 shadow-sm">
                <div class="flex items-center gap-2">
                    <x-icon name="o-folder" class="w-4 h-4 text-primary" />
                    <span class="font-semibold">Vistas:</span> 
                    <span class="font-mono bg-base-200 px-1 rounded">resources/views/themes/{{ $selectedTheme }}</span>
                </div>
                <div class="flex items-center gap-2">
                    <x-icon name="o-paint-brush" class="w-4 h-4 text-secondary" />
                    <span class="font-semibold">CSS (Tailwind):</span> 
                    <span class="font-mono bg-base-200 px-1 rounded">resources/css/themes/{{ $selectedTheme }}.css</span>
                </div>
                <div class="flex items-center gap-2">
                    <x-icon name="o-photo" class="w-4 h-4 text-info" />
                    <span class="font-semibold">Assets (Públicos):</span> 
                    <span class="font-mono bg-base-200 px-1 rounded">public/themes/{{ $selectedTheme }}/images/</span>
                    <span class="text-xs opacity-70 ml-2 hidden md:inline">Uso en Blade: <code>&lbrace;&lbrace; asset('themes/{{ $selectedTheme }}/images/archivo.png') &rbrace;&rbrace;</code></span>
                </div>

                <div class="divider my-2"></div>

                {{-- Export / Import --}}
                <div class="flex flex-col sm:flex-row items-start sm:items-center gap-3">
                    <div class="flex items-center gap-2 shrink-0">
                        <x-icon name="o-archive-box-arrow-down" class="w-4 h-4 text-success" />
                        <span class="font-semibold text-sm">Portabilidad:</span>
                    </div>

                    {{-- Export button --}}
                    <x-button
                        wire:click="exportTheme"
                        icon="o-arrow-down-tray"
                        class="btn-success btn-sm"
                        spinner="exportTheme"
                        tooltip="Descarga un ZIP con vistas, CSS y assets del tema"
                    >
                        Exportar tema
                    </x-button>

                    {{-- Import --}}
                    <div class="flex items-center gap-2 flex-1">
                        <x-file
                            wire:model="importFile"
                            accept=".zip"
                            class="file-input-sm flex-1"
                        />
                        <x-dropdown right>
                            <x-slot:trigger>
                                <x-button
                                    icon="o-arrow-up-tray"
                                    class="btn-warning btn-sm"
                                    tooltip="Importa un ZIP exportado desde este gestor"
                                    label="Importar"
                                />
                            </x-slot:trigger>
                            <x-menu-item title="¿Importar tema? Sobreescribirá archivos existentes" icon="o-question-mark-circle" />
                            <x-menu-separator />
                            <x-menu-item
                                title="Sí, importar y sobreescribir"
                                icon="o-arrow-up-tray"
                                wire:click="importTheme"
                                spinner="importTheme"
                            />
                            <x-menu-item title="Cancelar" icon="o-x-mark" />
                        </x-dropdown>
                    </div>
                </div>
            </div>

            <x-card title="Componentes de Catálogo (Card y Vista Única — Studio)" class="shadow-sm mb-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @foreach($this->themeCatalogComponents as $catalogComp)
                        <div class="p-4 rounded-xl border border-base-300 bg-base-100 flex flex-col justify-between gap-3">
                            <div>
                                <div class="flex items-center justify-between gap-2">
                                    <h4 class="font-bold text-sm">{{ $catalogComp['label'] }}</h4>
                                    @if($catalogComp['is_compiled'])
                                        <span class="badge badge-success badge-sm">Personalizado (AOT)</span>
                                    @elseif($catalogComp['has_native_file'])
                                        <span class="badge badge-info badge-sm">Override Manual</span>
                                    @else
                                        <span class="badge badge-ghost badge-sm">Motor Base (Core)</span>
                                    @endif
                                </div>
                                <p class="text-xs opacity-70 mt-1">{{ $catalogComp['description'] }}</p>
                                @if($catalogComp['has_backup'])
                                    <p class="text-[11px] text-warning mt-1">Con respaldo .bak.blade.php disponible para restaurar.</p>
                                @endif
                            </div>
                            <div class="flex items-center justify-between pt-2 border-t border-base-200">
                                <span class="text-[11px] font-mono opacity-60">{{ $catalogComp['target_view'] }}</span>
                                <a href="{{ $catalogComp['builder_url'] }}" class="btn btn-primary btn-xs">
                                    <x-icon name="o-paint-brush" class="w-3.5 h-3.5" />
                                    Editar en Studio
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            </x-card>

            <x-card title="Zonas Visuales del Tema (Studio)" class="shadow-sm mb-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @foreach($this->themeZones as $zone)
                        <div class="p-4 rounded-xl border border-base-300 bg-base-100 flex flex-col justify-between gap-3">
                            <div>
                                <div class="flex items-center justify-between gap-2">
                                    <h4 class="font-bold text-sm">{{ $zone['label'] }}</h4>
                                    @if($zone['has_active'])
                                        <span class="badge badge-success badge-sm">Activa (AOT)</span>
                                    @elseif($zone['is_compiled'])
                                        <span class="badge badge-warning badge-sm">Sin bloques activos</span>
                                    @else
                                        <span class="badge badge-ghost badge-sm">Sin personalizar</span>
                                    @endif
                                </div>
                                <p class="text-xs opacity-70 mt-1">{{ $zone['description'] }}</p>
                            </div>
                            <div class="flex items-center justify-between pt-2 border-t border-base-200">
                                <span class="text-[11px] font-mono opacity-60">{{ $zone['key'] }}</span>
                                <a href="{{ $zone['builder_url'] }}" class="btn btn-primary btn-xs">
                                    <x-icon name="o-paint-brush" class="w-3.5 h-3.5" />
                                    Editar en Studio
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            </x-card>

            <x-card title="Páginas Estáticas (/page)" class="shadow-sm mb-6">
                <div class="flex flex-col md:flex-row items-center justify-between mb-4 gap-4">
                    <div class="text-sm opacity-70 w-full md:w-1/2 text-center md:text-left">
                        Agrega páginas institucionales o informativas (ej: nosotros, contacto).<br>Aparecerán automáticamente en el menú.
                    </div>
                    <div class="w-full md:w-auto flex items-center gap-2 justify-end">
                        <x-input wire:model="newPageSlug" placeholder="Nombre de página..." class="input-sm" />
                        <x-button wire:click="createThemePage" icon="o-plus" class="btn-primary btn-sm" tooltip="Crear página" />
                    </div>
                </div>

                <div class="overflow-x-auto border border-base-300 rounded-lg">
                    <table class="table table-zebra table-sm w-full">
                        <thead>
                            <tr>
                                <th>Título</th>
                                <th>URL (Slug)</th>
                                <th>Origen</th>
                                <th>Archivo Físico</th>
                                <th class="text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($this->themePages as $page)
                                <tr>
                                    <td class="font-bold">{{ $page['title'] }}</td>
                                    <td class="font-mono text-sm">
                                        <a href="{{ $page['url'] }}" target="_blank" class="text-primary hover:underline flex items-center gap-1">
                                            /page/{{ $page['slug'] }}
                                            <x-icon name="o-arrow-top-right-on-square" class="w-3 h-3" />
                                        </a>
                                    </td>
                                    <td>
                                        @if($page['is_managed'])
                                            <span class="badge badge-primary badge-xs">Constructor Visual</span>
                                        @else
                                            <span class="badge badge-ghost badge-xs">Blade Nativa</span>
                                        @endif
                                    </td>
                                    <td class="font-mono text-xs opacity-75">{{ $page['file'] }}</td>
                                    <td class="text-right">
                                        <div class="inline-flex items-center gap-1.5">
                                            <a href="{{ $page['builder_url'] }}" class="btn btn-primary btn-xs btn-outline">
                                                <x-icon name="o-sparkles" class="w-3.5 h-3.5" />
                                                Constructor Visual
                                            </a>
                                            <x-dropdown right>
                                                <x-slot:trigger>
                                                    <x-button icon="o-trash" class="btn-error btn-xs btn-outline" tooltip="Eliminar página" />
                                                </x-slot:trigger>
                                                <x-menu-item title="¿Eliminar '{{ $page['title'] }}'?" icon="o-question-mark-circle" />
                                                <x-menu-separator />
                                                <x-menu-item
                                                    title="Sí, borrar página física"
                                                    icon="o-trash"
                                                    wire:click="deleteThemePage('{{ $page['slug'] }}')"
                                                    spinner="deleteThemePage"
                                                />
                                                <x-menu-item title="Cancelar" icon="o-x-mark" />
                                            </x-dropdown>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-gray-500">
                                        No se han creado páginas para este tema aún.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-card>

            <x-card title="Vistas del motor vs Overrides de Tema" class="shadow-sm">
                {{-- Filtros: Estado y Categorías --}}
                <div class="flex flex-col gap-3 mb-4 p-3 bg-base-200/50 rounded-lg border border-base-300">
                    {{-- 1. Filtro por Estado de Override --}}
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="text-xs font-semibold uppercase tracking-wider text-base-content/60 px-1 flex items-center gap-1">
                            <x-icon name="o-arrows-right-left" class="w-3.5 h-3.5" /> Estado:
                        </span>
                        <button 
                            type="button"
                            wire:click="$set('statusFilter', 'all')" 
                            class="btn btn-xs {{ $statusFilter === 'all' ? 'btn-neutral' : 'btn-ghost' }}"
                        >
                            Todas las vistas <span class="badge badge-xs">{{ $this->statusCounts['all'] ?? 0 }}</span>
                        </button>
                        <button 
                            type="button"
                            wire:click="$set('statusFilter', 'overridden')" 
                            class="btn btn-xs {{ $statusFilter === 'overridden' ? 'btn-success' : 'btn-ghost' }}"
                        >
                            <x-icon name="o-check-circle" class="w-3.5 h-3.5" /> Sobrescritas en {{ $selectedTheme }} <span class="badge badge-xs">{{ $this->statusCounts['overridden'] ?? 0 }}</span>
                        </button>
                        <button 
                            type="button"
                            wire:click="$set('statusFilter', 'fallback')" 
                            class="btn btn-xs {{ $statusFilter === 'fallback' ? 'btn-neutral' : 'btn-ghost' }}"
                        >
                            <x-icon name="o-arrow-path" class="w-3.5 h-3.5" /> Usando Motor Base <span class="badge badge-xs">{{ $this->statusCounts['fallback'] ?? 0 }}</span>
                        </button>
                    </div>

                    <div class="border-t border-base-300/60"></div>

                    {{-- 2. Filtro por Categorías / Ámbitos --}}
                    <div class="flex flex-wrap items-center gap-1.5">
                        <span class="text-xs font-semibold uppercase tracking-wider text-base-content/60 px-1 flex items-center gap-1">
                            <x-icon name="o-funnel" class="w-3.5 h-3.5" /> Ámbito:
                        </span>
                        <button 
                            type="button"
                            wire:click="$set('selectedCategory', 'all')" 
                            class="btn btn-xs {{ $selectedCategory === 'all' ? 'btn-neutral' : 'btn-ghost' }}"
                        >
                            Todos <span class="badge badge-xs">{{ $this->categoriesCount['all'] ?? 0 }}</span>
                        </button>
                        <button 
                            type="button"
                            wire:click="$set('selectedCategory', 'store')" 
                            class="btn btn-xs {{ $selectedCategory === 'store' ? 'btn-primary' : 'btn-ghost' }}"
                        >
                            <x-icon name="o-shopping-bag" class="w-3.5 h-3.5" /> Tienda <span class="badge badge-xs">{{ $this->categoriesCount['store'] ?? 0 }}</span>
                        </button>
                        <button 
                            type="button"
                            wire:click="$set('selectedCategory', 'admin')" 
                            class="btn btn-xs {{ $selectedCategory === 'admin' ? 'btn-warning' : 'btn-ghost' }}"
                        >
                            <x-icon name="o-cog-6-tooth" class="w-3.5 h-3.5" /> Admin <span class="badge badge-xs">{{ $this->categoriesCount['admin'] ?? 0 }}</span>
                        </button>
                        <button 
                            type="button"
                            wire:click="$set('selectedCategory', 'components')" 
                            class="btn btn-xs {{ $selectedCategory === 'components' ? 'btn-secondary' : 'btn-ghost' }}"
                        >
                            <x-icon name="o-rectangle-group" class="w-3.5 h-3.5" /> Layout / UI <span class="badge badge-xs">{{ $this->categoriesCount['components'] ?? 0 }}</span>
                        </button>
                        <button 
                            type="button"
                            wire:click="$set('selectedCategory', 'emails')" 
                            class="btn btn-xs {{ $selectedCategory === 'emails' ? 'btn-info' : 'btn-ghost' }}"
                        >
                            <x-icon name="o-envelope" class="w-3.5 h-3.5" /> Emails <span class="badge badge-xs">{{ $this->categoriesCount['emails'] ?? 0 }}</span>
                        </button>
                        <button 
                            type="button"
                            wire:click="$set('selectedCategory', 'errors')" 
                            class="btn btn-xs {{ $selectedCategory === 'errors' ? 'btn-neutral' : 'btn-ghost' }}"
                        >
                            <x-icon name="o-shield-exclamation" class="w-3.5 h-3.5" /> Páginas HTTP <span class="badge badge-xs">{{ $this->categoriesCount['errors'] ?? 0 }}</span>
                        </button>
                        <button 
                            type="button"
                            wire:click="$set('selectedCategory', 'root')" 
                            class="btn btn-xs {{ $selectedCategory === 'root' ? 'btn-neutral' : 'btn-ghost' }}"
                        >
                            <x-icon name="o-document-text" class="w-3.5 h-3.5" /> Base <span class="badge badge-xs">{{ $this->categoriesCount['root'] ?? 0 }}</span>
                        </button>
                    </div>
                </div>
                <div class="flex flex-col md:flex-row items-center justify-between mb-4 gap-4">
                    <div class="text-sm opacity-70 w-full md:w-auto text-center md:text-left">
                        Mostrando overrides para: <strong>{{ $this->selectedVariant ? "{$selectedTheme} / {$selectedVariant}" : $selectedTheme }}</strong>
                    </div>
                    <div class="w-full md:w-1/3">
                        <x-input wire:model.live.debounce.300ms="search" placeholder="Buscar componente o vista..." icon="o-magnifying-glass" clearable class="input-sm" />
                    </div>
                </div>

                <div class="overflow-x-auto max-h-[600px] border border-base-300 rounded-lg">
                    <table class="table table-pin-rows table-sm w-full">
                        <thead>
                            <tr>
                                <th class="w-2/3">Componente Base vs Equivalente en Tema</th>
                                <th class="text-center w-1/6">Resolución Activa</th>
                                <th class="text-right w-1/6">Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($this->views as $view)
                                <tr class="{{ $view['category']['bg'] }} transition-colors">
                                    <td class="py-2.5">
                                        <div class="flex flex-col gap-1.5">
                                            {{-- Vista Base del Motor --}}
                                            <div class="flex items-center gap-2">
                                                <span class="badge badge-ghost badge-sm gap-1 shrink-0 border-base-300">
                                                    <x-icon name="{{ $view['category']['icon'] }}" class="w-3 h-3 opacity-70" />
                                                    <span class="opacity-80">{{ $view['category']['label'] }}</span>
                                                </span>
                                                <div class="font-mono text-xs sm:text-sm">
                                                    <span class="text-xs opacity-50">resources/views/</span>@if($view['directory'])<span class="opacity-60">{{ $view['directory'] }}</span>@endif<span class="font-semibold text-base-content">{{ $view['filename'] }}</span>
                                                </div>
                                            </div>

                                            {{-- Equivalente en el Tema Seleccionado --}}
                                            <div class="flex items-center gap-2 pl-3 ml-2 border-l-2 {{ $view['overridden'] ? 'border-success' : 'border-base-300/60' }} text-xs font-mono">
                                                @if($view['overridden'])
                                                    <x-icon name="o-arrow-turn-down-right" class="w-3.5 h-3.5 text-success shrink-0" />
                                                    <span class="text-success font-medium">resources/views/{{ $view['theme_path'] }}</span>
                                                @else
                                                    <span class="opacity-40 italic">↳ Sin override en este tema (utiliza la plantilla del motor base)</span>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-center align-middle">
                                        @if($view['overridden'])
                                            <div class="flex flex-col items-center gap-1">
                                                <x-badge value="Activo en Tema" class="badge-success badge-sm font-medium" />
                                                @if($view['source'] === 'studio')
                                                    <span class="text-[10px] text-success/80 font-mono">Studio · bloques</span>
                                                @else
                                                    <span
                                                        class="text-[10px] text-warning font-mono cursor-help"
                                                        title="Blade escrito a mano: se edita en el archivo, no en Studio. Para contenido visual usá una página o zona del tema."
                                                    >Blade manual</span>
                                                @endif
                                            </div>
                                        @else
                                            <div class="flex flex-col items-center gap-1">
                                                <x-badge value="Motor Base" class="badge-ghost badge-sm opacity-80" />
                                                <span class="text-[10px] opacity-40 font-mono">Fallback</span>
                                            </div>
                                        @endif
                                    </td>
                                    <td class="text-right">
                                        @if($view['overridden'])
                                            <div class="inline-flex items-center gap-1">
                                                @if($view['studio_url'])
                                                    <a
                                                        href="{{ $view['studio_url'] }}"
                                                        class="btn btn-primary btn-outline btn-xs gap-1"
                                                        title="Editar este contenido en Studio"
                                                    >
                                                        <x-icon name="o-sparkles" class="w-3.5 h-3.5" />
                                                        Studio
                                                    </a>
                                                @endif
                                                <x-dropdown right>
                                                    <x-slot:trigger>
                                                        <x-button icon="o-arrow-uturn-left" class="btn-error btn-xs" tooltip="Revertir a base" />
                                                    </x-slot:trigger>
                                                    <x-menu-item title="¿Eliminar override y volver al fallback?" icon="o-question-mark-circle" />
                                                    <x-menu-separator />
                                                    <x-menu-item
                                                        title="Sí, revertir vista"
                                                        icon="o-arrow-uturn-left"
                                                        wire:click="revertView('{{ $view['file'] }}')"
                                                        spinner="revertView"
                                                    />
                                                    <x-menu-item title="Cancelar" icon="o-x-mark" />
                                                </x-dropdown>
                                            </div>
                                        @else
                                            <x-button wire:click="overrideView('{{ $view['file'] }}')" icon="o-document-duplicate" class="btn-primary btn-outline btn-xs" tooltip="Crear override" />
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center py-4 text-gray-500">
                                        No se encontraron vistas con esa búsqueda.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-card>
        @endif
    @else
        <div class="text-center text-gray-500 py-10">
            <x-icon name="o-swatch" class="w-12 h-12 mx-auto mb-4 opacity-50" />
            <p>Selecciona un tema de la barra superior para gestionar sus vistas.</p>
        </div>
    @endif
</div>
