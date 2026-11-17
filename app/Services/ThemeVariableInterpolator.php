<?php

namespace App\Services;

use App\Helpers\SettingsHelper;
use App\Models\AltUser;
use App\Models\User;

class ThemeVariableInterpolator
{
    /**
     * Function calls that must never survive into a compiled theme template.
     *
     * Stripping `{{` / `}}` is not enough on its own: text like `{{ exec("id") }}` would
     * otherwise compile down to a bare `exec("id")` inside Blade markup, one stray pair of
     * braces away from executing.
     */
    private const DANGEROUS_FUNCTION_PATTERN = '/\b(?:exec|shell_exec|system|passthru|proc_open|popen|pcntl_exec|eval|assert|create_function|call_user_func|call_user_func_array|forward_static_call|forward_static_call_array|file_put_contents|fwrite|fopen|unlink|rename|chmod|chown|dl|extract|parse_str|include|include_once|require|require_once|base64_decode)\s*\(/i';

    /**
     * @var array<string, array{key: string, blade: string, preview: callable}>|null
     */
    private ?array $aliasMap = null;

    /**
     * @var array<string, array<string, string>>
     */
    private array $previewValueCache = [];

    /**
     * Whitelist of supported variables grouped by category for the UI picker.
     *
     * @return array<string, array<int, array{token: string, label: string, aliases: array<int, string>, blade: string, preview: callable}>>
     */
    public function getCatalog(): array
    {
        return [
            'Empresa / Parámetros' => [
                [
                    'token' => '{{ company.name }}',
                    'label' => 'Nombre de la empresa',
                    'aliases' => ['company.name', 'setting.company_name', 'empresa.nombre'],
                    'blade' => "\\App\\Helpers\\SettingsHelper::settings('company_name', config('app.name'))",
                    'preview' => fn (string $theme): string => (string) SettingsHelper::settings('company_name', config('app.name')),
                ],
                [
                    'token' => '{{ company.address }}',
                    'label' => 'Dirección de la empresa',
                    'aliases' => ['company.address', 'setting.company_address', 'empresa.direccion'],
                    'blade' => "\\App\\Helpers\\SettingsHelper::settings('company_address', '')",
                    'preview' => fn (string $theme): string => (string) SettingsHelper::settings('company_address', ''),
                ],
                [
                    'token' => '{{ company.phone }}',
                    'label' => 'Teléfono principal',
                    'aliases' => ['company.phone', 'setting.company_phone', 'empresa.telefono'],
                    'blade' => "\\App\\Helpers\\SettingsHelper::settings('company_phone', '')",
                    'preview' => fn (string $theme): string => (string) SettingsHelper::settings('company_phone', ''),
                ],
                [
                    'token' => '{{ company.email }}',
                    'label' => 'Email de contacto',
                    'aliases' => ['company.email', 'setting.company_email', 'empresa.email'],
                    'blade' => "\\App\\Helpers\\SettingsHelper::settings('company_email', '')",
                    'preview' => fn (string $theme): string => (string) SettingsHelper::settings('company_email', ''),
                ],
                [
                    'token' => '{{ company.free_freight }}',
                    'label' => 'Monto mínimo p/ flete bonificado ($)',
                    'aliases' => ['company.free_freight', 'company.free_freight_threshold', 'setting.free_freight_threshold', 'empresa.flete_gratis'],
                    'blade' => "number_format((float) \\App\\Helpers\\SettingsHelper::settings('free_freight_threshold', 0), 2, ',', '.')",
                    'preview' => fn (string $theme): string => number_format((float) SettingsHelper::settings('free_freight_threshold', 0), 2, ',', '.'),
                ],
                [
                    'token' => '{{ company.min_freight }}',
                    'label' => 'Piso mínimo de flete ($)',
                    'aliases' => ['company.min_freight', 'company.freight_min_amount', 'setting.freight_min_amount', 'empresa.flete_minimo'],
                    'blade' => "number_format((float) \\App\\Helpers\\SettingsHelper::settings('freight_min_amount', 0), 2, ',', '.')",
                    'preview' => fn (string $theme): string => number_format((float) SettingsHelper::settings('freight_min_amount', 0), 2, ',', '.'),
                ],
            ],
            'Usuario / Cliente' => [
                [
                    'token' => '{{ user.name }}',
                    'label' => 'Nombre del usuario conectado',
                    'aliases' => ['user.name', 'usuario.nombre'],
                    'blade' => "(current_user()?->name ?? 'Invitado')",
                    'preview' => fn (string $theme): string => (string) (current_user()?->name ?? 'Invitado'),
                ],
                [
                    'token' => '{{ user.full_name }}',
                    'label' => 'Apellido y nombre del cliente',
                    'aliases' => ['user.full_name', 'usuario.nombre_completo'],
                    'blade' => "(current_user()?->fullName ?? 'Invitado')",
                    'preview' => fn (string $theme): string => $this->resolveUserFullName(current_user()),
                ],
                [
                    'token' => '{{ user.email }}',
                    'label' => 'Email del cliente conectado',
                    'aliases' => ['user.email', 'usuario.email'],
                    'blade' => "(current_user()?->email ?? '')",
                    'preview' => fn (string $theme): string => (string) (current_user()?->email ?? ''),
                ],
                [
                    'token' => '{{ user.price_list }}',
                    'label' => 'Nombre de lista de precios asignada',
                    'aliases' => ['user.price_list', 'user.list_name', 'usuario.lista'],
                    'blade' => "(current_user()?->list?->name ?? 'General')",
                    'preview' => fn (string $theme): string => (string) (current_user()?->list?->name ?? 'General'),
                ],
            ],
            'Tema y Sistema' => [
                [
                    'token' => '{{ theme.name }}',
                    'label' => 'Nombre del tema activo',
                    'aliases' => ['theme.name', 'tema.nombre'],
                    'blade' => "config('app.theme', 'default')",
                    'preview' => fn (string $theme): string => $theme,
                ],
                [
                    'token' => "{{ theme.image('file.webp') }}",
                    'label' => 'URL de imagen del tema',
                    'aliases' => ['theme.image', 'tema.imagen'],
                    'blade' => '',
                    'preview' => fn (string $theme, ?string $arg = null): string => $arg !== null ? asset("themes/{$theme}/images/".ltrim($arg, '/')) : '',
                ],
                [
                    'token' => '{{ current_year }}',
                    'label' => 'Año calendario actual',
                    'aliases' => ['current_year', 'system.year', 'anio_actual'],
                    'blade' => "date('Y')",
                    'preview' => fn (string $theme): string => date('Y'),
                ],
            ],
        ];
    }

    /**
     * Compile user text with whitelisted `{{ ... }}` tokens into safe Blade template markup.
     * Any raw PHP tags or arbitrary Blade directives outside the whitelist are neutralized.
     */
    public function compileToBlade(string $text, bool $escapeHtml = true): string
    {
        [$tokenized, $tokens] = $this->extractWhitelistedTokens($text);

        $sanitized = $escapeHtml
            ? $this->sanitizePlainText($tokenized)
            : $this->sanitizeRichHtml($tokenized);

        foreach ($tokens as $placeholder => $definition) {
            $bladeEcho = '{{ '.$definition['blade'].' }}';
            $sanitized = str_replace($placeholder, $bladeEcho, $sanitized);
        }

        return $sanitized;
    }

    /**
     * Compile user text into a safe PHP string expression suitable for Blade `:props="[...]"` arrays.
     */
    public function compileToPhpExpression(string $text): string
    {
        if (! str_contains($text, '{{')) {
            return var_export($this->stripDangerousDirectives($text), true);
        }

        [$tokenized, $tokens] = $this->extractWhitelistedTokens($text);

        $cleanText = $this->stripDangerousDirectives($tokenized);

        if ($tokens === []) {
            return var_export($cleanText, true);
        }

        $parts = [];
        $cursor = 0;

        while ($cursor < strlen($cleanText)) {
            $nextPos = null;
            $matchedPlaceholder = null;

            foreach ($tokens as $placeholder => $definition) {
                $pos = strpos($cleanText, $placeholder, $cursor);
                if ($pos !== false && ($nextPos === null || $pos < $nextPos)) {
                    $nextPos = $pos;
                    $matchedPlaceholder = $placeholder;
                }
            }

            if ($nextPos === null || $matchedPlaceholder === null) {
                $tail = substr($cleanText, $cursor);
                if ($tail !== '') {
                    $parts[] = var_export($tail, true);
                }

                break;
            }

            if ($nextPos > $cursor) {
                $parts[] = var_export(substr($cleanText, $cursor, $nextPos - $cursor), true);
            }

            $parts[] = '(string) ('.$tokens[$matchedPlaceholder]['blade'].')';
            $cursor = $nextPos + strlen($matchedPlaceholder);
        }

        return $parts === [] ? "''" : implode(' . ', $parts);
    }

    /**
     * Resolve whitelisted tokens to live values for the visual builder preview.
     */
    public function interpolatePreview(string $text, string $theme, bool $escapeHtml = true): string
    {
        if (! str_contains($text, '{{')) {
            return $escapeHtml
                ? $this->sanitizePlainText($text)
                : $this->sanitizeRichHtml($text);
        }

        [$tokenized, $tokens] = $this->extractWhitelistedTokens($text);

        $sanitized = $escapeHtml
            ? $this->sanitizePlainText($tokenized)
            : $this->sanitizeRichHtml($tokenized);

        foreach ($tokens as $placeholder => $definition) {
            $resolved = $this->resolveCachedPreview($theme, $definition['key'], $definition['preview']);
            $sanitized = str_replace($placeholder, e($resolved), $sanitized);
        }

        return $sanitized;
    }

    /**
     * Resolve an asset value that may contain `{{ theme.image('...') }}`, a theme filename, or an absolute URL.
     */
    public function compileAssetPhpExpression(string $value): string
    {
        $trimmed = trim($value);
        if ($trimmed === '') {
            return "''";
        }

        if (str_contains($trimmed, '{{')) {
            return $this->compileToPhpExpression($trimmed);
        }

        if (str_starts_with($trimmed, 'http://') || str_starts_with($trimmed, 'https://') || str_starts_with($trimmed, '/')) {
            return var_export($trimmed, true);
        }

        if ($this->isSafeImagePath($trimmed)) {
            $cleanFile = ltrim($trimmed, '/');

            return "asset('themes/' . config('app.theme', 'default') . '/images/' . ".var_export($cleanFile, true).')';
        }

        return "''";
    }

    /**
     * Resolve an asset value for live builder preview.
     */
    public function resolveAssetPreviewUrl(string $value, string $theme): string
    {
        $trimmed = trim($value);
        if ($trimmed === '') {
            return '';
        }

        if (str_contains($trimmed, '{{')) {
            [$tokenized, $tokens] = $this->extractWhitelistedTokens($trimmed);
            foreach ($tokens as $placeholder => $definition) {
                $tokenized = str_replace($placeholder, $this->resolveCachedPreview($theme, $definition['key'], $definition['preview']), $tokenized);
            }

            return $tokenized;
        }

        if (str_starts_with($trimmed, 'http://') || str_starts_with($trimmed, 'https://') || str_starts_with($trimmed, '/')) {
            return $trimmed;
        }

        if ($this->isSafeImagePath($trimmed)) {
            return asset("themes/{$theme}/images/".ltrim($trimmed, '/'));
        }

        return '';
    }

    /**
     * @return array{0: string, 1: array<string, array{key: string, blade: string, preview: callable}>}
     */
    private function extractWhitelistedTokens(string $text): array
    {
        $tokens = [];
        $index = 0;
        $aliasMap = $this->buildAliasMap();

        $replaced = preg_replace_callback(
            '/\{\{\s*([a-zA-Z0-9_.]+)(?:\(\s*[\'"]([^\'"]+)[\'"]\s*\))?\s*\}\}/',
            function (array $matches) use (&$tokens, &$index, $aliasMap): string {
                $key = strtolower($matches[1]);
                $argument = $matches[2] ?? null;

                if (in_array($key, ['tema.imagen', 'theme.image'], true)) {
                    if ($argument === null || ! $this->isSafeImagePath($argument)) {
                        return $matches[0];
                    }

                    $cleanArg = ltrim($argument, '/');
                    $placeholder = "__OAGO_VAR_{$index}__";
                    $index++;

                    $tokens[$placeholder] = [
                        'key' => "theme.image:{$cleanArg}",
                        'blade' => "asset('themes/' . config('app.theme', 'default') . '/images/' . ".var_export($cleanArg, true).')',
                        'preview' => fn (string $theme): string => asset("themes/{$theme}/images/{$cleanArg}"),
                    ];

                    return $placeholder;
                }

                if (! isset($aliasMap[$key])) {
                    return $matches[0];
                }

                $entry = $aliasMap[$key];
                $placeholder = "__OAGO_VAR_{$index}__";
                $index++;

                $tokens[$placeholder] = [
                    'key' => $entry['key'],
                    'blade' => $entry['blade'],
                    'preview' => $entry['preview'],
                ];

                return $placeholder;
            },
            $text
        );

        return [$replaced ?? $text, $tokens];
    }

    /**
     * @return array<string, array{key: string, blade: string, preview: callable}>
     */
    private function buildAliasMap(): array
    {
        if ($this->aliasMap !== null) {
            return $this->aliasMap;
        }

        $map = [];

        foreach ($this->getCatalog() as $items) {
            foreach ($items as $item) {
                if ($item['blade'] === '') {
                    continue;
                }

                $canonicalKey = strtolower($item['aliases'][0] ?? $item['token']);
                foreach ($item['aliases'] as $alias) {
                    $map[strtolower($alias)] = [
                        'key' => $canonicalKey,
                        'blade' => $item['blade'],
                        'preview' => $item['preview'],
                    ];
                }
            }
        }

        return $this->aliasMap = $map;
    }

    private function resolveCachedPreview(string $theme, string $key, callable $resolver): string
    {
        if (isset($this->previewValueCache[$theme][$key])) {
            return $this->previewValueCache[$theme][$key];
        }

        return $this->previewValueCache[$theme][$key] = (string) $resolver($theme);
    }

    private function sanitizePlainText(string $text): string
    {
        $stripped = $this->stripDangerousDirectives($text);

        return htmlspecialchars($stripped, ENT_QUOTES, 'UTF-8');
    }

    private function sanitizeRichHtml(string $html): string
    {
        $stripped = $this->stripDangerousDirectives($html);

        $stripped = preg_replace('#<(script|style|iframe|object|embed|form|input|textarea|button|select)\b[^>]*>.*?</\1>#is', '', $stripped) ?? $stripped;
        $stripped = strip_tags(
            $stripped,
            '<p><br><b><strong><i><em><u><s><ul><ol><li><h1><h2><h3><h4><h5><h6><blockquote><a><span><div><hr><table><thead><tbody><tr><th><td><img>'
        );

        // Strip inline event handlers (onclick, onload, etc.) and javascript:/data: URIs
        $stripped = preg_replace('/\s+on[a-z]+\s*=\s*(?:"[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $stripped) ?? $stripped;
        $stripped = preg_replace('/(href|src)\s*=\s*(["\'])\s*(?:javascript|vbscript|data):.*?\2/i', '$1=$2#$2', $stripped) ?? $stripped;

        return $stripped;
    }

    private function stripDangerousDirectives(string $text): string
    {
        $clean = str_replace(['<?php', '<?=', '<?', '?>', '{{', '}}', '{!!', '!!}'], ['', '', '', '', '', '', '', ''], $text);

        $clean = preg_replace(
            '/@(?:php|endphp|inject|include|includeIf|includeWhen|includeUnless|includeFirst|each|eval|verbatim|endverbatim|use|extends|section|yield|stack|push|once)\b/i',
            '',
            $clean
        ) ?? $clean;

        return preg_replace(self::DANGEROUS_FUNCTION_PATTERN, '', $clean) ?? $clean;
    }

    private function isSafeImagePath(string $path): bool
    {
        $clean = ltrim(str_replace('\\', '/', $path), '/');

        if ($clean === '' || str_contains($clean, '..') || str_contains($clean, "\0")) {
            return false;
        }

        return (bool) preg_match('/^[a-zA-Z0-9._\/-]+$/', $clean);
    }

    private function resolveUserFullName(User|AltUser|null $user): string
    {
        if (! $user) {
            return 'Invitado';
        }

        return $user->fullName ?: $user->name;
    }
}
