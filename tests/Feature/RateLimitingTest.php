<?php

use App\Helpers\SettingsHelper;
use App\Services\RateLimitService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;

uses(RefreshDatabase::class);

beforeEach(function () {
    SettingsHelper::update_setting('trusted_rate_limit_ips', json_encode([]));
    Cache::flush();
});

it('throttles api login after the allowed attempts', function () {
    for ($i = 0; $i < 5; $i++) {
        $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ])->assertStatus(401);
    }

    $this->postJson('/api/login', [
        'email' => 'test@example.com',
        'password' => 'password',
    ])->assertStatus(429);
});

it('gives trusted ips a very high but existing limit', function () {
    // Trust localhost (CIDR entry)
    SettingsHelper::update_setting('trusted_rate_limit_ips', json_encode(['127.0.0.0/8']));
    Cache::forget('settings.trusted_rate_limit_ips');

    $request = Request::create('/api/login', 'POST');
    $service = app(RateLimitService::class);

    expect($service->isTrustedIp($request))->toBeTrue();

    // Trusted limit is defined and much higher than the default
    expect(RateLimitService::TRUSTED_LIMIT_PER_MINUTE)
        ->toBeGreaterThan(RateLimitService::DEFAULT_LIMIT_PER_MINUTE)
        ->toBe(600);

    // Untrusted limit applies when not in the list
    SettingsHelper::update_setting('trusted_rate_limit_ips', json_encode(['10.0.0.0/8']));
    Cache::forget('settings.trusted_rate_limit_ips');
    expect($service->isTrustedIp($request))->toBeFalse();
});

it('only honors x-forwarded-for from trusted peers', function () {
    $service = app(RateLimitService::class);

    // Untrusted peer spoofing the header: must be ignored
    $request = Request::create('/api/login', 'POST', [], [], [], [
        'REMOTE_ADDR' => '203.0.113.10',
        'HTTP_X_FORWARDED_FOR' => '1.2.3.4, 10.0.0.99',
    ]);
    expect($service->clientIp($request))->toBe('203.0.113.10');
    expect($service->isTrustedIp($request))->toBeFalse();

    // Trusted proxy forwarding: real client is resolved from the chain
    SettingsHelper::update_setting('trusted_rate_limit_ips', json_encode(['10.0.0.0/8']));
    Cache::forget('settings.trusted_rate_limit_ips');

    $request = Request::create('/api/login', 'POST', [], [], [], [
        'REMOTE_ADDR' => '10.0.0.99',
        'HTTP_X_FORWARDED_FOR' => '1.2.3.4, 10.0.0.1',
    ]);
    expect($service->isTrustedIp($request))->toBeTrue();
    expect($service->clientIp($request))->toBe('1.2.3.4');

    // Trusted proxy without header: falls back to the proxy IP
    $request = Request::create('/api/login', 'POST', [], [], [], [
        'REMOTE_ADDR' => '10.0.0.99',
    ]);
    expect($service->clientIp($request))->toBe('10.0.0.99');
});

it('blocks web register through the rate limiter service', function () {
    $request = Request::create('/registrate', 'POST');
    $service = app(RateLimitService::class);

    for ($i = 0; $i < 5; $i++) {
        expect($service->attemptThrough('web-register', $request, 5))->toBeTrue();
    }

    expect($service->attemptThrough('web-register', $request, 5))->toBeFalse();

    // A different client is not affected
    $other = Request::create('/registrate', 'POST', [], [], [], [
        'REMOTE_ADDR' => '203.0.113.77',
    ]);
    expect($service->attemptThrough('web-register', $other, 5))->toBeTrue();
});
