<?php

namespace Tests;

use App\Helpers\SettingsHelper;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        // SettingsHelper keeps a static memo that outlives the per-test application instance.
        // App boot resolves the active theme from settings and prepends theme view paths, so a
        // stale memo from a previous test would make the new app boot with the old theme.
        // Reset it *before* the application boots.
        SettingsHelper::flushMemo();

        parent::setUp();
    }

    protected function tearDown(): void
    {
        // Limpiar archivos temporales de carritos generados durante las pruebas
        foreach (glob(storage_path('app/private/*_cart.json')) ?: [] as $file) {
            @unlink($file);
        }

        parent::tearDown();
    }
}
