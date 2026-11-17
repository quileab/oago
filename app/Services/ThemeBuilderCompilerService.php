<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use InvalidArgumentException;

class ThemeBuilderCompilerService
{
    public const COMPILED_MARKER = '{{-- @oago-builder-compiled --}}';

    /**
     * @var array<int, string>
     */
    private const IMAGE_EXTENSIONS = ['png', 'jpg', 'jpeg', 'webp', 'svg', 'gif'];

    public function __construct(
        private ThemeService $themeService,
        private ThemeWidgetRegistry $registry,
        private ThemeVariableInterpolator $interpolator,
        private ThemeCatalogComponentRegistry $catalogRegistry
    ) {}

    /**
     * Load a page blueprint JSON (or inspect native file status) for the visual builder.
     *
     * @return array{
     *     schema_version: int,
     *     target: string,
     *     slug: string,
     *     title: string,
     *     blocks: array<int, array{id: string, type: string, enabled: bool, props: array<string, mixed>}>,
     *     is_managed: bool,
     *     has_native_file: bool,
     *     has_backup: bool,
     *     updated_at: string|null
     * }
     */
    public function getPageBlueprint(string $theme, string $variant, string $slug): array
    {
        $cleanSlug = $this->normalizeSlug($slug);
        $jsonPath = $this->pageJsonPath($theme, $variant, $cleanSlug);
        $bladePath = $this->pageBladePath($theme, $variant, $cleanSlug);
        $backupPath = $this->pageBackupPath($theme, $variant, $cleanSlug);

        if (File::exists($jsonPath)) {
            $decoded = json_decode((string) File::get($jsonPath), true);
            if (is_array($decoded)) {
                return [
                    'schema_version' => (int) ($decoded['schema_version'] ?? 1),
                    'target' => 'page',
                    'slug' => $cleanSlug,
                    'title' => (string) ($decoded['title'] ?? Str::headline($cleanSlug)),
                    'blocks' => $this->normalizeBlocks((array) ($decoded['blocks'] ?? [])),
                    'is_managed' => true,
                    'has_native_file' => File::exists($bladePath) && ! $this->isCompiledFile($bladePath),
                    'has_backup' => File::exists($backupPath),
                    'updated_at' => isset($decoded['updated_at']) ? (string) $decoded['updated_at'] : null,
                ];
            }
        }

        $hasNative = File::exists($bladePath) && ! $this->isCompiledFile($bladePath);

        return [
            'schema_version' => 1,
            'target' => 'page',
            'slug' => $cleanSlug,
            'title' => Str::headline($cleanSlug),
            'blocks' => [],
            'is_managed' => false,
            'has_native_file' => $hasNative,
            'has_backup' => File::exists($backupPath),
            'updated_at' => null,
        ];
    }

    /**
     * Load a layout/home zone blueprint JSON for the visual builder.
     *
     * @return array{
     *     schema_version: int,
     *     target: string,
     *     zone: string,
     *     title: string,
     *     blocks: array<int, array{id: string, type: string, enabled: bool, props: array<string, mixed>}>,
     *     is_compiled: bool,
     *     updated_at: string|null
     * }
     */
    public function getZoneBlueprint(string $theme, string $variant, string $zone): array
    {
        $zoneMeta = $this->assertValidZone($zone);
        $jsonPath = $this->zoneJsonPath($theme, $variant, $zone);
        $bladePath = $this->zoneBladePath($theme, $variant, $zone);

        if (File::exists($jsonPath)) {
            $decoded = json_decode((string) File::get($jsonPath), true);
            if (is_array($decoded)) {
                return [
                    'schema_version' => (int) ($decoded['schema_version'] ?? 1),
                    'target' => 'zone',
                    'zone' => $zone,
                    'title' => $zoneMeta['label'],
                    'blocks' => $this->normalizeBlocks((array) ($decoded['blocks'] ?? [])),
                    'is_compiled' => File::exists($bladePath),
                    'updated_at' => isset($decoded['updated_at']) ? (string) $decoded['updated_at'] : null,
                ];
            }
        }

        return [
            'schema_version' => 1,
            'target' => 'zone',
            'zone' => $zone,
            'title' => $zoneMeta['label'],
            'blocks' => [],
            'is_compiled' => File::exists($bladePath),
            'updated_at' => null,
        ];
    }

    /**
     * Persist a page JSON blueprint and AOT-compile its native `.blade.php` view.
     *
     * @param  array<int, array<string, mixed>>  $blocks
     * @return array{json_path: string, blade_path: string, backup_created: bool, blade_source: string}
     */
    public function compileAndSavePage(string $theme, string $variant, string $slug, string $title, array $blocks): array
    {
        $cleanSlug = $this->normalizeSlug($slug);
        $normalizedBlocks = $this->normalizeBlocks($blocks);
        $rawTitle = $this->ensureUtf8(trim($title));
        $resolvedTitle = $rawTitle !== '' ? $rawTitle : Str::headline($cleanSlug);

        $jsonPath = $this->pageJsonPath($theme, $variant, $cleanSlug);
        $bladePath = $this->pageBladePath($theme, $variant, $cleanSlug);
        $backupPath = $this->pageBackupPath($theme, $variant, $cleanSlug);

        $backupCreated = false;
        if (File::exists($bladePath) && ! $this->isCompiledFile($bladePath) && ! File::exists($backupPath)) {
            File::ensureDirectoryExists(dirname($backupPath));
            File::copy($bladePath, $backupPath);
            $backupCreated = true;
        }

        $blueprint = [
            'schema_version' => 1,
            'target' => 'page',
            'slug' => $cleanSlug,
            'title' => $resolvedTitle,
            'blocks' => $normalizedBlocks,
            'updated_at' => now()->toIso8601String(),
        ];

        $bladeSource = $this->compileBlocksToBladeSource($normalizedBlocks, true, $resolvedTitle);

        File::ensureDirectoryExists(dirname($jsonPath));
        File::put($jsonPath, json_encode($blueprint, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."\n");

        File::ensureDirectoryExists(dirname($bladePath));
        File::put($bladePath, $bladeSource);

        $this->invalidateCompiledView($bladePath);

        return [
            'json_path' => $jsonPath,
            'blade_path' => $bladePath,
            'backup_created' => $backupCreated,
            'blade_source' => $bladeSource,
        ];
    }

    /**
     * Persist a zone JSON blueprint and AOT-compile its partial `.blade.php` view.
     *
     * @param  array<int, array<string, mixed>>  $blocks
     * @return array{json_path: string, blade_path: string, blade_source: string}
     */
    public function compileAndSaveZone(string $theme, string $variant, string $zone, array $blocks): array
    {
        $zoneMeta = $this->assertValidZone($zone);
        $normalizedBlocks = $this->normalizeBlocks($blocks);

        $jsonPath = $this->zoneJsonPath($theme, $variant, $zone);
        $bladePath = $this->zoneBladePath($theme, $variant, $zone);

        $blueprint = [
            'schema_version' => 1,
            'target' => 'zone',
            'zone' => $zone,
            'title' => $zoneMeta['label'],
            'blocks' => $normalizedBlocks,
            'updated_at' => now()->toIso8601String(),
        ];

        File::ensureDirectoryExists(dirname($jsonPath));
        File::put($jsonPath, json_encode($blueprint, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."\n");

        $bladeSource = $this->compileBlocksToBladeSource($normalizedBlocks, false, $zoneMeta['label']);

        File::ensureDirectoryExists(dirname($bladePath));
        File::put($bladePath, $bladeSource);

        $this->invalidateCompiledView($bladePath);

        return [
            'json_path' => $jsonPath,
            'blade_path' => $bladePath,
            'blade_source' => $bladeSource,
        ];
    }

    /**
     * Compile normalized blocks directly into zero-overhead Blade source code.
     *
     * @param  array<int, array{id: string, type: string, enabled: bool, props: array<string, mixed>}>  $blocks
     */
    public function compileBlocksToBladeSource(array $blocks, bool $wrapLayout = false, string $pageTitle = ''): string
    {
        $lines = [self::COMPILED_MARKER];

        if ($wrapLayout) {
            $titleExpr = $this->interpolator->compileToPhpExpression($pageTitle);
            $lines[] = '@php';
            $lines[] = '    $__oagoPageTitle = '.$titleExpr.';';
            $lines[] = '@endphp';
            $lines[] = '<x-layouts.clean :title="$__oagoPageTitle">';
            $lines[] = '<div class="w-full">';
        }

        $enabledIndex = 0;
        foreach ($blocks as $block) {
            if (! ($block['enabled'] ?? true)) {
                continue;
            }

            $definition = $this->registry->get($block['type']);
            if ($definition === null) {
                continue;
            }

            $varName = '$__oagoWidget'.$enabledIndex;
            $enabledIndex++;

            $slotFields = [];
            $phpLines = $this->compilePropsArrayLines($definition, $block['props'], $slotFields);

            $lines[] = '';
            $lines[] = '{{-- Bloque '.$enabledIndex.': '.$block['type'].' ('.$block['id'].') --}}';
            $lines[] = '@php';
            $lines[] = '    '.$varName.' = [';
            foreach ($phpLines as $propLine) {
                $lines[] = '        '.$propLine;
            }
            $lines[] = '    ];';
            $lines[] = '@endphp';

            $tagName = 'x-theme-widgets.'.$block['type'];
            if ($slotFields === []) {
                $lines[] = '<'.$tagName.' :props="'.$varName.'" :preview="false" />';
            } else {
                $lines[] = '<'.$tagName.' :props="'.$varName.'" :preview="false">';
                foreach ($slotFields as $slotBlade) {
                    $lines[] = '    <x-slot:htmlContent>';
                    $lines[] = $slotBlade;
                    $lines[] = '    </x-slot:htmlContent>';
                }
                $lines[] = '</'.$tagName.'>';
            }
        }

        if ($wrapLayout) {
            $lines[] = '</div>';
            $lines[] = '</x-layouts.clean>';
        }

        return implode("\n", $lines)."\n";
    }

    /**
     * Prepare blocks for instant live preview inside the Studio canvas without mutating blueprint tokens.
     *
     * @param  array<int, array<string, mixed>>  $blocks
     * @return array<int, array{id: string, type: string, enabled: bool, props: array<string, mixed>}>
     */
    public function preparePreviewBlocks(array $blocks, string $theme): array
    {
        $normalized = $this->normalizeBlocks($blocks);
        $prepared = [];

        foreach ($normalized as $block) {
            $definition = $this->registry->get($block['type']);
            if ($definition === null) {
                continue;
            }

            $block['props'] = $this->resolvePreviewProps($definition['fields'], $definition['defaults'], $block['props'], $theme);
            $prepared[] = $block;
        }

        return $prepared;
    }

    public function hasNativeBackup(string $theme, string $variant, string $slug): bool
    {
        $cleanSlug = $this->normalizeSlug($slug);

        return File::exists($this->pageBackupPath($theme, $variant, $cleanSlug));
    }

    public function restoreNativeBackup(string $theme, string $variant, string $slug): bool
    {
        $cleanSlug = $this->normalizeSlug($slug);
        $backupPath = $this->pageBackupPath($theme, $variant, $cleanSlug);
        $bladePath = $this->pageBladePath($theme, $variant, $cleanSlug);
        $jsonPath = $this->pageJsonPath($theme, $variant, $cleanSlug);

        if (! File::exists($backupPath)) {
            return false;
        }

        File::ensureDirectoryExists(dirname($bladePath));
        File::copy($backupPath, $bladePath);
        File::delete($backupPath);

        if (File::exists($jsonPath)) {
            File::delete($jsonPath);
        }

        $this->invalidateCompiledView($bladePath);

        return true;
    }

    public function isPageManagedByBuilder(string $theme, string $variant, string $slug): bool
    {
        $cleanSlug = $this->normalizeSlug($slug);

        return File::exists($this->pageJsonPath($theme, $variant, $cleanSlug))
            || $this->isCompiledFile($this->pageBladePath($theme, $variant, $cleanSlug));
    }

    public function isZoneCompiled(string $theme, string $variant, string $zone): bool
    {
        $this->assertValidZone($zone);

        return File::exists($this->zoneBladePath($theme, $variant, $zone));
    }

    public function zoneHasActiveBlocks(string $theme, string $variant, string $zone): bool
    {
        $blueprint = $this->getZoneBlueprint($theme, $variant, $zone);

        foreach ($blueprint['blocks'] as $block) {
            if ($block['enabled'] ?? true) {
                return true;
            }
        }

        return false;
    }

    /**
     * List all available image asset paths inside `public/themes/{theme}/images`.
     *
     * @return array<int, string>
     */
    public function listThemeImages(string $theme): array
    {
        $this->assertValidThemeAndVariant($theme, '');

        $baseDir = public_path("themes/{$theme}/images");
        if (! File::isDirectory($baseDir)) {
            return [];
        }

        $images = [];
        foreach (File::allFiles($baseDir) as $file) {
            $ext = strtolower($file->getExtension());
            if (! in_array($ext, self::IMAGE_EXTENSIONS, true)) {
                continue;
            }

            $images[] = str_replace('\\', '/', $file->getRelativePathname());
        }

        sort($images);

        return $images;
    }

    /**
     * Normalize incoming block arrays and enforce valid widget schemas and defaults.
     *
     * @param  array<int, array<string, mixed>>  $blocks
     * @return array<int, array{id: string, type: string, enabled: bool, props: array<string, mixed>}>
     */
    public function normalizeBlocks(array $blocks): array
    {
        $normalized = [];

        foreach ($blocks as $block) {
            if (! is_array($block)) {
                continue;
            }

            $type = (string) ($block['type'] ?? '');
            $definition = $this->registry->get($type);
            if ($definition === null) {
                continue;
            }

            $rawId = (string) ($block['id'] ?? '');
            $id = preg_match('/^[a-zA-Z0-9_-]+$/', $rawId) ? $rawId : 'blk_'.bin2hex(random_bytes(4));
            $incomingProps = is_array($block['props'] ?? null) ? $block['props'] : [];

            $mergedProps = $definition['defaults'];
            foreach ($incomingProps as $key => $value) {
                $mergedProps[$key] = $this->sanitizeUtf8Recursive($value);
            }

            $normalized[] = [
                'id' => $id,
                'type' => $type,
                'enabled' => (bool) ($block['enabled'] ?? true),
                'props' => $mergedProps,
            ];
        }

        return $normalized;
    }

    /**
     * Convert a native non-builder page's safe inner HTML into an editable custom_blade fallback block.
     *
     * @return array{id: string, type: string, enabled: bool, props: array<string, mixed>}
     */
    public function buildNativeImportBlock(string $theme, string $variant, string $slug): array
    {
        $cleanSlug = $this->normalizeSlug($slug);
        $bladePath = $this->pageBladePath($theme, $variant, $cleanSlug);
        $block = $this->registry->defaultBlock('custom_blade');
        $block['props']['title'] = 'Contenido importado: '.Str::headline($cleanSlug);

        if (! File::exists($bladePath)) {
            return $block;
        }

        $raw = $this->ensureUtf8((string) File::get($bladePath));
        $raw = preg_replace('/^\s*<\?php.*?\?>\s*/is', '', $raw) ?? $raw;
        if (preg_match('#<x-layouts\.clean[^>]*>(.*)</x-layouts\.clean>#is', $raw, $matches)) {
            $raw = trim($matches[1]);
        }

        $block['props']['html'] = trim($raw);

        return $block;
    }

    /**
     * Compile widget prop entries to indented PHP array lines, extracting slot HTML fields cleanly.
     *
     * @param  array<string, mixed>  $definition
     * @param  array<string, mixed>  $props
     * @param  array<int, string>  $slotFields
     * @return array<int, string>
     */
    private function compilePropsArrayLines(array $definition, array $props, array &$slotFields): array
    {
        $lines = [];
        $fields = $definition['fields'];
        $defaults = $definition['defaults'];

        foreach ($fields as $field) {
            $key = (string) $field['key'];
            $fieldType = (string) ($field['type'] ?? 'text');
            $value = $props[$key] ?? ($defaults[$key] ?? null);

            if (in_array($fieldType, ['richtext', 'code'], true)) {
                $slotFields[] = $this->interpolator->compileToBlade((string) ($value ?? ''), false);
                $lines[] = var_export($key, true)." => '',";

                continue;
            }

            if ($fieldType === 'repeater') {
                $itemFields = (array) ($field['item_fields'] ?? []);
                $items = is_array($value) ? $value : [];
                $lines[] = var_export($key, true).' => [';

                foreach ($items as $item) {
                    if (! is_array($item)) {
                        continue;
                    }

                    $lines[] = '    [';
                    foreach ($itemFields as $itemField) {
                        $itemKey = (string) $itemField['key'];
                        $itemType = (string) ($itemField['type'] ?? 'text');
                        $itemVal = $item[$itemKey] ?? '';
                        $compiledExpr = $this->compileScalarFieldExpression($itemType, $itemVal, '#1b365d');
                        $lines[] = '        '.var_export($itemKey, true).' => '.$compiledExpr.',';
                    }
                    $lines[] = '    ],';
                }

                $lines[] = '],';

                continue;
            }

            $defaultVal = (string) ($defaults[$key] ?? '#1b365d');
            $compiledExpr = $this->compileScalarFieldExpression($fieldType, $value, $defaultVal);
            $lines[] = var_export($key, true).' => '.$compiledExpr.',';
        }

        return $lines;
    }

    private function compileScalarFieldExpression(string $fieldType, mixed $value, string $colorFallback): string
    {
        return match ($fieldType) {
            'toggle' => var_export((bool) $value, true),
            'color' => var_export($this->sanitizeColor((string) ($value ?? ''), $colorFallback), true),
            'image' => $this->interpolator->compileAssetPhpExpression((string) ($value ?? '')),
            default => $this->interpolator->compileToPhpExpression((string) ($value ?? '')),
        };
    }

    /**
     * Resolve block props for live preview rendering inside the Studio component.
     *
     * @param  array<int, array<string, mixed>>  $fields
     * @param  array<string, mixed>  $defaults
     * @param  array<string, mixed>  $props
     * @return array<string, mixed>
     */
    private function resolvePreviewProps(array $fields, array $defaults, array $props, string $theme): array
    {
        $resolved = $defaults;

        foreach ($fields as $field) {
            $key = (string) $field['key'];
            $fieldType = (string) ($field['type'] ?? 'text');
            $value = $props[$key] ?? ($defaults[$key] ?? null);

            if ($fieldType === 'repeater') {
                $itemFields = (array) ($field['item_fields'] ?? []);
                $items = is_array($value) ? $value : [];
                $resolvedItems = [];

                foreach ($items as $item) {
                    if (! is_array($item)) {
                        continue;
                    }

                    $resolvedItem = [];
                    foreach ($itemFields as $itemField) {
                        $itemKey = (string) $itemField['key'];
                        $itemType = (string) ($itemField['type'] ?? 'text');
                        $resolvedItem[$itemKey] = $this->resolvePreviewScalar($itemType, $item[$itemKey] ?? '', '#1b365d', $theme);
                    }
                    $resolvedItems[] = $resolvedItem;
                }

                $resolved[$key] = $resolvedItems;

                continue;
            }

            $fallback = is_string($defaults[$key] ?? null) ? (string) $defaults[$key] : '#1b365d';
            $resolved[$key] = $this->resolvePreviewScalar($fieldType, $value, $fallback, $theme);
        }

        return $resolved;
    }

    private function resolvePreviewScalar(string $fieldType, mixed $value, string $colorFallback, string $theme): mixed
    {
        return match ($fieldType) {
            'toggle' => (bool) $value,
            'color' => $this->sanitizeColor((string) ($value ?? ''), $colorFallback),
            'image' => $this->interpolator->resolveAssetPreviewUrl((string) ($value ?? ''), $theme),
            'richtext', 'code' => $this->interpolator->interpolatePreview((string) ($value ?? ''), $theme, false),
            default => htmlspecialchars_decode($this->interpolator->interpolatePreview((string) ($value ?? ''), $theme, true), ENT_QUOTES),
        };
    }

    private function sanitizeColor(string $value, string $fallback): string
    {
        $trimmed = trim($value);
        if (strtolower($trimmed) === 'transparent') {
            return 'transparent';
        }

        if (preg_match('/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6}|[0-9a-fA-F]{8})$/', $trimmed)) {
            return $trimmed;
        }

        return preg_match('/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $fallback) ? $fallback : '#1b365d';
    }

    private function isCompiledFile(string $bladePath): bool
    {
        if (! File::exists($bladePath)) {
            return false;
        }

        return str_contains((string) File::get($bladePath), self::COMPILED_MARKER);
    }

    private function resolveViewsRoot(string $theme, string $variant): string
    {
        $this->assertValidThemeAndVariant($theme, $variant);

        if ($theme === 'default') {
            return resource_path('views');
        }

        $prefix = $this->themeService->resolvePrefix($theme, $variant);

        return resource_path("views/themes/{$prefix}");
    }

    private function pageJsonPath(string $theme, string $variant, string $slug): string
    {
        return $this->resolveViewsRoot($theme, $variant)."/builder/pages/{$slug}.json";
    }

    private function pageBladePath(string $theme, string $variant, string $slug): string
    {
        return $this->resolveViewsRoot($theme, $variant)."/pages/{$slug}.blade.php";
    }

    private function pageBackupPath(string $theme, string $variant, string $slug): string
    {
        return $this->resolveViewsRoot($theme, $variant)."/builder/backups/pages/{$slug}.bak.blade.php";
    }

    private function zoneJsonPath(string $theme, string $variant, string $zone): string
    {
        return $this->resolveViewsRoot($theme, $variant)."/builder/zones/{$zone}.json";
    }

    private function zoneBladePath(string $theme, string $variant, string $zone): string
    {
        return $this->resolveViewsRoot($theme, $variant)."/partials/zones/{$zone}.blade.php";
    }

    /**
     * Load a catalog component blueprint (`product_card` or `product_detail`) for Studio.
     *
     * @return array{
     *     schema_version: int,
     *     target: string,
     *     component: string,
     *     title: string,
     *     blocks: array<int, array{id: string, type: string, enabled: bool, props: array<string, mixed>}>,
     *     is_managed: bool,
     *     has_native_file: bool,
     *     has_backup: bool,
     *     updated_at: string|null
     * }
     */
    public function getCatalogComponentBlueprint(string $theme, string $variant, string $component): array
    {
        $meta = $this->assertValidCatalogComponent($component);
        $jsonPath = $this->catalogJsonPath($theme, $variant, $component);
        $bladePath = $this->catalogBladePath($theme, $variant, $component);
        $backupPath = $this->catalogBackupPath($theme, $variant, $component);

        $isCompiled = $this->isCompiledFile($bladePath);
        $hasNative = File::exists($bladePath) && ! $isCompiled;

        if (File::exists($jsonPath)) {
            $decoded = json_decode((string) File::get($jsonPath), true);
            if (is_array($decoded)) {
                return [
                    'schema_version' => (int) ($decoded['schema_version'] ?? 1),
                    'target' => 'catalog',
                    'component' => $component,
                    'title' => $meta['label'],
                    'blocks' => $this->normalizeCatalogBlocks($component, (array) ($decoded['blocks'] ?? [])),
                    'is_managed' => $isCompiled || File::exists($jsonPath),
                    'has_native_file' => $hasNative,
                    'has_backup' => File::exists($backupPath),
                    'updated_at' => isset($decoded['updated_at']) ? (string) $decoded['updated_at'] : null,
                ];
            }
        }

        return [
            'schema_version' => 1,
            'target' => 'catalog',
            'component' => $component,
            'title' => $meta['label'],
            'blocks' => $this->catalogRegistry->defaultBlocks($component),
            'is_managed' => false,
            'has_native_file' => $hasNative,
            'has_backup' => File::exists($backupPath),
            'updated_at' => null,
        ];
    }

    /**
     * Persist catalog component blueprint JSON and AOT-compile its Blade view override.
     *
     * @param  array<int, array<string, mixed>>  $blocks
     * @return array{json_path: string, blade_path: string, backup_created: bool, blade_source: string}
     */
    public function compileAndSaveCatalogComponent(string $theme, string $variant, string $component, array $blocks): array
    {
        $meta = $this->assertValidCatalogComponent($component);
        $this->assertCatalogThemeIsWritable($theme);
        $normalizedBlocks = $this->normalizeCatalogBlocks($component, $blocks);

        $jsonPath = $this->catalogJsonPath($theme, $variant, $component);
        $bladePath = $this->catalogBladePath($theme, $variant, $component);
        $backupPath = $this->catalogBackupPath($theme, $variant, $component);

        $backupCreated = false;
        if (File::exists($bladePath) && ! $this->isCompiledFile($bladePath) && ! File::exists($backupPath)) {
            File::ensureDirectoryExists(dirname($backupPath));
            File::copy($bladePath, $backupPath);
            $backupCreated = true;
        }

        $blueprint = [
            'schema_version' => 1,
            'target' => 'catalog',
            'component' => $component,
            'title' => $meta['label'],
            'blocks' => $normalizedBlocks,
            'updated_at' => now()->toIso8601String(),
        ];

        File::ensureDirectoryExists(dirname($jsonPath));
        File::put($jsonPath, json_encode($blueprint, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."\n");

        $bladeSource = $this->compileCatalogComponentToBladeSource($component, $normalizedBlocks);

        File::ensureDirectoryExists(dirname($bladePath));
        File::put($bladePath, $bladeSource);

        $this->invalidateCompiledView($bladePath);

        return [
            'json_path' => $jsonPath,
            'blade_path' => $bladePath,
            'backup_created' => $backupCreated,
            'blade_source' => $bladeSource,
        ];
    }

    /**
     * Compile normalized catalog sub-blocks into a standalone Blade/Volt file with zero JSON runtime reads.
     *
     * @param  array<int, array{id: string, type: string, enabled: bool, props: array<string, mixed>}>  $blocks
     */
    public function compileCatalogComponentToBladeSource(string $component, array $blocks): string
    {
        $this->assertValidCatalogComponent($component);
        $normalized = $this->normalizeCatalogBlocks($component, $blocks);
        $exportedBlocks = var_export($normalized, true);

        if ($component === 'product_detail') {
            return <<<BLADE
<?php

use App\Helpers\SettingsHelper;
use App\Models\Product;
use App\Services\ProductSearchService;
use Illuminate\Support\Collection;
use Livewire\Volt\Component;
use Mary\Traits\Toast;

new class extends Component
{
    use Toast;

    public \$product;

    public \$qtty = 1;

    public \$related_products = [];

    public array \$media = [];

    public array \$tags = [];

    public \$user_price = 0;

    public \$offer_price = 0;

    public function mount(Product|int|string|null \$prod_id = null, Product|int|string|null \$id = null): void
    {
        \$targetId = \$prod_id instanceof Product
            ? \$prod_id->id
            : (int) (\$prod_id ?? (\$id instanceof Product ? \$id->id : (\$id ?? 0)));

        \$this->product = \$targetId > 0
            ? app(ProductSearchService::class)->searchProducts(['id' => \$targetId], 1)
            : null;

        if (! \$this->product) {
            \$this->product = new Product([
                'id' => 0,
                'brand' => 'Producto no encontrado',
                'description' => 'El producto que busca no está disponible o no existe.',
                'image_url' => asset('imgs/fallback.webp'),
                'featured' => false,
                'description_html' => '',
                'user_price' => 0,
                'offer_price' => 0,
                'qtty_unit' => 1,
                'qtty_package' => 1,
                'stock' => 0,
            ]);
            \$this->related_products = [];
        } else {
            \$this->user_price = \$this->product->base_price ?? (float) \$this->product->price;
            \$this->offer_price = \$this->product->promo_price ?? 0;
            \$this->qtty = \$this->product->qtty_package;
            \$this->media = \$this->product->media ?? [];
            \$this->tags = \$this->product->commercial_tags_array ?? [];
        }
    }

    public function getRelatedProducts(): Collection|array
    {
        if (! empty(\$this->related_products)) {
            return \$this->related_products;
        }

        if (! \$this->product || ! \$this->product->id) {
            return collect();
        }

        \$this->related_products = app(ProductSearchService::class)
            ->searchRelatedProducts(\$this->product, 12);

        return \$this->related_products;
    }

    public function buy(int \$productId): void
    {
        \$this->dispatch('addToCart', product: \$productId, quantity: (int) \$this->qtty);

        \$this->qtty = \$this->product->qtty_package ?? 1;
    }

    public function primaryMedia(): array
    {
        return \$this->media[0] ?? [
            'type' => 'image',
            'url' => \$this->product->image_url,
            'thumb' => \$this->product->image_url,
        ];
    }

    public function stockBadge(): array
    {
        return \$this->product->stockBadge();
    }

    public function canBuy(): bool
    {
        \$basePrice = \$this->offer_price > 0 ? \$this->offer_price : \$this->user_price;
        \$currUser = current_user();

        return \$this->product->hasAvailableStock()
            && \$currUser
            && ! in_array(\$currUser->role->value, ['none', 'guest'], true)
            && \$basePrice > 0;
    }

    public function unitPrice(): float
    {
        \$basePrice = \$this->offer_price > 0 ? \$this->offer_price : \$this->user_price;

        return \$this->product->qtty_unit > 0 ? \$basePrice / \$this->product->qtty_unit : \$basePrice;
    }

    public function showPrices(): bool
    {
        return current_user() !== null || (bool) SettingsHelper::settings('show_prices_to_guests', false);
    }

    public function guestMessage(): string
    {
        return (bool) SettingsHelper::settings('show_prices_to_guests', false)
            ? 'Inicie sesión para comprar'
            : 'Inicie sesión para ver precios y comprar';
    }
}; ?>
{{-- @oago-builder-compiled --}}
@php
    \$__oagoCatalogBlocks = {$exportedBlocks};
    \$__oagoPrimaryMedia = \$this->primaryMedia();
    \$__oagoStockBadge = \$this->stockBadge();
    \$__oagoCanBuy = \$this->canBuy();
    \$__oagoIsGuest = current_user() === null;
    \$__oagoShowPrices = \$this->showPrices();
    \$__oagoGuestMessage = \$this->guestMessage();
    \$__oagoUnitPrice = \$this->unitPrice();
    \$__oagoIsOutOfStock = \$product ? \$product->isOutOfStock() : false;
    \$__oagoRelatedItems = \$this->getRelatedProducts();
@endphp
<x-theme-catalog.product-detail
    :blocks="\$__oagoCatalogBlocks"
    :preview="false"
    :product="\$product"
    :media="\$this->media"
    :primary-media="\$__oagoPrimaryMedia"
    :tags="\$this->tags"
    :user-price="\$user_price"
    :offer-price="\$offer_price"
    :unit-price="\$__oagoUnitPrice"
    :stock-badge="\$__oagoStockBadge"
    :can-buy="\$__oagoCanBuy"
    :is-guest="\$__oagoIsGuest"
    :show-prices="\$__oagoShowPrices"
    :guest-message="\$__oagoGuestMessage"
    :is-out-of-stock="\$__oagoIsOutOfStock"
    :related-items="\$__oagoRelatedItems"
/>

BLADE;
        }

        return <<<BLADE
{{-- @oago-builder-compiled --}}
@php
    \$__oagoCatalogBlocks = {$exportedBlocks};
@endphp
<x-theme-catalog.product-card
    :blocks="\$__oagoCatalogBlocks"
    :preview="false"
    :product="\$product"
    :display-price="\$display_price ?? 0"
    :display-offer="\$display_offer ?? 0"
    :cart="\$cart ?? []"
    :is-guest="\$isGuest ?? true"
    :show-prices="\$showPrices ?? false"
    :guest-message="\$guestMessage ?? ''"
    :is-out-of-stock="\$isOutOfStock ?? false"
    :has-available-stock="\$hasAvailableStock ?? false"
    :stock-badge="\$stockBadge ?? []"
    :cart-payload="\$cartPayload ?? []"
/>

BLADE;
    }

    /**
     * Normalize and complete catalog sub-blocks preserving custom layer ordering.
     *
     * @param  array<int, array<string, mixed>>  $blocks
     * @return array<int, array{id: string, type: string, enabled: bool, props: array<string, mixed>}>
     */
    public function normalizeCatalogBlocks(string $component, array $blocks): array
    {
        $definitions = $this->catalogRegistry->definitions($component);
        $styleType = $component === 'product_detail' ? 'detail_style' : 'card_style';
        $pinnedEndTypes = $component === 'product_detail' ? ['detail_related'] : [];

        $byType = [];
        $order = [];

        foreach ($blocks as $block) {
            if (! is_array($block)) {
                continue;
            }

            $type = (string) ($block['type'] ?? '');
            if ($type === '' || ! isset($definitions[$type]) || isset($byType[$type])) {
                continue;
            }

            $def = $definitions[$type];
            $incomingProps = is_array($block['props'] ?? null) ? $block['props'] : [];
            $mergedProps = $def['defaults'];
            foreach ($incomingProps as $key => $value) {
                $mergedProps[$key] = $this->sanitizeUtf8Recursive($value);
            }

            $rawId = (string) ($block['id'] ?? '');
            $id = preg_match('/^[a-zA-Z0-9_-]+$/', $rawId) ? $rawId : 'cat_'.$type;

            $byType[$type] = [
                'id' => $id,
                'type' => $type,
                'enabled' => ($def['fixed'] ?? false) ? true : (bool) ($block['enabled'] ?? true),
                'props' => $mergedProps,
            ];
            $order[] = $type;
        }

        foreach ($definitions as $type => $def) {
            if (! isset($byType[$type])) {
                $byType[$type] = [
                    'id' => 'cat_'.$type,
                    'type' => $type,
                    'enabled' => $type !== 'card_excerpt',
                    'props' => $def['defaults'],
                ];
                $order[] = $type;
            }
        }

        $ordered = [];
        if (isset($byType[$styleType])) {
            $ordered[] = $byType[$styleType];
        }

        foreach ($order as $type) {
            if ($type === $styleType || in_array($type, $pinnedEndTypes, true)) {
                continue;
            }
            $ordered[] = $byType[$type];
        }

        foreach ($pinnedEndTypes as $type) {
            if (isset($byType[$type])) {
                $ordered[] = $byType[$type];
            }
        }

        return $ordered;
    }

    public function restoreCatalogComponentBackup(string $theme, string $variant, string $component): bool
    {
        $this->assertValidCatalogComponent($component);
        $backupPath = $this->catalogBackupPath($theme, $variant, $component);
        $bladePath = $this->catalogBladePath($theme, $variant, $component);
        $jsonPath = $this->catalogJsonPath($theme, $variant, $component);

        if ($theme === 'default' && ! File::exists($backupPath)) {
            return false;
        }

        $restored = false;

        if (File::exists($backupPath)) {
            File::ensureDirectoryExists(dirname($bladePath));
            File::copy($backupPath, $bladePath);
            File::delete($backupPath);
            $restored = true;
        } elseif ($theme !== 'default' && File::exists($bladePath) && $this->isCompiledFile($bladePath)) {
            File::delete($bladePath);
            $restored = true;
        }

        if (File::exists($jsonPath)) {
            File::delete($jsonPath);
            $restored = true;
        }

        if ($restored) {
            $this->invalidateCompiledView($bladePath);
        }

        return $restored;
    }

    public function isCatalogComponentCompiled(string $theme, string $variant, string $component): bool
    {
        $this->assertValidCatalogComponent($component);

        return $this->isCompiledFile($this->catalogBladePath($theme, $variant, $component));
    }

    private function catalogJsonPath(string $theme, string $variant, string $component): string
    {
        return $this->resolveViewsRoot($theme, $variant)."/builder/components/{$component}.json";
    }

    private function catalogBladePath(string $theme, string $variant, string $component): string
    {
        $meta = $this->assertValidCatalogComponent($component);

        return $this->resolveViewsRoot($theme, $variant).'/'.$meta['target_view'];
    }

    private function catalogBackupPath(string $theme, string $variant, string $component): string
    {
        return $this->resolveViewsRoot($theme, $variant)."/builder/backups/components/{$component}.bak.blade.php";
    }

    /**
     * @return array{key: string, label: string, short_label: string, description: string, target_view: string}
     */
    private function assertValidCatalogComponent(string $component): array
    {
        $components = $this->catalogRegistry->components();
        if (! isset($components[$component])) {
            throw new InvalidArgumentException("Componente de catálogo no soportado: {$component}");
        }

        return $components[$component];
    }

    private function assertValidThemeAndVariant(string $theme, string $variant): void
    {
        if (! $this->themeService->isValidSegment($theme)) {
            throw new InvalidArgumentException("Nombre de tema inválido: {$theme}");
        }

        if ($variant !== '' && ! $this->themeService->isValidSegment($variant)) {
            throw new InvalidArgumentException("Nombre de variante inválido: {$variant}");
        }
    }

    /**
     * Catalog components replace an existing view file, so `default` (which resolves to the
     * core views root) must never be a valid compile target.
     */
    private function assertCatalogThemeIsWritable(string $theme): void
    {
        if ($theme === 'default') {
            throw new InvalidArgumentException('Los componentes de catálogo se editan desde un tema, no desde el tema base: compilar sobre "default" sobrescribiría las vistas del core.');
        }
    }

    private function normalizeSlug(string $slug): string
    {
        $clean = Str::slug($slug);
        if ($clean === '') {
            throw new InvalidArgumentException('El slug de la página no es válido.');
        }

        return $clean;
    }

    /**
     * @return array{key: string, label: string, description: string, badge: string}
     */
    private function assertValidZone(string $zone): array
    {
        $zones = $this->registry->zones();
        if (! isset($zones[$zone])) {
            throw new InvalidArgumentException("Zona de tema no soportada: {$zone}");
        }

        return $zones[$zone];
    }

    private function invalidateCompiledView(string $bladePath): void
    {
        try {
            $compiledPath = app('blade.compiler')->getCompiledPath($bladePath);
            if (File::exists($compiledPath)) {
                File::delete($compiledPath);
            }

            if (function_exists('opcache_invalidate')) {
                @opcache_invalidate($bladePath, true);
                @opcache_invalidate($compiledPath, true);
            }
        } catch (\Throwable) {
            // Ignorado en entornos donde la caché de vistas no está inicializada aún.
        }
    }

    private function sanitizeUtf8Recursive(mixed $value): mixed
    {
        if (is_string($value)) {
            return $this->ensureUtf8($value);
        }

        if (! is_array($value)) {
            return $value;
        }

        foreach ($value as $key => $item) {
            $value[$key] = $this->sanitizeUtf8Recursive($item);
        }

        return $value;
    }

    private function ensureUtf8(string $text): string
    {
        if ($text === '' || mb_check_encoding($text, 'UTF-8')) {
            return $text;
        }

        $converted = @mb_convert_encoding($text, 'UTF-8', 'Windows-1252');
        if (is_string($converted) && mb_check_encoding($converted, 'UTF-8')) {
            return $converted;
        }

        $clean = @iconv('UTF-8', 'UTF-8//IGNORE', $text);

        return is_string($clean) ? $clean : '';
    }
}
