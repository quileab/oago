<?php

use App\Services\ThemeBuilderCompilerService;
use App\Services\ThemeCatalogComponentRegistry;
use App\Services\ThemeService;
use App\Services\ThemeVariableInterpolator;
use App\Services\ThemeWidgetRegistry;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Mary\Traits\Toast;

new
#[Layout('components.layouts.studio')]
#[Title('Studio — Constructor Visual de Temas')]
class extends Component
{
    use Toast;
    use WithFileUploads;

    #[Url(as: 'theme', keep: true)]
    public string $selectedTheme = '';

    #[Url(as: 'variant', except: '')]
    public string $selectedVariant = '';

    #[Url(as: 'target')]
    public string $targetType = 'page';

    #[Url(as: 'slug', except: '')]
    public string $selectedSlug = '';

    #[Url(as: 'zone', except: 'home_before_catalog')]
    public string $selectedZone = 'home_before_catalog';

    #[Url(as: 'component', except: 'product_card')]
    public string $selectedComponent = 'product_card';

    public string $catalogPreviewState = 'offer';

    public string $pageTitle = '';

    public string $newPageSlug = '';

    public bool $isManaged = false;

    public bool $hasNativeFile = false;

    public bool $hasBackup = false;

    public bool $hasUnsavedDraft = false;

    public ?string $lastUpdatedAt = null;

    /** @var array<int, array{id: string, type: string, enabled: bool, props: array<string, mixed>}> */
    public array $blocks = [];

    public int $selectedIndex = 0;

    public bool $activeEnabled = true;

    /** @var array<string, mixed> */
    public array $activeProps = [];

    public string $viewport = 'desktop';

    public string $canvasTab = 'visual';

    public bool $showTemplatesModal = false;

    public bool $showVariablesDrawer = false;

    public bool $showInspectorDrawer = false;

    public bool $showMediaModal = false;

    public bool $showNewPageModal = false;

    public string $mediaTargetField = '';

    public ?int $mediaTargetItemIndex = null;

    public string $mediaTargetRepeaterKey = '';

    /** @var TemporaryUploadedFile|null */
    public $uploadedImage = null;

    /** @var array<int, string> */
    public array $themes = [];

    /** @var array<int, string> */
    public array $variants = [];

    /** @var array<int, array{slug: string, title: string, is_managed: bool, exists: bool}> */
    public array $pagesList = [];

    public function mount(): void
    {
        $themeService = app(ThemeService::class);

        $this->loadThemes();

        $queryTheme = trim((string) request()->query('theme', $this->selectedTheme));
        $queryVariant = trim((string) request()->query('variant', $this->selectedVariant));
        $queryTarget = trim((string) request()->query('target', $this->targetType));
        $querySlug = trim((string) request()->query('slug', $this->selectedSlug));
        $queryZone = trim((string) request()->query('zone', $this->selectedZone));
        $queryComponent = trim((string) request()->query('component', $this->selectedComponent));

        $this->selectedTheme = ($queryTheme !== '' && in_array($queryTheme, $this->themes, true))
            ? $queryTheme
            : $themeService->activeTheme();

        if (! in_array($this->selectedTheme, $this->themes, true)) {
            $this->selectedTheme = $this->themes[0] ?? 'default';
        }

        $this->loadVariants();
        $this->selectedVariant = ($queryVariant !== '' && in_array($queryVariant, $this->variants, true))
            ? $queryVariant
            : ($this->selectedTheme === $themeService->activeTheme() ? $themeService->activeVariant() : '');

        $this->targetType = in_array($queryTarget, ['page', 'zone', 'catalog'], true) ? $queryTarget : 'page';

        $zones = app(ThemeWidgetRegistry::class)->zones();
        $this->selectedZone = isset($zones[$queryZone]) ? $queryZone : 'home_before_catalog';

        $catalogComponents = app(ThemeCatalogComponentRegistry::class)->components();
        $this->selectedComponent = isset($catalogComponents[$queryComponent]) ? $queryComponent : 'product_card';

        $this->loadPagesList();

        if ($querySlug !== '') {
            $this->selectedSlug = Str::slug($querySlug);
        } elseif ($this->pagesList !== []) {
            $this->selectedSlug = $this->pagesList[0]['slug'];
        } else {
            $this->selectedSlug = 'nosotros';
        }

        $this->ensureSelectedSlugInPagesList();
        $this->loadCurrentBlueprint();
    }

    public function updatedSelectedTheme(): void
    {
        $this->selectedVariant = '';
        $this->loadVariants();
        $this->loadPagesList();

        if ($this->targetType === 'page') {
            $slugs = array_column($this->pagesList, 'slug');
            if ($this->selectedSlug === '' || ($slugs !== [] && ! in_array($this->selectedSlug, $slugs, true))) {
                $this->selectedSlug = $slugs[0] ?? 'nosotros';
            }
            $this->ensureSelectedSlugInPagesList();
        }

        $this->loadCurrentBlueprint();
    }

    public function updatedSelectedVariant(): void
    {
        $this->loadPagesList();
        $this->ensureSelectedSlugInPagesList();
        $this->loadCurrentBlueprint();
    }

    public function updatedSelectedSlug(): void
    {
        $this->selectedSlug = Str::slug($this->selectedSlug) ?: 'nosotros';
        $this->ensureSelectedSlugInPagesList();
        $this->loadCurrentBlueprint();
    }

    public function updatedSelectedZone(): void
    {
        $zones = app(ThemeWidgetRegistry::class)->zones();
        if (! isset($zones[$this->selectedZone])) {
            $this->selectedZone = 'home_before_catalog';
        }

        $this->loadCurrentBlueprint();
    }

    public function updatedSelectedComponent(): void
    {
        $components = app(ThemeCatalogComponentRegistry::class)->components();
        if (! isset($components[$this->selectedComponent])) {
            $this->selectedComponent = 'product_card';
        }

        $this->loadCurrentBlueprint();
    }

    public function updatedPageTitle(): void
    {
        $this->saveDraftToSession();
    }

    public function updatedTargetType(): void
    {
        if (! in_array($this->targetType, ['page', 'zone', 'catalog'], true)) {
            $this->targetType = 'page';
        }

        $this->ensureSelectedSlugInPagesList();
        $this->loadCurrentBlueprint();
    }

    public function selectCatalogComponent(string $component): void
    {
        $components = app(ThemeCatalogComponentRegistry::class)->components();
        if (! isset($components[$component])) {
            return;
        }

        $this->targetType = 'catalog';
        $this->selectedComponent = $component;
        $this->loadCurrentBlueprint();
    }

    public function selectPage(string $slug): void
    {
        $this->targetType = 'page';
        $this->selectedSlug = Str::slug($slug) ?: 'nosotros';
        $this->ensureSelectedSlugInPagesList();
        $this->loadCurrentBlueprint();
    }

    public function selectZone(string $zone): void
    {
        $zones = app(ThemeWidgetRegistry::class)->zones();
        if (! isset($zones[$zone])) {
            return;
        }

        $this->targetType = 'zone';
        $this->selectedZone = $zone;
        $this->loadCurrentBlueprint();
    }

    public function createPageFromModal(): void
    {
        $slug = Str::slug(trim($this->newPageSlug));
        if ($slug === '') {
            $this->error('Ingresa un nombre o slug válido (ej: nosotros, contacto, envios).');

            return;
        }

        $this->targetType = 'page';
        $this->selectedSlug = $slug;
        $this->pageTitle = Str::headline($slug);
        $this->blocks = [];
        $this->selectedIndex = 0;
        $this->showNewPageModal = false;
        $this->newPageSlug = '';

        $compiler = app(ThemeBuilderCompilerService::class);
        $blueprint = $compiler->getPageBlueprint($this->selectedTheme, $this->selectedVariant, $slug);

        if ($blueprint['is_managed'] || $blueprint['has_native_file']) {
            $this->loadCurrentBlueprint();
            $this->info("Se cargó la página existente '/page/{$slug}'.");

            return;
        }

        $this->addBlock('hero_banner');
        $compiler->compileAndSavePage($this->selectedTheme, $this->selectedVariant, $slug, $this->pageTitle, $this->blocks);
        session()->forget($this->draftSessionKey());
        $this->hasUnsavedDraft = false;
        $this->loadPagesList();
        $this->ensureSelectedSlugInPagesList();
        $this->loadCurrentBlueprint();
        $this->success("Página '/page/{$slug}' creada y lista para editar.");
    }

    public function loadCurrentBlueprint(): void
    {
        $compiler = app(ThemeBuilderCompilerService::class);

        if ($this->targetType === 'zone') {
            $blueprint = $compiler->getZoneBlueprint($this->selectedTheme, $this->selectedVariant, $this->selectedZone);
            $this->pageTitle = $blueprint['title'];
            $this->blocks = $blueprint['blocks'];
            $this->isManaged = $blueprint['is_compiled'];
            $this->hasNativeFile = false;
            $this->hasBackup = false;
            $this->lastUpdatedAt = $blueprint['updated_at'];
        } elseif ($this->targetType === 'catalog') {
            $blueprint = $compiler->getCatalogComponentBlueprint($this->selectedTheme, $this->selectedVariant, $this->selectedComponent);
            $this->pageTitle = $blueprint['title'];
            $this->blocks = $blueprint['blocks'];
            $this->isManaged = $blueprint['is_managed'];
            $this->hasNativeFile = $blueprint['has_native_file'];
            $this->hasBackup = $blueprint['has_backup'];
            $this->lastUpdatedAt = $blueprint['updated_at'];
        } else {
            if ($this->selectedSlug === '') {
                $this->selectedSlug = 'nosotros';
            }

            $blueprint = $compiler->getPageBlueprint($this->selectedTheme, $this->selectedVariant, $this->selectedSlug);
            $this->pageTitle = $blueprint['title'];
            $this->blocks = $blueprint['blocks'];
            $this->isManaged = $blueprint['is_managed'];
            $this->hasNativeFile = $blueprint['has_native_file'];
            $this->hasBackup = $blueprint['has_backup'];
            $this->lastUpdatedAt = $blueprint['updated_at'];
        }

        $this->hasUnsavedDraft = false;
        $draft = session($this->draftSessionKey());
        if (is_array($draft) && array_key_exists('blocks', $draft)) {
            $this->blocks = $this->targetType === 'catalog'
                ? $compiler->normalizeCatalogBlocks($this->selectedComponent, (array) $draft['blocks'])
                : $compiler->normalizeBlocks((array) $draft['blocks']);
            if (isset($draft['title']) && is_string($draft['title']) && $this->targetType === 'page') {
                $this->pageTitle = $draft['title'];
            }
            $this->hasUnsavedDraft = true;
        }

        $this->selectedIndex = count($this->blocks) > 0 ? 0 : -1;
        $this->sanitizeBlocks();
        $this->syncActiveBlockState();
        if ($this->blocks === []) {
            $this->showInspectorDrawer = false;
        }
    }

    public function updatedActiveEnabled(): void
    {
        if ($this->selectedIndex < 0 || ! isset($this->blocks[$this->selectedIndex]['type'])) {
            return;
        }

        $this->blocks[$this->selectedIndex]['enabled'] = (bool) $this->activeEnabled;
        $this->sanitizeBlocks();
        $this->saveDraftToSession();
    }

    public function updatedActiveProps(mixed $value = null, ?string $key = null): void
    {
        if ($this->selectedIndex < 0 || ! isset($this->blocks[$this->selectedIndex]['type'])) {
            return;
        }

        $this->blocks[$this->selectedIndex]['props'] = array_replace(
            (array) ($this->blocks[$this->selectedIndex]['props'] ?? []),
            $this->activeProps
        );
        $this->saveDraftToSession();
    }

    public function addBlock(string $type): void
    {
        if ($this->targetType === 'catalog') {
            return;
        }

        $registry = app(ThemeWidgetRegistry::class);
        $block = $registry->defaultBlock($type);
        $this->blocks[] = $block;
        $this->selectedIndex = count($this->blocks) - 1;
        $this->canvasTab = 'visual';
        $this->sanitizeBlocks();
        $this->syncActiveBlockState();
        $this->saveDraftToSession();
        $this->showInspectorDrawer = true;
    }

    public function selectBlock(int $index): void
    {
        if (isset($this->blocks[$index]['type'])) {
            $this->selectedIndex = $index;
            $this->showInspectorDrawer = true;
        }

        $this->sanitizeBlocks();
        $this->syncActiveBlockState();
    }

    public function moveBlockUp(int $index): void
    {
        if ($index <= 0 || ! isset($this->blocks[$index], $this->blocks[$index - 1])) {
            return;
        }

        if ($this->targetType === 'catalog') {
            $defs = $this->catalogDefinitionsMap;
            $curDef = $defs[$this->blocks[$index]['type'] ?? ''] ?? [];
            $prevDef = $defs[$this->blocks[$index - 1]['type'] ?? ''] ?? [];
            if (($curDef['fixed'] ?? false) || ($curDef['pinned'] ?? false) || ($prevDef['fixed'] ?? false) || ($prevDef['pinned'] ?? false)) {
                return;
            }
        }

        $temp = $this->blocks[$index - 1];
        $this->blocks[$index - 1] = $this->blocks[$index];
        $this->blocks[$index] = $temp;
        $this->selectedIndex = $index - 1;
        $this->sanitizeBlocks();
        $this->syncActiveBlockState();
        $this->saveDraftToSession();
    }

    public function moveBlockDown(int $index): void
    {
        if ($index < 0 || $index >= count($this->blocks) - 1 || ! isset($this->blocks[$index], $this->blocks[$index + 1])) {
            return;
        }

        if ($this->targetType === 'catalog') {
            $defs = $this->catalogDefinitionsMap;
            $curDef = $defs[$this->blocks[$index]['type'] ?? ''] ?? [];
            $nextDef = $defs[$this->blocks[$index + 1]['type'] ?? ''] ?? [];
            if (($curDef['fixed'] ?? false) || ($curDef['pinned'] ?? false) || ($nextDef['fixed'] ?? false) || ($nextDef['pinned'] ?? false)) {
                return;
            }
        }

        $temp = $this->blocks[$index + 1];
        $this->blocks[$index + 1] = $this->blocks[$index];
        $this->blocks[$index] = $temp;
        $this->selectedIndex = $index + 1;
        $this->sanitizeBlocks();
        $this->syncActiveBlockState();
        $this->saveDraftToSession();
    }

    public function duplicateBlock(int $index): void
    {
        if ($this->targetType === 'catalog' || ! isset($this->blocks[$index])) {
            return;
        }

        $copy = $this->blocks[$index];
        $copy['id'] = 'blk_'.bin2hex(random_bytes(4));
        array_splice($this->blocks, $index + 1, 0, [$copy]);
        $this->selectedIndex = $index + 1;
        $this->sanitizeBlocks();
        $this->syncActiveBlockState();
        $this->saveDraftToSession();
        $this->showInspectorDrawer = true;
    }

    public function toggleBlock(int $index): void
    {
        if (! isset($this->blocks[$index]['type'])) {
            return;
        }

        if ($this->targetType === 'catalog') {
            $def = $this->catalogDefinitionsMap[$this->blocks[$index]['type']] ?? [];
            if ($def['fixed'] ?? false) {
                return;
            }
        }

        $this->blocks[$index]['enabled'] = ! (bool) ($this->blocks[$index]['enabled'] ?? true);
        $this->sanitizeBlocks();
        $this->syncActiveBlockState();
        $this->saveDraftToSession();
    }

    public function removeBlock(int $index): void
    {
        if ($this->targetType === 'catalog' || ! isset($this->blocks[$index])) {
            return;
        }

        array_splice($this->blocks, $index, 1);

        if (count($this->blocks) === 0) {
            $this->selectedIndex = -1;
            $this->showInspectorDrawer = false;
        } else {
            $this->selectedIndex = min($index, count($this->blocks) - 1);
        }

        $this->sanitizeBlocks();
        $this->syncActiveBlockState();
        $this->saveDraftToSession();
    }

    public function addRepeaterItem(string $repeaterKey): void
    {
        if ($this->selectedIndex < 0 || ! isset($this->blocks[$this->selectedIndex]['type'])) {
            return;
        }

        $definition = app(ThemeWidgetRegistry::class)->get((string) $this->blocks[$this->selectedIndex]['type']);
        if ($definition === null) {
            return;
        }

        $newItem = [];
        foreach ($definition['fields'] as $field) {
            if (($field['key'] ?? '') === $repeaterKey && ($field['type'] ?? '') === 'repeater') {
                foreach ((array) ($field['item_fields'] ?? []) as $itemField) {
                    $itemKey = (string) $itemField['key'];
                    $newItem[$itemKey] = $itemField['type'] === 'select'
                        ? (string) ($itemField['options'][0]['id'] ?? '')
                        : '';
                }
                break;
            }
        }

        $items = (array) ($this->blocks[$this->selectedIndex]['props'][$repeaterKey] ?? []);
        $items[] = $newItem;
        $this->blocks[$this->selectedIndex]['props'][$repeaterKey] = array_values($items);
        $this->sanitizeBlocks();
        $this->syncActiveBlockState();
        $this->saveDraftToSession();
    }

    public function removeRepeaterItem(string $repeaterKey, int $itemIndex): void
    {
        if ($this->selectedIndex < 0 || ! isset($this->blocks[$this->selectedIndex]['type'])) {
            return;
        }

        $items = (array) ($this->blocks[$this->selectedIndex]['props'][$repeaterKey] ?? []);
        if (! isset($items[$itemIndex])) {
            return;
        }

        array_splice($items, $itemIndex, 1);
        $this->blocks[$this->selectedIndex]['props'][$repeaterKey] = array_values($items);
        $this->sanitizeBlocks();
        $this->syncActiveBlockState();
        $this->saveDraftToSession();
    }

    public function applyStarterTemplate(string $templateKey): void
    {
        $templates = app(ThemeWidgetRegistry::class)->getStarterTemplates();
        if (! isset($templates[$templateKey])) {
            return;
        }

        $this->blocks = $templates[$templateKey]['blocks'];
        $this->selectedIndex = count($this->blocks) > 0 ? 0 : -1;
        $this->showTemplatesModal = false;
        $this->canvasTab = 'visual';
        $this->sanitizeBlocks();
        $this->syncActiveBlockState();
        $this->saveDraftToSession();
        $this->success("Plantilla '{$templates[$templateKey]['name']}' aplicada al lienzo. Pulsa 'Guardar y Compilar' para publicarla.");
    }

    public function importNativeAsBlock(): void
    {
        if ($this->targetType !== 'page' || $this->selectedSlug === '') {
            return;
        }

        $compiler = app(ThemeBuilderCompilerService::class);
        $this->blocks[] = $compiler->buildNativeImportBlock($this->selectedTheme, $this->selectedVariant, $this->selectedSlug);
        $this->selectedIndex = count($this->blocks) - 1;
        $this->sanitizeBlocks();
        $this->syncActiveBlockState();
        $this->saveDraftToSession();
        $this->showInspectorDrawer = true;
        $this->info('Contenido original importado como bloque HTML editable. Se creará una copia .bak al guardar.');
    }

    public function restoreNativePage(): void
    {
        if ($this->targetType !== 'page' || $this->selectedSlug === '') {
            return;
        }

        $restored = app(ThemeBuilderCompilerService::class)->restoreNativeBackup(
            $this->selectedTheme,
            $this->selectedVariant,
            $this->selectedSlug
        );

        if ($restored) {
            session()->forget($this->draftSessionKey());
            $this->hasUnsavedDraft = false;
            $this->loadPagesList();
            $this->ensureSelectedSlugInPagesList();
            $this->loadCurrentBlueprint();
            $this->success('Se restauró el archivo Blade nativo original y se limpió la compilación.');
        } else {
            $this->warning('No se encontró un archivo de respaldo nativo para esta página.');
        }
    }

    public function applyCatalogPreset(string $presetKey): void
    {
        if ($this->targetType !== 'catalog') {
            return;
        }

        $presets = app(ThemeCatalogComponentRegistry::class)->presets($this->selectedComponent);
        if (! isset($presets[$presetKey])) {
            return;
        }

        $this->blocks = $presets[$presetKey]['blocks'];
        $this->selectedIndex = count($this->blocks) > 0 ? 0 : -1;
        $this->canvasTab = 'visual';
        $this->sanitizeBlocks();
        $this->syncActiveBlockState();
        $this->saveDraftToSession();
        $this->success("Preset '{$presets[$presetKey]['name']}' aplicado. Pulsa 'Guardar y Compilar' para publicarlo.");
    }

    public function restoreCatalogComponent(): void
    {
        if ($this->targetType !== 'catalog') {
            return;
        }

        $restored = app(ThemeBuilderCompilerService::class)->restoreCatalogComponentBackup(
            $this->selectedTheme,
            $this->selectedVariant,
            $this->selectedComponent
        );

        if ($restored) {
            session()->forget($this->draftSessionKey());
            $this->hasUnsavedDraft = false;
            $this->loadCurrentBlueprint();
            $this->success('Se restauró la vista previa original / fallback del componente.');
        } else {
            $this->warning('No hay cambios compilados para revertir en este componente.');
        }
    }

    public function discardDraft(): void
    {
        session()->forget($this->draftSessionKey());
        $this->hasUnsavedDraft = false;
        $this->loadCurrentBlueprint();
        $this->info('Se descartaron los cambios sin guardar del borrador.');
    }

    public function openMediaPicker(string $fieldKey, ?int $itemIndex = null, string $repeaterKey = ''): void
    {
        $this->mediaTargetField = $fieldKey;
        $this->mediaTargetItemIndex = $itemIndex;
        $this->mediaTargetRepeaterKey = $repeaterKey;
        $this->uploadedImage = null;
        $this->showMediaModal = true;
    }

    public function selectMediaImage(string $relativeOrUrl): void
    {
        if ($this->selectedIndex < 0 || ! isset($this->blocks[$this->selectedIndex]['type'])) {
            $this->showMediaModal = false;

            return;
        }

        $formattedValue = ($this->selectedTheme !== 'default' && ! str_starts_with($relativeOrUrl, '/'))
            ? "{{ theme.image('".ltrim($relativeOrUrl, '/')."') }}"
            : $relativeOrUrl;

        if ($this->mediaTargetRepeaterKey !== '' && $this->mediaTargetItemIndex !== null) {
            $this->blocks[$this->selectedIndex]['props'][$this->mediaTargetRepeaterKey][$this->mediaTargetItemIndex][$this->mediaTargetField] = $formattedValue;
        } else {
            $this->blocks[$this->selectedIndex]['props'][$this->mediaTargetField] = $formattedValue;
        }

        $this->sanitizeBlocks();
        $this->syncActiveBlockState();
        $this->saveDraftToSession();
        $this->showMediaModal = false;
    }

    public function uploadImageToTheme(): void
    {
        $this->validate(
            ['uploadedImage' => 'required|file|mimes:png,jpg,jpeg,webp,svg,gif|max:5120'],
            [
                'uploadedImage.required' => 'Selecciona una imagen primero.',
                'uploadedImage.mimes' => 'Formato permitido: PNG, JPG, WEBP, SVG o GIF.',
                'uploadedImage.max' => 'La imagen no puede superar 5 MB.',
            ]
        );

        if (! $this->uploadedImage) {
            return;
        }

        $ext = strtolower($this->uploadedImage->getClientOriginalExtension() ?: 'webp');
        $baseName = Str::slug(pathinfo($this->uploadedImage->getClientOriginalName(), PATHINFO_FILENAME));
        $fileName = ($baseName !== '' ? $baseName : 'studio-img').'-'.substr(bin2hex(random_bytes(3)), 0, 5).'.'.$ext;

        if ($this->selectedTheme === 'default') {
            $destDir = public_path('imgs/builder');
            File::ensureDirectoryExists($destDir);
            File::copy($this->uploadedImage->getRealPath(), "{$destDir}/{$fileName}");
            $assigned = "/imgs/builder/{$fileName}";
        } else {
            $destDir = public_path("themes/{$this->selectedTheme}/images/studio");
            File::ensureDirectoryExists($destDir);
            File::copy($this->uploadedImage->getRealPath(), "{$destDir}/{$fileName}");
            $assigned = "studio/{$fileName}";
        }

        $this->uploadedImage = null;
        unset($this->themeImages);
        $this->selectMediaImage($assigned);
        $this->success('Imagen subida y asignada al bloque.');
    }

    public function appendTokenToField(string $fieldKey, string $token): void
    {
        if ($this->selectedIndex < 0 || ! isset($this->blocks[$this->selectedIndex]['type'])) {
            return;
        }

        $current = (string) ($this->blocks[$this->selectedIndex]['props'][$fieldKey] ?? '');
        $separator = $current !== '' && ! str_ends_with($current, ' ') ? ' ' : '';
        $this->blocks[$this->selectedIndex]['props'][$fieldKey] = $current.$separator.$token;
        $this->sanitizeBlocks();
        $this->syncActiveBlockState();
        $this->saveDraftToSession();
    }

    public function saveAndCompile(): void
    {
        $this->sanitizeBlocks();
        $compiler = app(ThemeBuilderCompilerService::class);

        if ($this->targetType === 'zone') {
            $compiler->compileAndSaveZone($this->selectedTheme, $this->selectedVariant, $this->selectedZone, $this->blocks);
            session()->forget($this->draftSessionKey());
            $this->hasUnsavedDraft = false;
            $this->loadCurrentBlueprint();
            $this->success("Zona '{$this->selectedZone}' compilada a Blade nativo y publicada.");

            return;
        }

        if ($this->targetType === 'catalog') {
            try {
                $result = $compiler->compileAndSaveCatalogComponent($this->selectedTheme, $this->selectedVariant, $this->selectedComponent, $this->blocks);
            } catch (InvalidArgumentException $e) {
                $this->error($e->getMessage());

                return;
            }

            session()->forget($this->draftSessionKey());
            $this->hasUnsavedDraft = false;
            $this->loadCurrentBlueprint();

            $meta = app(ThemeCatalogComponentRegistry::class)->components()[$this->selectedComponent] ?? null;
            $label = $meta['short_label'] ?? $this->selectedComponent;
            $msg = "Componente '{$label}' compilado a Blade nativo (0ms overhead).";
            if ($result['backup_created']) {
                $msg .= ' Se resguardó una copia .bak de la vista original.';
            }
            $this->success($msg);

            return;
        }

        $result = $compiler->compileAndSavePage(
            $this->selectedTheme,
            $this->selectedVariant,
            $this->selectedSlug,
            $this->pageTitle,
            $this->blocks
        );

        session()->forget($this->draftSessionKey());
        $this->hasUnsavedDraft = false;
        $this->loadPagesList();
        $this->ensureSelectedSlugInPagesList();
        $this->loadCurrentBlueprint();

        $msg = "Página '/page/{$this->selectedSlug}' compilada a Blade nativo (0ms overhead).";
        if ($result['backup_created']) {
            $msg .= ' Se resguardó una copia .bak de la vista original.';
        }
        $this->success($msg);
    }

    /**
     * @return array<int, array{id: string, type: string, enabled: bool, props: array<string, mixed>}>
     */
    #[Computed]
    public function previewBlocks(): array
    {
        if ($this->targetType === 'catalog') {
            return $this->blocks;
        }

        return app(ThemeBuilderCompilerService::class)->preparePreviewBlocks($this->blocks, $this->selectedTheme);
    }

    #[Computed]
    public function compiledBladePreview(): string
    {
        $compiler = app(ThemeBuilderCompilerService::class);

        if ($this->targetType === 'catalog') {
            return $compiler->compileCatalogComponentToBladeSource($this->selectedComponent, $this->blocks);
        }

        return $compiler->compileBlocksToBladeSource(
            $compiler->normalizeBlocks($this->blocks),
            $this->targetType === 'page',
            $this->pageTitle
        );
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    #[Computed]
    public function catalogComponents(): array
    {
        return app(ThemeCatalogComponentRegistry::class)->components();
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    #[Computed]
    public function catalogDefinitionsMap(): array
    {
        if ($this->targetType !== 'catalog') {
            return [];
        }

        return app(ThemeCatalogComponentRegistry::class)->definitions($this->selectedComponent);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    #[Computed]
    public function catalogPresets(): array
    {
        if ($this->targetType !== 'catalog') {
            return [];
        }

        return app(ThemeCatalogComponentRegistry::class)->presets($this->selectedComponent);
    }

    /**
     * @return array<string, mixed>
     */
    #[Computed]
    public function catalogMockData(): array
    {
        if ($this->targetType !== 'catalog') {
            return [];
        }

        return app(ThemeCatalogComponentRegistry::class)->mockPreviewData($this->catalogPreviewState);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    #[Computed]
    public function widgetDefinitionsMap(): array
    {
        return app(ThemeWidgetRegistry::class)->all();
    }

    /**
     * @return array<string, array<int, array<string, mixed>>>
     */
    #[Computed]
    public function widgetsByCategory(): array
    {
        $grouped = [];
        foreach ($this->widgetDefinitionsMap as $widget) {
            $grouped[$widget['category']][] = $widget;
        }

        return $grouped;
    }

    /**
     * @return array<string, mixed>|null
     */
    #[Computed]
    public function selectedDefinition(): ?array
    {
        if ($this->selectedIndex < 0 || ! isset($this->blocks[$this->selectedIndex]) || ! is_array($this->blocks[$this->selectedIndex])) {
            return null;
        }

        $type = (string) ($this->blocks[$this->selectedIndex]['type'] ?? '');
        if ($type === '') {
            return null;
        }

        if ($this->targetType === 'catalog') {
            return $this->catalogDefinitionsMap[$type] ?? null;
        }

        return $this->widgetDefinitionsMap[$type] ?? null;
    }

    /**
     * @return array<string, array{key: string, label: string, description: string, badge: string}>
     */
    #[Computed]
    public function zonesList(): array
    {
        return app(ThemeWidgetRegistry::class)->zones();
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    #[Computed]
    public function starterTemplates(): array
    {
        return app(ThemeWidgetRegistry::class)->getStarterTemplates();
    }

    /**
     * @return array<string, array<int, array<string, mixed>>>
     */
    #[Computed]
    public function variableCatalog(): array
    {
        return app(ThemeVariableInterpolator::class)->getCatalog();
    }

    /**
     * @return array<int, string>
     */
    #[Computed]
    public function themeImages(): array
    {
        return app(ThemeBuilderCompilerService::class)->listThemeImages($this->selectedTheme);
    }

    private function loadThemes(): void
    {
        $themesPath = resource_path('views/themes');
        File::ensureDirectoryExists($themesPath);

        $dirs = array_map(fn (string $dir): string => basename($dir), File::directories($themesPath));
        sort($dirs);

        if (! in_array('default', $dirs, true)) {
            array_unshift($dirs, 'default');
        }

        $this->themes = $dirs;
    }

    private function loadVariants(): void
    {
        $this->variants = [];
        if ($this->selectedTheme === '' || $this->selectedTheme === 'default') {
            return;
        }

        $themePath = resource_path("views/themes/{$this->selectedTheme}");
        if (! File::isDirectory($themePath)) {
            return;
        }

        $exclude = ['livewire', 'components', 'layouts', 'vendor', 'emails', 'auth', 'errors', 'pages', 'partials', 'builder'];
        foreach (File::directories($themePath) as $dir) {
            $name = basename($dir);
            if (! in_array($name, $exclude, true)) {
                $this->variants[] = $name;
            }
        }
        sort($this->variants);
    }

    private function loadPagesList(): void
    {
        $this->pagesList = [];
        $themeService = app(ThemeService::class);
        $compiler = app(ThemeBuilderCompilerService::class);

        if ($this->selectedTheme === 'default') {
            $pagesDir = resource_path('views/pages');
        } else {
            $prefix = $themeService->resolvePrefix($this->selectedTheme, $this->selectedVariant);
            $pagesDir = resource_path("views/themes/{$prefix}/pages");
        }

        if (! File::isDirectory($pagesDir)) {
            return;
        }

        foreach (File::files($pagesDir) as $file) {
            if (! Str::endsWith($file->getFilename(), '.blade.php')) {
                continue;
            }

            $slug = Str::before($file->getFilename(), '.blade.php');
            $this->pagesList[] = [
                'slug' => $slug,
                'title' => Str::headline($slug),
                'is_managed' => $compiler->isPageManagedByBuilder($this->selectedTheme, $this->selectedVariant, $slug),
                'exists' => true,
            ];
        }

        usort($this->pagesList, fn (array $a, array $b): int => strcmp($a['slug'], $b['slug']));
    }

    private function ensureSelectedSlugInPagesList(): void
    {
        if ($this->selectedSlug === '') {
            return;
        }

        foreach ($this->pagesList as $page) {
            if ($page['slug'] === $this->selectedSlug) {
                return;
            }
        }

        $this->pagesList[] = [
            'slug' => $this->selectedSlug,
            'title' => Str::headline($this->selectedSlug),
            'is_managed' => false,
            'exists' => false,
        ];

        usort($this->pagesList, fn (array $a, array $b): int => strcmp($a['slug'], $b['slug']));
    }

    private function draftSessionKey(): string
    {
        $variant = $this->selectedVariant !== '' ? $this->selectedVariant : 'root';
        $targetKey = match ($this->targetType) {
            'zone' => $this->selectedZone,
            'catalog' => $this->selectedComponent,
            default => $this->selectedSlug,
        };

        return "oago_studio_draft.{$this->selectedTheme}.{$variant}.{$this->targetType}.{$targetKey}";
    }

    private function saveDraftToSession(): void
    {
        $targetKey = match ($this->targetType) {
            'zone' => $this->selectedZone,
            'catalog' => $this->selectedComponent,
            default => $this->selectedSlug,
        };
        if ($this->selectedTheme === '' || $targetKey === '') {
            return;
        }

        session()->put($this->draftSessionKey(), [
            'title' => $this->pageTitle,
            'blocks' => $this->blocks,
            'updated_at' => now()->toIso8601String(),
        ]);

        $this->hasUnsavedDraft = true;
    }

    private function sanitizeBlocks(): void
    {
        $compiler = app(ThemeBuilderCompilerService::class);

        $this->blocks = $this->targetType === 'catalog'
            ? $compiler->normalizeCatalogBlocks($this->selectedComponent, $this->blocks)
            : $compiler->normalizeBlocks($this->blocks);

        if ($this->blocks === []) {
            $this->selectedIndex = -1;
        } elseif ($this->selectedIndex < 0 || $this->selectedIndex >= count($this->blocks)) {
            $this->selectedIndex = 0;
        }
    }

    private function syncActiveBlockState(): void
    {
        unset(
            $this->previewBlocks,
            $this->compiledBladePreview,
            $this->selectedDefinition,
            $this->catalogDefinitionsMap,
            $this->catalogPresets,
            $this->catalogMockData
        );

        if ($this->selectedIndex < 0 || ! isset($this->blocks[$this->selectedIndex]) || ! is_array($this->blocks[$this->selectedIndex])) {
            $this->activeEnabled = true;
            $this->activeProps = [];

            return;
        }

        $block = $this->blocks[$this->selectedIndex];
        $this->activeEnabled = (bool) ($block['enabled'] ?? true);
        $this->activeProps = is_array($block['props'] ?? null) ? $block['props'] : [];
    }
};
?>

<div class="space-y-4">
    {{-- Studio Top Bar --}}
    <div class="bg-base-100 border border-base-300 rounded-2xl p-3 sm:p-4 shadow-xs flex flex-col gap-3">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <a href="/manage-themes" class="btn btn-ghost btn-sm gap-1">
                    <x-icon name="o-arrow-left" class="w-4 h-4" />
                    Temas
                </a>
                <div class="border-l border-base-300 pl-3">
                    <div class="flex items-center gap-2">
                        <h1 class="text-lg font-black tracking-tight">Studio</h1>
                        <span class="badge badge-primary badge-xs font-semibold">Compilador Blade AOT</span>
                    </div>
                    <p class="text-xs opacity-65">Editor visual de páginas estáticas, zonas del tema y catálogo con cero sobrecarga en runtime</p>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                @if ($targetType !== 'catalog')
                    <x-button
                        wire:click="$set('showTemplatesModal', true)"
                        icon="o-rectangle-stack"
                        class="btn-outline btn-sm"
                        label="Plantillas"
                    />
                    <x-button
                        wire:click="$set('showVariablesDrawer', true)"
                        icon="o-variable"
                        class="btn-outline btn-sm"
                        label="Variables"
                    />
                @endif
                @if ($hasUnsavedDraft)
                    <x-dropdown right>
                        <x-slot:trigger>
                            <x-button icon="o-trash" class="btn-ghost btn-sm text-warning" label="Descartar borrador" />
                        </x-slot:trigger>
                        <x-menu-item title="¿Descartar el borrador?" icon="o-question-mark-circle" />
                        <x-menu-separator />
                        <x-menu-item
                            title="Sí, volver a la versión guardada"
                            icon="o-trash"
                            wire:click="discardDraft"
                            spinner="discardDraft"
                        />
                        <x-menu-item title="Cancelar" icon="o-x-mark" />
                    </x-dropdown>
                @endif
                @if ($targetType === 'page' && $hasBackup)
                    <x-dropdown right>
                        <x-slot:trigger>
                            <x-button
                                icon="o-arrow-uturn-left"
                                class="btn-warning btn-outline btn-sm"
                                label="Restaurar Nativa"
                            />
                        </x-slot:trigger>
                        <x-menu-item title="¿Restaurar vista nativa de '/page/{{ $selectedSlug }}'?" icon="o-question-mark-circle" />
                        <x-menu-separator />
                        <x-menu-item
                            title="Sí, restaurar (descarta diseño Studio)"
                            icon="o-arrow-uturn-left"
                            wire:click="restoreNativePage"
                            spinner="restoreNativePage"
                        />
                        <x-menu-item title="Cancelar" icon="o-x-mark" />
                    </x-dropdown>
                @elseif ($targetType === 'catalog' && ($hasBackup || ($isManaged && $selectedTheme !== 'default')))
                    <x-dropdown right>
                        <x-slot:trigger>
                            <x-button
                                icon="o-arrow-uturn-left"
                                class="btn-warning btn-outline btn-sm"
                                :label="$hasBackup ? 'Restaurar Nativa' : 'Volver a Base'"
                            />
                        </x-slot:trigger>
                        <x-menu-item
                            :title="$hasBackup ? '¿Restaurar la vista Blade nativa?' : '¿Quitar la vista de Studio y volver a base?'"
                            icon="o-question-mark-circle"
                        />
                        <x-menu-separator />
                        <x-menu-item
                            :title="$hasBackup ? 'Sí, restaurar original' : 'Sí, volver al tema base'"
                            icon="o-arrow-uturn-left"
                            wire:click="restoreCatalogComponent"
                            spinner="restoreCatalogComponent"
                        />
                        <x-menu-item title="Cancelar" icon="o-x-mark" />
                    </x-dropdown>
                @endif
                @if ($targetType === 'page' && $selectedSlug !== '')
                    <a
                        href="{{ route('theme.page', ['slug' => $selectedSlug]) }}"
                        target="_blank"
                        class="btn btn-ghost btn-sm gap-1"
                        title="Abrir página pública en nueva pestaña"
                    >
                        <x-icon name="o-arrow-top-right-on-square" class="w-4 h-4" />
                        Ver /page/{{ $selectedSlug }}
                    </a>
                @endif
                <span
                    wire:loading.flex
                    wire:target="selectBlock,addBlock,updatedSelectedSlug,updatedSelectedZone,updatedSelectedComponent,updatedSelectedTheme,updatedSelectedVariant,updatedTargetType,selectCatalogComponent,applyCatalogPreset,restoreNativePage,restoreCatalogComponent,catalogPreviewState,saveAndCompile"
                    class="hidden items-center gap-1.5 text-xs font-semibold opacity-70"
                >
                    <span class="loading loading-spinner loading-xs"></span>
                    Aplicando…
                </span>
                <x-button
                    wire:click="saveAndCompile"
                    icon="o-bolt"
                    class="btn-primary btn-sm font-bold shadow-sm"
                    spinner="saveAndCompile"
                    label="Guardar y Compilar"
                />
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-12 gap-3 pt-2 border-t border-base-200 items-end">
            <div class="grid grid-cols-2 gap-3 md:col-span-4 items-end">
                <div>
                    <x-select
                        label="Tema"
                        wire:model.live="selectedTheme"
                        :options="collect($themes)->map(fn (string $t) => ['id' => $t, 'name' => $t])->values()->all()"
                        class="select-sm"
                    />
                </div>

                <div>
                    <x-select
                        label="Variante"
                        wire:model.live="selectedVariant"
                        :options="collect($variants)->map(fn (string $v) => ['id' => $v, 'name' => $v])->values()->all()"
                        placeholder="Raíz del tema"
                        class="select-sm"
                        :disabled="$selectedTheme === 'default' || count($variants) === 0"
                    />
                </div>
            </div>

            <div class="oago-group-fill oago-segmented md:col-span-3 md:border-l md:border-base-300 md:pl-3">
                <x-group
                    label="Destino"
                    wire:model.live="targetType"
                    :options="[
                        ['id' => 'page', 'name' => 'Páginas'],
                        ['id' => 'zone', 'name' => 'Zonas'],
                        ['id' => 'catalog', 'name' => 'Catálogo'],
                    ]"
                    class="flex-1 btn-sm btn-outline"
                />
            </div>

            @if ($targetType === 'page')
                <div class="md:col-span-3 flex items-end gap-1.5">
                    <div class="flex-1">
                        <x-select
                            label="Página Estática (/page/...)"
                            wire:model.live="selectedSlug"
                            :options="collect($pagesList)->map(fn (array $p) => ['id' => $p['slug'], 'name' => $p['slug'] . ($p['is_managed'] ? ' (Studio)' : (($p['exists'] ?? true) ? ' (Nativa)' : ' (Nueva)'))])->values()->all()"
                            placeholder="Seleccionar página..."
                            class="select-sm"
                        />
                    </div>
                    <x-button
                        wire:click="$set('showNewPageModal', true)"
                        icon="o-plus"
                        class="btn-sm btn-outline"
                        tooltip="Crear nueva página"
                    />
                </div>
                <div class="md:col-span-2">
                    <x-input
                        label="Título <title> de la página"
                        wire:model.live.debounce.300ms="pageTitle"
                        :placeholder="'Ej: Sobre ' . '{{ company.name }}'"
                        class="input-sm"
                    />
                </div>
            @elseif ($targetType === 'zone')
                <div class="md:col-span-5">
                    <x-select
                        label="Zona Editable del Tema"
                        wire:model.live="selectedZone"
                        :options="collect($this->zonesList)->map(fn (array $z) => ['id' => $z['key'], 'name' => $z['label'] . ' — ' . $z['badge']])->values()->all()"
                        class="select-sm"
                    />
                </div>
            @else
                <div class="md:col-span-5">
                    <x-select
                        label="Componente de Catálogo (Card / Vista Única)"
                        wire:model.live="selectedComponent"
                        :options="collect($this->catalogComponents)->map(fn (array $c) => ['id' => $c['key'], 'name' => $c['label']])->values()->all()"
                        class="select-sm"
                    />
                </div>
            @endif
        </div>
    </div>

    {{-- 2-Column Studio Workspace --}}
    <div class="grid grid-cols-1 lg:grid-cols-[minmax(18rem,20%)_1fr] gap-4 items-start">
        {{-- Left Column: Layers + Widget Catalog --}}
        <div class="space-y-4">
            <x-card title="Capas ({{ count($blocks) }})" class="shadow-xs border border-base-300" padding="p-3">
                @if (count($blocks) === 0)
                    <div class="text-xs text-center py-6 opacity-60">
                        Sin bloques todavía.<br>Agrega widgets desde el catálogo inferior o elige una plantilla.
                    </div>
                @else
                    @php $defsMap = $targetType === 'catalog' ? $this->catalogDefinitionsMap : $this->widgetDefinitionsMap; @endphp
                    <div class="space-y-1.5 max-h-[340px] overflow-y-auto pr-1">
                        @foreach ($blocks as $idx => $block)
                            @continue(! is_array($block) || empty($block['type']) || ! is_string($block['type']))
                            @php
                                $blockType = $block['type'];
                                $blockId = (string) ($block['id'] ?? 'blk_' . $idx);
                                $def = $defsMap[$blockType] ?? null;
                                $isSelected = $selectedIndex === $idx;
                                $isFixed = $targetType === 'catalog' && (bool) ($def['fixed'] ?? false);
                                $isPinned = $targetType === 'catalog' && (bool) ($def['pinned'] ?? false);
                            @endphp
                            <div
                                wire:key="layer-{{ $blockId }}"
                                class="rounded-xl border p-1.5 transition flex items-center justify-between gap-1.5 cursor-pointer {{ $isSelected ? 'border-primary bg-primary/10' : 'border-base-300 bg-base-100 hover:bg-base-200/60' }}"
                                wire:click="selectBlock({{ $idx }})"
                            >
                                <div class="min-w-0 flex items-center gap-2 flex-1">
                                    <span class="badge badge-xs {{ $isFixed ? 'badge-primary' : (($block['enabled'] ?? true) ? 'badge-success' : 'badge-ghost') }}">
                                        {{ $isFixed ? '⚙' : ($idx + 1) }}
                                    </span>
                                    <div class="truncate">
                                        <div class="text-xs font-bold truncate {{ ! ($block['enabled'] ?? true) ? 'line-through opacity-50' : '' }}">
                                            {{ $def['name'] ?? $blockType }}
                                        </div>
                                        <div class="text-[10px] opacity-60 truncate">
                                            {{ $targetType === 'catalog' ? Str::limit((string) ($def['description'] ?? $blockType), 28) : Str::limit((string) ($block['props']['title'] ?? $block['props']['message'] ?? $blockType), 26) }}
                                        </div>
                                    </div>
                                </div>

                                <div class="flex items-center gap-0 shrink-0" wire:click.stop>
                                    @if ($targetType !== 'catalog' || (! $isFixed && ! $isPinned))
                                        <button type="button" wire:click="moveBlockUp({{ $idx }})" class="btn btn-ghost btn-xs px-0.5" title="Subir">↑</button>
                                        <button type="button" wire:click="moveBlockDown({{ $idx }})" class="btn btn-ghost btn-xs px-0.5" title="Bajar">↓</button>
                                    @endif
                                    @if (! $isFixed)
                                        <button type="button" wire:click="toggleBlock({{ $idx }})" class="btn btn-ghost btn-xs px-0.5" title="Mostrar / Ocultar">
                                            {{ ($block['enabled'] ?? true) ? '👁' : '🚫' }}
                                        </button>
                                    @else
                                        <span class="text-[10px] font-semibold uppercase tracking-wide opacity-55 px-1">Global</span>
                                    @endif
                                    @if ($targetType !== 'catalog')
                                        <button type="button" wire:click="duplicateBlock({{ $idx }})" class="btn btn-ghost btn-xs px-0.5" title="Duplicar">⧉</button>
                                        <button type="button" wire:click="removeBlock({{ $idx }})" class="btn btn-ghost btn-xs px-0.5 text-error" title="Eliminar">✕</button>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-card>

            @if ($targetType === 'catalog')
                <x-card title="Presets de Diseño Rápido" class="shadow-xs border border-base-300" padding="p-3">
                    <div class="space-y-2.5">
                        @foreach ($this->catalogPresets as $presetKey => $preset)
                            <div class="p-2.5 rounded-xl border border-base-300 bg-base-100 hover:border-primary/60 transition space-y-2">
                                <div>
                                    <div class="text-xs font-bold">{{ $preset['name'] }}</div>
                                    <p class="text-[11px] opacity-65 leading-snug mt-0.5">{{ $preset['description'] }}</p>
                                </div>
                                <x-dropdown class="w-full">
                                    <x-slot:trigger>
                                        <x-button
                                            icon="o-sparkles"
                                            class="btn-xs btn-outline btn-primary w-full"
                                            label="Aplicar Preset"
                                        />
                                    </x-slot:trigger>
                                    <x-menu-item title="¿Aplicar '{{ $preset['name'] }}'?" icon="o-question-mark-circle" />
                                    <x-menu-separator />
                                    <x-menu-item
                                        title="Sí, reemplazar diseño del borrador"
                                        icon="o-sparkles"
                                        wire:click="applyCatalogPreset('{{ $presetKey }}')"
                                        spinner="applyCatalogPreset"
                                    />
                                    <x-menu-item title="Cancelar" icon="o-x-mark" />
                                </x-dropdown>
                            </div>
                        @endforeach
                    </div>
                </x-card>
            @else
                <x-card title="Catálogo de Widgets" class="shadow-xs border border-base-300" padding="p-3">
                    <div class="space-y-3 max-h-[26rem] overflow-y-auto pr-1">
                        @foreach ($this->widgetsByCategory as $category => $widgets)
                            <div>
                                <div class="text-[11px] font-bold uppercase tracking-wider opacity-60 mb-1.5">{{ $category }}</div>
                                <div class="grid grid-cols-2 gap-2">
                                    @foreach ($widgets as $widget)
                                        @php
                                            $tooltipPlacement = $loop->index % 2 === 0 ? 'sm:tooltip-right' : 'sm:tooltip-left';
                                        @endphp
                                        <div
                                            class="tooltip tooltip-top {{ $tooltipPlacement }} w-full before:z-50 before:max-w-[13rem] before:text-xs"
                                            data-tip="{{ $widget['description'] }}"
                                        >
                                            <button
                                                type="button"
                                                wire:click="addBlock('{{ $widget['type'] }}')"
                                                class="w-full h-full min-h-[76px] p-2 rounded-xl border border-base-300 bg-base-100 hover:border-primary hover:bg-primary/5 transition flex flex-col items-center justify-center text-center gap-1.5 cursor-pointer"
                                            >
                                                <x-icon name="{{ $widget['icon'] }}" class="w-5 h-5 text-primary shrink-0" />
                                                <span class="text-[11px] font-semibold leading-tight line-clamp-2">
                                                    {{ $widget['name'] }}
                                                </span>
                                            </button>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </x-card>
            @endif
        </div>

        {{-- Center Column: Responsive Preview / Compiled Code --}}
        <div class="min-w-0 space-y-3">
            <div class="bg-base-100 border border-base-300 rounded-2xl p-2.5 flex flex-wrap items-center justify-between gap-2 shadow-xs">
                <div class="oago-segmented">
                    <x-group
                        wire:model.live="canvasTab"
                        :options="[
                            ['id' => 'visual', 'name' => 'Vista Previa Viva'],
                            ['id' => 'code', 'name' => 'Código Blade Compilado'],
                        ]"
                        class="btn-xs btn-outline"
                    />
                </div>

                @if ($canvasTab === 'visual')
                    <div class="flex flex-wrap items-center gap-2">
                        <div class="oago-segmented">
                            <x-group
                                wire:model.live="viewport"
                                :options="[
                                    ['id' => 'desktop', 'name' => 'Escritorio'],
                                    ['id' => 'tablet', 'name' => 'Tablet'],
                                    ['id' => 'mobile', 'name' => 'Móvil'],
                                ]"
                                class="btn-xs btn-outline"
                            />
                        </div>

                        @if ($targetType === 'catalog')
                            <div class="oago-segmented border-l border-base-300 pl-2">
                                <x-group
                                    wire:model.live="catalogPreviewState"
                                    :options="[
                                        ['id' => 'offer', 'name' => 'Con Oferta'],
                                        ['id' => 'normal', 'name' => 'Normal'],
                                        ['id' => 'guest', 'name' => 'Invitado'],
                                        ['id' => 'out_of_stock', 'name' => 'Agotado'],
                                    ]"
                                    class="btn-xs btn-outline"
                                />
                            </div>
                        @endif
                    </div>
                @endif

                <div class="flex items-center gap-2">
                    <span class="text-[11px] opacity-65 font-mono">
                        @if ($targetType === 'page')
                            /page/{{ $selectedSlug }}
                        @elseif ($targetType === 'catalog')
                            catalog:{{ $selectedComponent }}
                        @else
                            zone:{{ $selectedZone }}
                        @endif
                    </span>

                    @if ($selectedIndex >= 0 && isset($blocks[$selectedIndex]['type']))
                        <x-button
                            wire:click="$set('showInspectorDrawer', true)"
                            icon="o-adjustments-horizontal"
                            class="btn-xs btn-primary btn-outline"
                            label="Inspector"
                        />
                    @endif
                </div>
            </div>

            @if ($targetType === 'page' && $hasNativeFile && ! $isManaged && count($blocks) === 0)
                <div class="alert alert-info shadow-xs rounded-2xl">
                    <x-icon name="o-shield-check" class="w-6 h-6 shrink-0" />
                    <div class="flex-1">
                        <h3 class="font-bold text-sm">Página Blade Nativa Protegida</h3>
                        <p class="text-xs opacity-90 mt-0.5">
                            <code>/page/{{ $selectedSlug }}</code> utiliza actualmente un archivo Blade personalizado escrito a mano. Al guardar desde Studio se creará automáticamente una copia de respaldo <code>.bak</code> para que puedas revertir en cualquier momento.
                        </p>
                        <div class="mt-3 flex flex-wrap gap-2">
                            <x-button wire:click="importNativeAsBlock" class="btn-sm btn-primary" icon="o-arrow-down-on-square" label="Importar HTML actual como bloque" />
                            <x-button wire:click="$set('showTemplatesModal', true)" class="btn-sm btn-outline" icon="o-sparkles" label="Reemplazar desde plantilla" />
                        </div>
                    </div>
                </div>
            @elseif ($targetType === 'catalog' && $selectedTheme === 'default')
                <div class="alert alert-warning shadow-xs rounded-2xl">
                    <x-icon name="o-exclamation-triangle" class="w-6 h-6 shrink-0" />
                    <div class="flex-1">
                        <h3 class="font-bold text-sm">Tema Base Protegido</h3>
                        <p class="text-xs opacity-90 mt-0.5">
                            Los componentes de catálogo reemplazan un archivo Blade existente, así que no se pueden compilar sobre el tema <strong>default</strong>: eso sobrescribiría las vistas del core para todos los temas. Selecciona o crea un tema para publicar este diseño.
                        </p>
                    </div>
                </div>
            @elseif ($targetType === 'catalog' && $hasNativeFile && ! $isManaged)
                <div class="alert alert-info shadow-xs rounded-2xl">
                    <x-icon name="o-shield-check" class="w-6 h-6 shrink-0" />
                    <div class="flex-1">
                        <h3 class="font-bold text-sm">Vista Manual Existente Protegida</h3>
                        <p class="text-xs opacity-90 mt-0.5">
                            Este componente actualmente utiliza un archivo Blade personalizado escrito a mano en el tema. Al guardar desde Studio se resguardará una copia automática <code>.bak.blade.php</code> para poder restaurar la versión original cuando quieras.
                        </p>
                    </div>
                </div>
            @endif

            @if ($canvasTab === 'visual')
                @php
                    $frameWidthClass = match ($viewport) {
                        'mobile' => 'max-w-[390px]',
                        'tablet' => 'max-w-[768px]',
                        default => 'w-full',
                    };
                @endphp
                <div class="relative bg-slate-200/80 border border-base-300 rounded-2xl p-3 sm:p-4 min-h-[680px] overflow-x-auto">
                    <div
                        wire:loading.flex
                        wire:target="selectBlock,addBlock,moveBlockUp,moveBlockDown,toggleBlock,duplicateBlock,removeBlock,updatedSelectedSlug,updatedSelectedZone,updatedSelectedComponent,updatedSelectedTheme,updatedSelectedVariant,updatedTargetType,selectCatalogComponent,applyCatalogPreset,discardDraft,importNativeAsBlock,restoreNativePage,restoreCatalogComponent,catalogPreviewState,saveAndCompile"
                        class="hidden absolute inset-0 z-40 items-center justify-center gap-2 rounded-2xl bg-white/55 backdrop-blur-[1px] text-slate-600 text-xs font-semibold"
                    >
                        <span class="loading loading-spinner loading-md text-primary"></span>
                        Actualizando lienzo…
                    </div>
                    <div class="{{ $frameWidthClass }} mx-auto bg-white text-slate-900 rounded-xl shadow-lg overflow-hidden border border-slate-300 min-h-[620px]">
                        @if ($targetType === 'catalog')
                            @php
                                $mock = $this->catalogMockData;
                                $layerIndices = [];
                                foreach ($blocks as $layerIdx => $layerBlock) {
                                    if (is_array($layerBlock) && ! empty($layerBlock['type'])) {
                                        $layerIndices[$layerBlock['type']] = $layerIdx;
                                    }
                                }
                                $mockProduct = $mock['product'];
                            @endphp
                            @if ($selectedComponent === 'product_detail')
                                @php
                                    $mockMedia = $mockProduct->media ?? [];
                                    $mockPrimaryMedia = $mockMedia[0] ?? [
                                        'type' => 'image',
                                        'url' => $mockProduct->image_url,
                                        'thumb' => $mockProduct->image_url,
                                    ];
                                    $mockUserPrice = (float) ($mock['display_price'] ?? 0);
                                    $mockOfferPrice = (float) ($mock['display_offer'] ?? 0);
                                    $mockEffective = $mockOfferPrice > 0 ? $mockOfferPrice : $mockUserPrice;
                                    $mockUnitPrice = ($mockProduct->qtty_unit ?? 1) > 0
                                        ? $mockEffective / $mockProduct->qtty_unit
                                        : $mockEffective;
                                    $mockCanBuy = ! ($mock['isGuest'] ?? false) && ! ($mock['isOutOfStock'] ?? false) && $mockEffective > 0;
                                @endphp
                                <div class="p-3 sm:p-5 bg-slate-50 min-h-[620px] space-y-3">
                                    <div class="flex flex-wrap items-center justify-between gap-2 pb-2 border-b border-slate-200 text-xs">
                                        <span class="text-slate-500">Haz clic en cualquier sección para editar colores, orden o textos</span>
                                        <button type="button" wire:click="selectBlock(0)" class="btn btn-xs btn-outline btn-primary">
                                            ⚙ Diseño General y Colores
                                        </button>
                                    </div>
                                    <x-theme-catalog.product-detail
                                        :blocks="$blocks"
                                        :preview="true"
                                        :layer-indices="$layerIndices"
                                        :product="$mockProduct"
                                        :media="$mockMedia"
                                        :primary-media="$mockPrimaryMedia"
                                        :tags="$mockProduct->commercial_tags_array ?? $mockProduct->tags_array ?? []"
                                        :user-price="$mockUserPrice"
                                        :offer-price="$mockOfferPrice"
                                        :unit-price="$mockUnitPrice"
                                        :stock-badge="$mock['stockBadge']"
                                        :can-buy="$mockCanBuy"
                                        :is-guest="$mock['isGuest']"
                                        :show-prices="$mock['showPrices']"
                                        :guest-message="$mock['guestMessage']"
                                        :is-out-of-stock="$mock['isOutOfStock']"
                                        :related-items="$mock['relatedProducts'] ?? []"
                                    />
                                </div>
                            @else
                                @php
                                    $cardLayoutMode = (string) ($blocks[0]['props']['layout'] ?? 'horizontal');
                                    $cardPreviewWidthClass = $cardLayoutMode === 'horizontal' ? 'max-w-xl' : 'max-w-sm';
                                @endphp
                                <div class="p-6 sm:p-10 bg-slate-100/80 min-h-[620px] flex flex-col items-center justify-center gap-4">
                                    <div class="flex flex-wrap items-center justify-center gap-2 text-xs">
                                        <span class="text-slate-500">Haz clic en cualquier bloque de la tarjeta para editarlo</span>
                                        <button type="button" wire:click="selectBlock(0)" class="btn btn-xs btn-outline btn-primary">
                                            ⚙ Diseño y Colores Globales
                                        </button>
                                    </div>
                                    <div class="w-full {{ $cardPreviewWidthClass }}">
                                        <x-theme-catalog.product-card
                                            :blocks="$blocks"
                                            :preview="true"
                                            :layer-indices="$layerIndices"
                                            :product="$mockProduct"
                                            :display-price="$mock['display_price']"
                                            :display-offer="$mock['display_offer']"
                                            :cart="$mock['cart']"
                                            :is-guest="$mock['isGuest']"
                                            :show-prices="$mock['showPrices']"
                                            :guest-message="$mock['guestMessage']"
                                            :is-out-of-stock="$mock['isOutOfStock']"
                                            :has-available-stock="$mock['hasAvailableStock']"
                                            :stock-badge="$mock['stockBadge']"
                                            :cart-payload="$mock['cartPayload']"
                                        />
                                    </div>
                                </div>
                            @endif
                        @else
                            @forelse ($this->previewBlocks as $idx => $previewBlock)
                                @continue(! is_array($previewBlock) || empty($previewBlock['type']) || ! is_string($previewBlock['type']))
                                @php
                                    $previewId = (string) ($previewBlock['id'] ?? 'blk_' . $idx);
                                    $previewType = $previewBlock['type'];
                                    $isActive = $selectedIndex === $idx;
                                    $isEnabled = (bool) ($previewBlock['enabled'] ?? true);
                                @endphp
                                <div
                                    wire:key="preview-{{ $previewId }}"
                                    wire:click="selectBlock({{ $idx }})"
                                    class="relative group cursor-pointer transition {{ $isActive ? 'ring-2 ring-blue-600 ring-inset' : 'hover:ring-1 hover:ring-blue-400 hover:ring-inset' }} {{ ! $isEnabled ? 'opacity-40 grayscale' : '' }}"
                                >
                                    <div class="absolute top-2 right-2 z-30 opacity-0 group-hover:opacity-100 {{ $isActive ? 'opacity-100' : '' }} transition pointer-events-none">
                                        <span class="px-2 py-0.5 rounded bg-blue-600 text-white text-[10px] font-bold shadow">
                                            #{{ $idx + 1 }} {{ $previewType }}{{ ! $isEnabled ? ' (oculto)' : '' }}
                                        </span>
                                    </div>

                                    <x-dynamic-component
                                        :component="'theme-widgets.' . $previewType"
                                        :props="$previewBlock['props'] ?? []"
                                        :preview="true"
                                    />
                                </div>
                            @empty
                                <div class="flex flex-col items-center justify-center py-28 px-6 text-center text-slate-500">
                                    <x-icon name="o-squares-plus" class="w-12 h-12 mb-3 opacity-40" />
                                    <p class="font-bold text-slate-700">Lienzo vacío</p>
                                    <p class="text-xs mt-1 max-w-md">
                                        Selecciona cualquier widget del panel izquierdo o abre <strong>Plantillas</strong> para poblar tu diseño al instante.
                                    </p>
                                </div>
                            @endforelse
                        @endif
                    </div>
                </div>
            @else
                <div class="bg-slate-950 text-slate-100 rounded-2xl border border-slate-800 p-4 shadow-md">
                    <div class="flex items-center justify-between mb-3 pb-2 border-b border-slate-800">
                        <span class="text-xs font-mono text-emerald-400">Código .blade.php AOT generado automáticamente</span>
                        <span class="text-[11px] text-slate-400">0 consultas JSON / 0 regex en producción</span>
                    </div>
                    <pre class="text-xs font-mono leading-relaxed overflow-x-auto max-h-[680px] whitespace-pre-wrap">{{ $this->compiledBladePreview }}</pre>
                </div>
            @endif
        </div>
    </div>

    {{-- Drawer: Block Inspector --}}
    <x-drawer
        wire:model="showInspectorDrawer"
        title="Inspector de Bloque"
        right
        with-close-button
        without-trap-focus
        class="w-11/12 max-w-lg"
    >
        @if ($showInspectorDrawer)
        @if ($selectedIndex < 0 || ! isset($blocks[$selectedIndex]) || ! is_array($blocks[$selectedIndex]) || empty($blocks[$selectedIndex]['type']) || ! $this->selectedDefinition)
            <div class="text-xs text-center py-10 opacity-60">
                Haz clic en un bloque del lienzo o de la lista de capas para editar sus propiedades.
            </div>
        @else
            @php
                $activeBlock = $blocks[$selectedIndex];
                $activeBlockId = (string) ($activeBlock['id'] ?? 'blk_' . $selectedIndex);
                $definition = $this->selectedDefinition;
            @endphp
            <div wire:key="inspector-{{ $activeBlockId }}" class="space-y-4 pb-6">
                        <div class="p-2.5 rounded-xl bg-base-200 flex items-center justify-between gap-2">
                            <div>
                                <div class="text-xs font-bold">{{ $definition['name'] }}</div>
                                <div class="text-[10px] font-mono opacity-60">{{ $activeBlockId }}</div>
                            </div>
                            @if (! empty($definition['fixed']))
                                <span class="badge badge-sm badge-primary badge-outline">Global</span>
                            @else
                                <x-toggle
                                    :id="'toggle-visible-' . $activeBlockId"
                                    label="Visible"
                                    wire:model.live="activeEnabled"
                                    class="toggle-sm"
                                />
                            @endif
                        </div>

                        @foreach ($definition['fields'] as $field)
                            @php
                                $fieldKey = (string) $field['key'];
                                $fieldType = (string) ($field['type'] ?? 'text');
                                $fieldLabel = (string) ($field['label'] ?? $fieldKey);
                                $supportsVars = (bool) ($field['supports_vars'] ?? false);
                            @endphp

                            <div wire:key="field-{{ $activeBlockId }}-{{ $fieldKey }}" class="space-y-1">
                                @if ($fieldType === 'text')
                                    <x-input
                                        label="{{ $fieldLabel }}"
                                        wire:model.blur="activeProps.{{ $fieldKey }}"
                                        class="input-sm"
                                    />
                                @elseif ($fieldType === 'textarea')
                                    <x-textarea
                                        label="{{ $fieldLabel }}"
                                        wire:model.blur="activeProps.{{ $fieldKey }}"
                                        rows="3"
                                        class="textarea-sm"
                                    />
                                @elseif ($fieldType === 'color')
                                    <label class="label py-0.5"><span class="label-text text-xs font-semibold">{{ $fieldLabel }}</span></label>
                                    <div class="flex items-center gap-2">
                                        <input
                                            type="color"
                                            wire:model.live.debounce.200ms="activeProps.{{ $fieldKey }}"
                                            class="w-9 h-8 rounded cursor-pointer border border-base-300 bg-transparent p-0"
                                        />
                                        <x-input
                                            wire:model.blur="activeProps.{{ $fieldKey }}"
                                            class="input-sm flex-1 font-mono text-xs"
                                        />
                                    </div>
                                @elseif ($fieldType === 'select')
                                    <x-select
                                        label="{{ $fieldLabel }}"
                                        wire:model.live="activeProps.{{ $fieldKey }}"
                                        :options="$field['options'] ?? []"
                                        class="select-sm"
                                    />
                                @elseif ($fieldType === 'toggle')
                                    <x-toggle
                                        :id="'toggle-prop-' . $activeBlockId . '-' . $fieldKey"
                                        label="{{ $fieldLabel }}"
                                        wire:model.live="activeProps.{{ $fieldKey }}"
                                        class="toggle-sm"
                                    />
                                @elseif ($fieldType === 'image')
                                    <label class="label py-0.5"><span class="label-text text-xs font-semibold">{{ $fieldLabel }}</span></label>
                                    <div class="flex items-center gap-1.5">
                                        <x-input
                                            wire:model.blur="activeProps.{{ $fieldKey }}"
                                            :placeholder="'{{ theme.image(\'file.webp\') }}'"
                                            class="input-sm flex-1 font-mono text-xs"
                                        />
                                        <x-button
                                            wire:click="openMediaPicker('{{ $fieldKey }}')"
                                            icon="o-photo"
                                            class="btn-sm btn-outline"
                                            tooltip="Elegir o subir imagen"
                                        />
                                    </div>
                                @elseif ($fieldType === 'richtext')
                                    @php
                                        $tinyConfig = [
                                            'plugins' => 'autoresize',
                                            'statusbar' => false,
                                            'menubar' => false,
                                            'height' => 260,
                                            'toolbar' => 'undo redo | bold italic underline | bullist numlist | link | h2 h3 | removeformat',
                                        ];
                                    @endphp
                                    <div wire:key="richtext-{{ $activeBlockId }}-{{ $fieldKey }}">
                                        <x-editor
                                            :id="'studio-editor-' . $activeBlockId . '-' . $fieldKey"
                                            wire:model.live="activeProps.{{ $fieldKey }}"
                                            label="{{ $fieldLabel }}"
                                            gpl-license
                                            :config="$tinyConfig"
                                        />
                                    </div>
                                @elseif ($fieldType === 'code')
                                    <x-textarea
                                        label="{{ $fieldLabel }}"
                                        wire:model.live.debounce.300ms="activeProps.{{ $fieldKey }}"
                                        rows="9"
                                        class="font-mono text-xs"
                                    />
                                @elseif ($fieldType === 'repeater')
                                    @php
                                        $items = (array) ($activeProps[$fieldKey] ?? []);
                                    @endphp
                                    <div class="pt-2 border-t border-base-300 space-y-2">
                                        <div class="flex items-center justify-between">
                                            <span class="text-xs font-bold">{{ $fieldLabel }} ({{ count($items) }})</span>
                                            <x-button
                                                wire:click="addRepeaterItem('{{ $fieldKey }}')"
                                                icon="o-plus"
                                                class="btn-xs btn-primary btn-outline"
                                                label="Agregar"
                                            />
                                        </div>

                                        @foreach ($items as $itemIdx => $item)
                                            <div
                                                wire:key="rep-{{ $activeBlockId }}-{{ $fieldKey }}-{{ $itemIdx }}"
                                                class="rounded-xl border border-base-300 bg-base-200/40 p-2.5 space-y-2"
                                            >
                                                <div class="flex items-center justify-between">
                                                    <span class="text-[11px] font-bold opacity-70">Elemento #{{ $itemIdx + 1 }}</span>
                                                    <button
                                                        type="button"
                                                        wire:click="removeRepeaterItem('{{ $fieldKey }}', {{ $itemIdx }})"
                                                        class="btn btn-ghost btn-xs text-error px-1.5"
                                                    >
                                                        ✕
                                                    </button>
                                                </div>

                                                @foreach ((array) ($field['item_fields'] ?? []) as $subField)
                                                    @php
                                                        $subKey = (string) $subField['key'];
                                                        $subType = (string) ($subField['type'] ?? 'text');
                                                        $subLabel = (string) ($subField['label'] ?? $subKey);
                                                    @endphp
                                                    @if ($subType === 'select')
                                                        <x-select
                                                            label="{{ $subLabel }}"
                                                            wire:model.live="activeProps.{{ $fieldKey }}.{{ $itemIdx }}.{{ $subKey }}"
                                                            :options="$subField['options'] ?? []"
                                                            class="select-xs"
                                                        />
                                                    @elseif ($subType === 'textarea')
                                                        <x-textarea
                                                            label="{{ $subLabel }}"
                                                            wire:model.live.debounce.250ms="activeProps.{{ $fieldKey }}.{{ $itemIdx }}.{{ $subKey }}"
                                                            rows="2"
                                                            class="textarea-xs"
                                                        />
                                                    @elseif ($subType === 'image')
                                                        <div>
                                                            <label class="label py-0.5"><span class="label-text text-[11px]">{{ $subLabel }}</span></label>
                                                            <div class="flex items-center gap-1">
                                                                <x-input
                                                                    wire:model.live.debounce.250ms="activeProps.{{ $fieldKey }}.{{ $itemIdx }}.{{ $subKey }}"
                                                                    class="input-xs flex-1 font-mono text-[11px]"
                                                                />
                                                                <x-button
                                                                    wire:click="openMediaPicker('{{ $subKey }}', {{ $itemIdx }}, '{{ $fieldKey }}')"
                                                                    icon="o-photo"
                                                                    class="btn-xs btn-outline"
                                                                />
                                                            </div>
                                                        </div>
                                                    @else
                                                        <x-input
                                                            label="{{ $subLabel }}"
                                                            wire:model.live.debounce.250ms="activeProps.{{ $fieldKey }}.{{ $itemIdx }}.{{ $subKey }}"
                                                            class="input-xs"
                                                        />
                                                    @endif
                                                @endforeach
                                            </div>
                                        @endforeach
                                    </div>
                                @endif

                                @if ($supportsVars && in_array($fieldType, ['text', 'textarea', 'richtext', 'code'], true))
                                    <div class="flex flex-wrap gap-1 pt-0.5">
                                        <button type="button" wire:click="appendTokenToField('{{ $fieldKey }}', '@{{ company.name }}')" class="badge badge-ghost badge-xs hover:badge-primary cursor-pointer">+company.name</button>
                                        <button type="button" wire:click="appendTokenToField('{{ $fieldKey }}', '@{{ company.phone }}')" class="badge badge-ghost badge-xs hover:badge-primary cursor-pointer">+company.phone</button>
                                        <button type="button" wire:click="appendTokenToField('{{ $fieldKey }}', '@{{ user.name }}')" class="badge badge-ghost badge-xs hover:badge-primary cursor-pointer">+user.name</button>
                                        <button type="button" wire:click="appendTokenToField('{{ $fieldKey }}', '@{{ current_year }}')" class="badge badge-ghost badge-xs hover:badge-primary cursor-pointer">+current_year</button>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
        @endif
    </x-drawer>

    {{-- Modal: New Page --}}
    <x-modal wire:model="showNewPageModal" title="Crear Nueva Página Estática" box-class="max-w-md">
        <div class="space-y-3">
            <x-input
                label="Slug / URL de la página"
                wire:model="newPageSlug"
                placeholder="Ej: envios, garantias, sucursales"
                hint="Se publicará automáticamente en /page/{slug} y en el menú superior."
            />
        </div>
        <x-slot:actions>
            <x-button label="Cancelar" wire:click="$set('showNewPageModal', false)" class="btn-ghost" />
            <x-button label="Crear Página" wire:click="createPageFromModal" class="btn-primary" spinner />
        </x-slot:actions>
    </x-modal>

    {{-- Modal: Starter Templates --}}
    <x-modal wire:model="showTemplatesModal" title="Plantillas Predefinidas" box-class="max-w-2xl">
        @if ($showTemplatesModal)
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                @foreach ($this->starterTemplates as $template)
                    <div class="p-4 rounded-xl border border-base-300 hover:border-primary transition flex flex-col justify-between gap-3">
                        <div>
                            <h4 class="font-bold text-sm">{{ $template['name'] }}</h4>
                            <p class="text-xs opacity-70 mt-1">{{ $template['description'] }}</p>
                            <div class="mt-2 text-[11px] font-mono opacity-60">{{ count($template['blocks']) }} bloques preconfigurados</div>
                        </div>
                        <x-button
                            wire:click="applyStarterTemplate('{{ $template['key'] }}')"
                            class="btn-primary btn-sm w-full"
                            label="Usar Plantilla"
                        />
                    </div>
                @endforeach
            </div>
        @endif
    </x-modal>

    {{-- Modal: Theme Media Gallery & Uploader --}}
    <x-modal wire:model="showMediaModal" title="Galería de Imágenes del Tema ({{ $selectedTheme }})" box-class="max-w-3xl">
        @if ($showMediaModal)
            <div class="space-y-4">
                <div class="p-3 rounded-xl bg-base-200 flex flex-col sm:flex-row items-center gap-3">
                    <div class="flex-1 w-full">
                        <x-file wire:model="uploadedImage" label="Subir nueva imagen al tema" accept=".png,.jpg,.jpeg,.webp,.svg,.gif" />
                    </div>
                    <x-button wire:click="uploadImageToTheme" icon="o-arrow-up-tray" class="btn-primary btn-sm shrink-0" spinner label="Subir y Usar" />
                </div>

                @if (count($this->themeImages) > 0)
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 max-h-[340px] overflow-y-auto p-1">
                        @foreach ($this->themeImages as $imgRel)
                            <button
                                type="button"
                                wire:click="selectMediaImage('{{ $imgRel }}')"
                                class="group rounded-xl border border-base-300 hover:border-primary overflow-hidden bg-white text-left transition"
                            >
                                <div class="h-24 w-full bg-slate-100 flex items-center justify-center overflow-hidden p-1">
                                    <img src="{{ asset('themes/' . $selectedTheme . '/images/' . $imgRel) }}" alt="{{ $imgRel }}" class="max-h-full max-w-full object-contain group-hover:scale-105 transition" />
                                </div>
                                <div class="p-1.5 text-[10px] font-mono text-slate-700 truncate border-t border-slate-100">
                                    {{ $imgRel }}
                                </div>
                            </button>
                        @endforeach
                    </div>
                @else
                    <p class="text-center text-xs opacity-65 py-6">
                        Todavía no hay imágenes en <code>public/themes/{{ $selectedTheme }}/images/</code>. Sube la primera arriba.
                    </p>
                @endif
            </div>
        @endif
    </x-modal>

    {{-- Drawer: Variables Cheat Sheet --}}
    <x-drawer wire:model="showVariablesDrawer" title="Variables Dinámicas Disponibles" right with-close-button class="w-11/12 max-w-md">
        @if ($showVariablesDrawer)
            <p class="text-xs opacity-75 mb-4">
                Usa estos tokens dentro de cualquier texto, enlace o bloque HTML. En producción se compilan directamente a llamadas PHP seguras (no evalúan código arbitrario).
            </p>

            <div class="space-y-4">
                @foreach ($this->variableCatalog as $groupName => $variables)
                    <div>
                        <h4 class="text-xs font-bold uppercase tracking-wider text-primary mb-2">{{ $groupName }}</h4>
                        <div class="space-y-2">
                            @foreach ($variables as $var)
                                <div class="p-2.5 rounded-xl border border-base-300 bg-base-100 flex items-center justify-between gap-2">
                                    <div class="min-w-0">
                                        <code class="text-xs font-bold text-primary break-all">{{ $var['token'] }}</code>
                                        <div class="text-[11px] opacity-70 mt-0.5">{{ $var['label'] }}</div>
                                    </div>
                                    <button
                                        type="button"
                                        x-data="{ copied: false }"
                                        x-on:click="navigator.clipboard.writeText(@js($var['token'])); copied = true; setTimeout(() => copied = false, 1500)"
                                        class="btn btn-xs btn-outline shrink-0"
                                    >
                                        <span x-show="!copied">Copiar</span>
                                        <span x-show="copied" class="text-success">¡Listo!</span>
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </x-drawer>
</div>
