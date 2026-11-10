<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function tearDown(): void
    {
        // Limpiar archivos temporales de carritos generados durante las pruebas
        foreach (glob(storage_path('app/private/*_cart.json')) ?: [] as $file) {
            @unlink($file);
        }

        parent::tearDown();
    }
}
