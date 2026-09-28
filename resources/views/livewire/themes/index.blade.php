<?php

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Livewire\Volt\Component;
use Mary\Traits\Toast;

new class extends Component {
    use Toast;

    public string $selectedTheme = '';
    public string $selectedVariant = '';
    public array $themes = [];
    public array $variants = [];
    
    public string $search = '';
    
    public string $newThemeName = '';
    public string $newVariantName = '';
    public string $newPageSlug = '';

    public function mount()
    {
        $this->selectedTheme = config('app.theme', 'default');
        $this->selectedVariant = config('app.theme_variant') ?: '';
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
        if (!File::exists($themesPath)) {
            File::makeDirectory($themesPath, 0755, true);
        }

        $directories = File::directories($themesPath);
        $this->themes = array_map(fn($dir) => basename($dir), $directories);
        
        if (!in_array('default', $this->themes)) {
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
        if (!File::exists($themePath)) {
            return;
        }

        $directories = File::directories($themePath);
        $exclude = ['livewire', 'components', 'layouts', 'vendor', 'emails', 'auth', 'errors'];
        
        foreach ($directories as $dir) {
            $name = basename($dir);
            if (!in_array($name, $exclude)) {
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
        $cssDir = resource_path("css/themes");
        if (!File::exists($cssDir)) {
            File::makeDirectory($cssDir, 0755, true);
        }
        
        $cssFile = resource_path("css/themes/{$name}.css");
        if (!File::exists($cssFile)) {
            $cssTemplate = <<<CSS
@import "tailwindcss";

@plugin "daisyui" {
    darktheme: "dark";
    themes: dark --default, light --preferslight;
}
@plugin "daisyui/theme" {
    default: true;
}

[data-theme="light"] {
    --color-primary: #002b6b;
    --color-secondary: #8a053c;
    --color-success: #3c8e26;
}
[data-theme="dark"] {
    --color-primary: #002b6b;
    --color-secondary: #8a053c;
    --color-success: #3c8e26;
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
        if (!File::exists($publicAssetsPath)) {
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

        $exclude = ['livewire', 'components', 'layouts', 'vendor', 'emails', 'auth', 'errors'];
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

    public function activateTheme()
    {
        if (empty($this->selectedTheme)) {
            $this->error('Selecciona un tema primero.');
            return;
        }

        $envPath = base_path('.env');
        if (!File::exists($envPath)) {
            $this->error('El archivo .env no existe.');
            return;
        }

        $env = File::get($envPath);

        // Update APP_THEME
        if (preg_match('/^APP_THEME=.*$/m', $env)) {
            $env = preg_replace('/^APP_THEME=.*$/m', 'APP_THEME=' . $this->selectedTheme, $env);
        } else {
            $env .= "\nAPP_THEME=" . $this->selectedTheme;
        }

        // Update APP_THEME_VARIANT
        if (preg_match('/^APP_THEME_VARIANT=.*$/m', $env)) {
            $env = preg_replace('/^APP_THEME_VARIANT=.*$/m', 'APP_THEME_VARIANT=' . $this->selectedVariant, $env);
        } else {
            $env .= "\nAPP_THEME_VARIANT=" . $this->selectedVariant;
        }

        File::put($envPath, $env);
        Artisan::call('config:clear');
        $this->success("Tema activado exitosamente. Se limpió la caché de config.");
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

        if (!File::exists($basePath)) {
            $this->error('El archivo base no existe.');
            return;
        }

        if (File::exists($themePath)) {
            $this->warning('El archivo ya está sobrescrito en este nivel.');
            return;
        }

        $directory = dirname($themePath);
        if (!File::exists($directory)) {
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

        if (!File::exists($themePath)) {
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

        $this->success("Vista revertida. Ahora usa el fallback.");
    }

    public function getViewsProperty()
    {
        if (empty($this->selectedTheme)) {
            return [];
        }

        $basePath = resource_path('views');
        $allFiles = File::allFiles($basePath);
        
        $viewsList = [];
        $prefix = $this->getCurrentOverridePrefix();
        
        foreach ($allFiles as $file) {
            $relativePath = $file->getRelativePathname();
            $relativePath = str_replace('\\', '/', $relativePath);
            
            // Ignore files that are inside the themes directory itself
            if (str_starts_with($relativePath, 'themes/')) {
                continue;
            }
            
            if ($this->search && stripos($relativePath, $this->search) === false) {
                continue;
            }

            $themePath = resource_path("views/themes/{$prefix}/{$relativePath}");
            $isOverridden = File::exists($themePath);

            $viewsList[] = [
                'file' => $relativePath,
                'overridden' => $isOverridden,
            ];
        }

        return $viewsList;
    }

    public function getThemePagesProperty()
    {
        if (empty($this->selectedTheme)) {
            return [];
        }

        $prefix = $this->getCurrentOverridePrefix();
        $pagesPath = resource_path("views/themes/{$prefix}/pages");

        if (!File::isDirectory($pagesPath)) {
            return [];
        }

        $pages = [];
        foreach (File::files($pagesPath) as $file) {
            if (Str::endsWith($file->getFilename(), '.blade.php')) {
                $slug = Str::before($file->getFilename(), '.blade.php');
                $pages[] = [
                    'slug' => $slug,
                    'file' => $file->getRelativePathname(),
                    'title' => Str::headline($slug),
                    'url' => route('theme.page', ['slug' => $slug])
                ];
            }
        }
        return $pages;
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

        if (!File::isDirectory($pagesPath)) {
            File::makeDirectory($pagesPath, 0755, true);
        }

        $filePath = "{$pagesPath}/{$slug}.blade.php";
        if (File::exists($filePath)) {
            $this->warning("La página '{$slug}' ya existe.");
            return;
        }

        $title = Str::headline($slug);
        
        $base64Template = 'PD9waHAKdXNlIExpdmV3aXJlXEF0dHJpYnV0ZXNcTGF5b3V0Owp1c2UgTGl2ZXdpcmVcQXR0cmlidXRlc1xUaXRsZTsKdXNlIExpdmV3aXJlXFZvbHRcQ29tcG9uZW50OwoKbmV3ICNbTGF5b3V0KCdjb21wb25lbnRzLmxheW91dHMuY2xlYW4nKV0gI1tUaXRsZSgne3t0aXRsZX19JyldIGNsYXNzIGV4dGVuZHMgQ29tcG9uZW50IHsKfTsgPz4KCjxkaXYgY2xhc3M9ImJnLXdoaXRlIG1pbi1oLXNjcmVlbiBkYXJrOmJnLWdyYXktOTUwIHB4LTQgcHktOCB0ZXh0LWdyYXktOTAwIGRhcms6dGV4dC1ncmF5LTEwMCI+CiAgICA8ZGl2IGNsYXNzPSJteC1hdXRvIG1heC13LTR4bCBzcGFjZS15LTYiPgogICAgICAgIDxoMSBjbGFzcz0idGV4dC0zeGwgZm9udC1ib2xkIj57e3RpdGxlfX08L2gxPgogICAgICAgIDxwPkNvbnRlbmlkbyBkZSBsYSBw4WdpbmEgdmEgYXF17S4uLjwvcD4KICAgIDwvZGl2Pgo8L2Rpdj4=';
        $template = str_replace('{{title}}', $title, base64_decode($base64Template));

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

        if (File::exists($filePath)) {
            File::delete($filePath);
            $this->success("Página '{$slug}' eliminada.");
        } else {
            $this->error("La página '{$slug}' no existe.");
        }
    }
}
?>

<div class="mx-1">
    <x-header title="Gestor de Temas" subtitle="Administra las vistas personalizadas para cada tema" separator>
        <x-slot:actions>
            <div class="flex items-center gap-2">
                <x-input wire:model="newThemeName" placeholder="Nuevo tema..." class="input-sm" />
                <x-button wire:click="createTheme" icon="o-plus" class="btn-primary btn-sm" tooltip="Crear tema" />
            </div>
            
            <div class="flex items-center gap-2 ml-4 border-l pl-4 border-base-300">
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
                <x-button wire:click="activateTheme" icon="o-check-circle" class="btn-success text-white w-full" spinner>
                    Activar en .env
                </x-button>
            </div>
        @endif
    </div>

    @if($selectedTheme)
        @if($selectedTheme === 'default')
            <div class="text-center text-gray-500 py-10">
                <x-icon name="o-shield-check" class="w-12 h-12 mx-auto mb-4 text-success opacity-80" />
                <h3 class="text-xl font-bold mb-2">Tema Base (Core)</h3>
                <p>El tema <strong>default</strong> carga directamente las vistas originales del sistema.</p>
                <p class="text-sm mt-2 opacity-75">No es posible crear overrides aquí. Si deseas hacer modificaciones personalizadas, crea un nuevo tema.</p>
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
            </div>

            <x-card title="Vistas del motor" class="shadow-sm">
                <div class="flex flex-col md:flex-row items-center justify-between mb-4 gap-4">
                    <div class="text-sm opacity-70 w-full md:w-auto text-center md:text-left">
                        Mostrando overrides para: <strong>{{ $this->selectedVariant ? "{$selectedTheme} / {$selectedVariant}" : $selectedTheme }}</strong>
                    </div>
                    <div class="w-full md:w-1/3">
                        <x-input wire:model.live.debounce.300ms="search" placeholder="Buscar componente o vista..." icon="o-magnifying-glass" clearable class="input-sm" />
                    </div>
                </div>

                <div class="overflow-x-auto max-h-[600px] border border-base-300 rounded-lg">
                    <table class="table table-zebra table-pin-rows table-sm w-full">
                        <thead>
                            <tr>
                                <th>Archivo / Componente</th>
                                <th class="text-center">Estado</th>
                                <th class="text-right">Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($this->views as $view)
                                <tr>
                                    <td class="font-mono text-sm">{{ $view['file'] }}</td>
                                    <td class="text-center">
                                        @if($view['overridden'])
                                            <x-badge value="Sobrescrito" class="badge-success badge-sm" />
                                        @else
                                            <x-badge value="Fallback (Motor)" class="badge-ghost badge-sm" />
                                        @endif
                                    </td>
                                    <td class="text-right">
                                        @if($view['overridden'])
                                            <x-button wire:click="revertView('{{ $view['file'] }}')" wire:confirm="¿Estás seguro de eliminar el archivo y volver al fallback?" icon="o-arrow-uturn-left" class="btn-error btn-xs" tooltip="Revertir a base" />
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
            
            <x-card title="Páginas Estáticas (/page)" class="shadow-sm mt-6">
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
                                    <td class="font-mono text-xs opacity-75">{{ $page['file'] }}</td>
                                    <td class="text-right">
                                        <x-button wire:click="deleteThemePage('{{ $page['slug'] }}')" wire:confirm="¿Estás seguro de eliminar la página '{{ $page['title'] }}'? Se borrará el archivo físico permanentemente." icon="o-trash" class="btn-error btn-xs btn-outline" tooltip="Eliminar página" />
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-gray-500">
                                        No se han creado páginas para este tema aún.
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
