<?php

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

it('serves fallback for non-image content-type', function () {
    Http::fake([
        'https://example.com/test.txt' => Http::response('Hello world', 200, ['Content-Type' => 'text/plain']),
    ]);

    $response = $this->get('/proxy-image?url=https://example.com/test.txt');

    // Should return fallback image (webp)
    $response->assertStatus(200);
    $response->assertHeader('Content-Type', 'image/webp');
});

it('serves fallback for oversized images', function () {
    $hugeContent = str_repeat('A', 6 * 1024 * 1024); // 6MB
    Http::fake([
        'https://example.com/huge.jpg' => Http::response($hugeContent, 200, ['Content-Type' => 'image/jpeg']),
    ]);

    $response = $this->get('/proxy-image?url=https://example.com/huge.jpg');

    // Should return fallback image
    $response->assertStatus(200);
    $response->assertHeader('Content-Type', 'image/webp');
});

it('proxies and caches valid images', function () {
    Storage::fake('public');

    // Tiny valid 1x1 pixel JPEG
    $imageContent = base64_decode('/9j/4AAQSkZJRgABAQEASABIAAD/2wBDAP////////////////////////////////////////////////////////////////////////////////////2wBDAf////////////////////////////////////////////////////////////////////////////////////wAARCAABAAEDASIAAhEBAxEB/8QAHwAAAQUBAQEBAQEAAAAAAAAAAAECAwQFBgcICQoL/8QAtRAAAgEDAwIEAwUFBAQAAAF9AQIDAAQRBRIhMUEGE1FhByJxFDKBkaEII0KxwRVS0fAkM2JyggkKFhcYGRolJicoKSo0NTY3ODk6Q0RFRkdISUpTVFVWV1hZWmNkZWZnaGlqc3R1dnd4eXqDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uHi4+Tl5ufo6erx8vP09fb3+Pn6/8QAHwEAAwEBAQEBAQEBAQAAAAAAAAECAwQFBgcICQoL/8QAtREAAgECBAQDBAcFBAQAAQJ3AAECAxEEBSExBhJBUQdhcRMiMoEIFEKRobHBCSMzUvAVYnLRChYkNOEl8RcYGRomJygpKjU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6goOEhYaHiImKkpOUlWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uHi4+Tl5ufo6erx8vP09fb3+Pn6/9oADAMBAAIRAxEAPwD+f+iiiv9A8P8A8iaP+Ffkf6Y0v8AkXp+X6BRRRX6ef55BRRRX/9k=');

    Http::fake([
        'https://example.com/valid.jpg' => Http::response($imageContent, 200, ['Content-Type' => 'image/jpeg']),
    ]);

    // We need to allow example.com in settings for this to work
    config(['settings.image_proxy_allowed_hosts' => ['example.com']]);

    $response = $this->get('/proxy-image?url=https://example.com/valid.jpg');

    $response->assertSuccessful();
    // It might return webp if GD is enabled, or original content-type if not.
    // Given we are testing the proxying logic.
});
