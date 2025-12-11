<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ isset($title) ? $title . ' - ' . config('app.name') : config('app.name') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="{{ asset('js/tinymce/tinymce.min.js') }}"></script>
    @livewireStyles
</head>

<body class="min-h-screen font-sans antialiased bg-base-200/50 dark:bg-base-200">

    {{-- NAVBAR mobile only --}}
    <x-nav sticky class="lg:hidden">
        <x-slot:brand>
            <x-app-brand />
        </x-slot:brand>
        <x-slot:actions>
            <label for="main-drawer" class="lg:hidden me-3">
                <x-icon name="o-bars-3" class="cursor-pointer" />
            </label>
        </x-slot:actions>
    </x-nav>

    {{-- MAIN --}}
    <x-main full-width>
        {{-- SIDEBAR --}}
        <x-slot:sidebar drawer="main-drawer" collapsible class="bg-base-100 lg:bg-inherit">

            {{-- BRAND --}}
            <x-app-brand class="p-5 pt-3" />

            {{-- MENU --}}
            <x-menu activate-by-route>

                {{-- User --}}
                @if($user = auth()->user())
                    <x-list-item :item="$user" value="name" sub-value="email" class="-mx-2 !-mt-4 rounded bg-primary/10">
                        <x-slot:actions>
                            <x-button icon="o-power" class="btn-circle btn-ghost btn-xs text-warning" tooltip-left="SALIR"
                                no-wire-navigate link="/logout" />
                        </x-slot:actions>
                    </x-list-item>
                @endif

                @if($user->role->value == 'admin')
                    <x-menu-item title="Sitio Principal" icon="o-sparkles" link="/" no-wire-navigate />
                    <x-menu-item title="Dashboard" icon="o-chart-pie" link="/dashboard" class="text-info" />
                    <x-menu-sub title="Usuarios" icon="o-user">
                        <x-menu-item title="Registrados" icon="s-users" link="/users" />
                        <x-menu-item title="Alternativos" icon="o-users" link="/alts" />
                    </x-menu-sub>
                    <x-menu-sub title="Productos" icon="o-cube">
                        <x-menu-item title="Listas de Precios" icon="o-square-3-stack-3d" link="/products" />
                        <x-menu-item title="Atrib. Extras Web" icon="s-square-3-stack-3d" link="/products/extras" />
                    </x-menu-sub>
                    <x-menu-sub title="Web" icon="o-paint-brush">
                        <x-menu-item title="Slider" icon="o-photo" link="/slider" />
                    </x-menu-sub>
                    <x-menu-sub title="Contenido" icon="o-document-text">
                        <x-menu-item title="Logros" icon="o-star" link="/achievements" />
                        <x-menu-item title="Asignar Logro" icon="o-plus-circle" link="/assign-achievement" />
                    </x-menu-sub>
                    <x-menu-sub title="Report/Export" icon="o-document-chart-bar">
                        <x-menu-item title="Productos Todos" icon="o-cube" link="/export/products" external />
                        <x-menu-item title="Productos Vista Clientes" icon="o-cube" link="/export/customers-products"
                            external />
                        <x-menu-item title="Usuarios ⇨ Ventas" icon="o-table-cells" link="/export/users-order-stats"
                            external />
                    </x-menu-sub>
                    <x-menu-item title="Settings" icon="o-cog-8-tooth" link="/settings" />
                @endif
                @if($user->role->value != 'guest')
                    <x-menu-item title="Pedidos" icon="o-clipboard-document-list" link="/orders" class="text-warning" />
                    @if($user->role->value === 'customer')
                        <x-menu-item title="Mis Vendedores" icon="o-users" link="/my-sales-agents" />
                    @endif
                    <x-menu-item title="Mi Perfil" icon="o-user" link="/user/profile" />
                @endif
            </x-menu>
        </x-slot:sidebar>

        {{-- The `$slot` goes here --}}
        <x-slot:content>
            {{ $slot }}
        </x-slot:content>
    </x-main>

    {{-- TOAST area --}}
    <x-toast />

    @stack('scripts')
</body>

</html>