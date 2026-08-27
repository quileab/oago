<?php

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;
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
        $this->success("Tema '{$name}' creado correctamente.");
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
        <x-card title="Vistas del motor" class="shadow-sm">
            <div class="flex items-center justify-between mb-4">
                <div class="text-sm opacity-70">
                    Mostrando overrides para: <strong>{{ $this->selectedVariant ? "{$selectedTheme} / {$selectedVariant}" : $selectedTheme }}</strong>
                </div>
                <div class="w-1/2 md:w-1/3">
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
    @else
        <div class="text-center text-gray-500 py-10">
            <x-icon name="o-swatch" class="w-12 h-12 mx-auto mb-4 opacity-50" />
            <p>Selecciona un tema de la barra superior para gestionar sus vistas.</p>
        </div>
    @endif
</div>
