<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script>
        // Backend follows the device color scheme (drives Tailwind `dark:` variants)
        (() => {
            const mq = window.matchMedia('(prefers-color-scheme: dark)');
            const apply = () => document.documentElement.classList.toggle('dark', mq.matches);
            if (mq.addEventListener) {
                mq.addEventListener('change', apply);
            }
            apply();
        })();
    </script>
    @php
        $studioCompanyName = App\Helpers\SettingsHelper::settings('company_name', config('app.name'));
        $activeThemeCss = 'resources/css/themes/' . config('app.theme', 'default') . '.css';
    @endphp
    <title>{{ isset($title) ? $title . ' - ' . $studioCompanyName : $studioCompanyName }}</title>

    @php
        $studioViteEntries = ['resources/css/app.css', 'resources/js/app.js'];
        if (config('app.theme', 'default') !== 'default' && file_exists(base_path($activeThemeCss))) {
            $studioViteEntries[] = $activeThemeCss;
        }
    @endphp
    @vite($studioViteEntries)
    <script src="{{ asset('js/tinymce/tinymce.min.js') }}"></script>
    @livewireStyles
    <link rel="icon" type="image/webp" href="{{ asset('imgs/fallback.webp') }}">
</head>

<body class="min-h-screen font-sans antialiased bg-base-200/50 dark:bg-base-200">
    <main class="w-full min-h-screen p-3 lg:p-4">
        {{ $slot }}
    </main>

    {{-- TOAST area --}}
    <x-toast />

    @stack('scripts')
</body>

</html>
