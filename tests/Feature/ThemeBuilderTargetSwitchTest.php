<?php

use App\Services\ThemeCatalogComponentRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;

uses(RefreshDatabase::class);

it('switches the builder destination through the group component and reloads the blueprint', function (): void {
    $catalogLabel = app(ThemeCatalogComponentRegistry::class)->components()['product_card']['label'];

    Volt::test('themes.builder')
        ->assertSee('Página Estática (/page/...)')
        ->assertDontSee('Componente de Catálogo (Card / Vista Única)')
        ->set('targetType', 'catalog')
        ->assertSet('targetType', 'catalog')
        ->assertSet('pageTitle', $catalogLabel)
        ->assertSee('Componente de Catálogo (Card / Vista Única)')
        ->assertDontSee('Página Estática (/page/...)');
});

it('switches canvas tab, viewport and catalog preview state through the group components', function (): void {
    Volt::test('themes.builder')
        ->assertSet('canvasTab', 'visual')
        ->assertSet('viewport', 'desktop')
        ->set('canvasTab', 'code')
        ->assertSet('canvasTab', 'code')
        ->assertSee('Código Blade Compilado')
        ->set('canvasTab', 'visual')
        ->set('viewport', 'mobile')
        ->assertSet('viewport', 'mobile')
        ->assertDontSee('aria-label="Con Oferta"', false)
        ->set('targetType', 'catalog')
        ->assertSee('aria-label="Con Oferta"', false)
        ->assertSee('aria-label="Agotado"', false)
        ->set('catalogPreviewState', 'out_of_stock')
        ->assertSet('catalogPreviewState', 'out_of_stock');
});

it('confirms discarding the draft through a dropdown instead of a browser dialog', function (): void {
    Volt::test('themes.builder')
        ->assertDontSee('Descartar borrador')
        ->set('hasUnsavedDraft', true)
        ->assertSee('Descartar borrador')
        ->assertSee('¿Descartar el borrador?')
        ->assertSee('Sí, volver a la versión guardada')
        ->assertDontSee('wire:confirm="¿Descartar los cambios sin guardar')
        ->call('discardDraft')
        ->assertSet('hasUnsavedDraft', false)
        ->assertDontSee('Descartar borrador');
});
