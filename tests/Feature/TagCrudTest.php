<?php

use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;

uses(RefreshDatabase::class);

it('tags index page renders with existing tags', function () {
    Tag::fromName('NUEVO');
    Tag::fromName('OFERTA');

    $test = Volt::test('tags.index');

    $test->assertSee('NUEVO')
        ->assertSee('OFERTA')
        ->assertSee('Tags');
});

it('tags index page shows search field', function () {
    Tag::fromName('NUEVO');

    $test = Volt::test('tags.index');

    $test->assertSee('Buscar');
});

it('tags crud page creates a new tag', function () {
    $test = Volt::test('tags.crud');

    $test->set('formData.name', 'PRUEBA')
        ->set('formData.slug', '')
        ->set('formData.sort_order', 0);

    $test->call('save')
        ->assertRedirect('/tags');

    $this->assertDatabaseHas('tags', [
        'slug' => 'prueba',
        'name' => 'PRUEBA',
    ]);
});

it('tags crud page edits existing tag', function () {
    $tag = Tag::fromName('NUEVO');

    $test = Volt::test('tags.crud', ['tag' => $tag->id]);

    $test->set('formData.name', 'ACTUALIZADO');

    $test->call('save')
        ->assertRedirect('/tags');

    $this->assertDatabaseHas('tags', [
        'id' => $tag->id,
        'name' => 'ACTUALIZADO',
    ]);
});

it('tags crud page validates required name', function () {
    $test = Volt::test('tags.crud');

    $test->set('formData.name', '')
        ->set('formData.slug', '')
        ->set('formData.sort_order', 0);

    $test->call('save')
        ->assertHasErrors(['formData.name' => 'required']);
});

it('tags index page can delete a tag', function () {
    $tag = Tag::fromName('BORRAR');

    $test = Volt::test('tags.index');
    $test->call('delete', $tag);

    $this->assertDatabaseMissing('tags', ['slug' => 'borrar']);
});
