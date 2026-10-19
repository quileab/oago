<?php

namespace App\Services;

use App\Helpers\SettingsHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Rate limiting aware of trusted IPs (reverse proxies, integrations, etc.).
 *
 * Trusted IPs (setting `trusted_rate_limit_ips`, IP or CIDR) get a very high
 * but existing limit instead of an absolute bypass, configured in
 * config/default_settings.php.
 */
class RateLimitService
{
    /**
     * Attempts per minute allowed for trusted IPs.
     */
    public const TRUSTED_LIMIT_PER_MINUTE = 600;

    /**
     * Default attempts per minute for untrusted IPs.
     */
    public const DEFAULT_LIMIT_PER_MINUTE = 5;

    /**
     * Resolves the connection peer IP (not honoring any proxy header).
     */
    public function connectionIp(Request $request): string
    {
        return $request->server('REMOTE_ADDR') ?: ($request->ip() ?? 'unknown');
    }

    /**
     * Resolves the real client IP. Only when the peer IP is trusted,
     * the X-Forwarded-For chain is honored (rightmost non-trusted entry),
     * which prevents header spoofing from untrusted clients.
     */
    public function clientIp(Request $request): string
    {
        $peer = $this->connectionIp($request);

        if (! $this->isTrustedIp($request)) {
            return $peer;
        }

        $forwarded = $request->header('X-Forwarded-For');

        if (! $forwarded) {
            return $peer;
        }

        $trusted = $this->trustedEntries();
        $chain = array_map('trim', explode(',', $forwarded));

        for ($i = count($chain) - 1; $i >= 0; $i--) {
            $candidate = $chain[$i];

            if (! $this->isValidIp($candidate)) {
                continue;
            }

            if (in_array($candidate, $trusted, true) || $this->matchesCidrList($candidate, $trusted)) {
                continue;
            }

            return $candidate;
        }

        return $peer;
    }

    /**
     * The connection peer IP belongs to the trusted list.
     */
    public function isTrustedIp(Request $request): bool
    {
        return $this->matchesEntry($this->connectionIp($request), $this->trustedEntries());
    }

    /**
     * Attempts for an action from the current client. Returns true when
     * the attempt is allowed. Trusted clients are not counted all the way
     * to zero: they share a very high limit.
     *
     * @return bool allowed to proceed
     */
    public function attemptThrough(string $action, Request $request, int $untrustedLimit = self::DEFAULT_LIMIT_PER_MINUTE): bool
    {
        $isTrusted = $this->isTrustedIp($request);
        $max = $isTrusted ? self::TRUSTED_LIMIT_PER_MINUTE : $untrustedLimit;
        $key = $action.'|'.$this->clientIp($request);

        if (RateLimiter::tooManyAttempts($key, $max)) {
            return false;
        }

        RateLimiter::hit($key, 60);

        return true;
    }

    /**
     * Clears the counter for an action/client (e.g. after a successful login).
     */
    public function clearFor(string $action, Request $request): void
    {
        RateLimiter::clear($action.'|'.$this->clientIp($request));
    }

    /**
     * @return array<string> trusted IP / CIDR entries
     */
    protected function trustedEntries(): array
    {
        // Fail-closed: if settings are unavailable, treat as an empty
        // trusted list (everyone gets rate limited).
        try {
            $raw = SettingsHelper::settings('trusted_rate_limit_ips', []);
        } catch (\Throwable) {
            return [];
        }

        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            $raw = json_last_error() === JSON_ERROR_NONE ? $decoded : preg_split('/\s*,\s*/', $raw);
        }

        $raw = is_array($raw) ? $raw : [$raw];

        return array_values(array_filter(array_map(
            static fn ($entry) => trim((string) $entry),
            $raw
        )));
    }

    /**
     * @param  array<string>  $entries
     */
    protected function matchesEntry(string $ip, array $entries): bool
    {
        if (! $this->isValidIp($ip)) {
            return false;
        }

        foreach ($entries as $entry) {
            if ($entry === $ip) {
                return true;
            }

            if ($this->matchesCidr($ip, $entry)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string>  $entries
     */
    protected function matchesCidrList(string $ip, array $entries): bool
    {
        foreach ($entries as $entry) {
            if ($this->matchesCidr($ip, $entry)) {
                return true;
            }
        }

        return false;
    }

    /**
     * CIDR notation match, IPv4 and IPv6.
     */
    protected function matchesCidr(string $ip, string $entry): bool
    {
        if (! str_contains($entry, '/')) {
            return false;
        }

        [$subnet, $bits] = explode('/', $entry, 2);

        if (! $this->isValidIp($subnet) || ! ctype_digit($bits)) {
            return false;
        }

        if (str_contains($subnet, ':') !== str_contains($ip, ':')) {
            return false;
        }

        $ipBytes = @inet_pton($ip);
        $subnetBytes = @inet_pton($subnet);

        if ($ipBytes === false || $subnetBytes === false) {
            return false;
        }

        $bits = (int) $bits;

        if ($bits < 0 || $bits > strlen($ipBytes) * 8) {
            return false;
        }

        $fullBytes = intdiv($bits, 8);
        $remainingBits = $bits % 8;

        if ($fullBytes > 0 && substr($ipBytes, 0, $fullBytes) !== substr($subnetBytes, 0, $fullBytes)) {
            return false;
        }

        if ($remainingBits > 0) {
            $mask = 0xFF << (8 - $remainingBits);
            $ipByte = ord($ipBytes[$fullBytes]) & $mask;
            $subnetByte = ord($subnetBytes[$fullBytes]) & $mask;

            return $ipByte === $subnetByte;
        }

        return true;
    }

    /**
     * Single IP (v4 or v6) validation. CIDR ranges by themselves are not valid IPs.
     */
    protected function isValidIp(string $value): bool
    {
        return (bool) filter_var($value, FILTER_VALIDATE_IP);
    }
}
