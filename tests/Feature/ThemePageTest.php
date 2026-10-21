<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renders a theme page inside its layout', function () {
    config(['app.theme' => 'encendidorqta']);

    $response = $this->get('/page/nosotros');

    $response->assertSuccessful()
        ->assertSee('<html', false)
        ->assertSee('</html>', false);
});

it('returns 404 for an unknown theme page', function () {
    config(['app.theme' => 'encendidorqta']);

    $this->get('/page/no-existe')->assertNotFound();
});
