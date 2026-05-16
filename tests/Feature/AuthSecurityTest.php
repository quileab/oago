<?php

use function Pest\Laravel\postJson;

it('does not leak email existence during login', function () {
    // Attempt login with non-existent email
    $response = postJson('/api/login', [
        'email' => 'nonexistent@example.com',
        'password' => 'password',
    ]);

    // Before fix, this returns 400 because validation fails on exists:users
    // After fix, it should return 401 with a generic message
    $response->assertStatus(401)
        ->assertJson(['message' => 'The provided credentials are incorrect.']);
});

it('applies rate limiting to login route', function () {
    // Make 6 requests
    for ($i = 0; $i < 6; $i++) {
        postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);
    }

    // The 7th request should be throttled
    $response = postJson('/api/login', [
        'email' => 'test@example.com',
        'password' => 'password',
    ]);

    $response->assertStatus(429);
});
