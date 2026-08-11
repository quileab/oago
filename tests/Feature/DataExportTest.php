<?php

use Illuminate\Support\Facades\File;

afterEach(function () {
    if (File::exists(base_path('test_dump.sql'))) {
        File::delete(base_path('test_dump.sql'));
    }
});

test('db:export command exports database schema and data', function () {
    $this->artisan('db:export', [
        'filename' => 'test_dump.sql',
        '--drop' => true,
        '--if-not-exists' => true,
    ])
        ->assertExitCode(0);

    expect(File::exists(base_path('test_dump.sql')))->toBeTrue();

    $content = File::get(base_path('test_dump.sql'));
    expect($content)->toContain('DROP TABLE IF EXISTS')
        ->toContain('CREATE TABLE IF NOT EXISTS');
});
