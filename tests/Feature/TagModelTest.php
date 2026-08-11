<?php

use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

it('creates a tag with fromName', function () {
    $tag = Tag::fromName('nuevo');

    expect($tag->slug)->toBe('nuevo')
        ->and($tag->name)->toBe('NUEVO');

    $this->assertDatabaseHas('tags', [
        'slug' => 'nuevo',
        'name' => 'NUEVO',
    ]);
});

it('normalizes tag names to uppercase in fromName', function () {
    $tag = Tag::fromName('Oferta Especial');

    expect($tag->name)->toBe('OFERTA ESPECIAL')
        ->and($tag->slug)->toBe('oferta-especial');
});

it('finds existing tag via fromName without creating duplicate', function () {
    Tag::fromName('NUEVO');

    $tag = Tag::fromName('nuevo');

    $this->assertDatabaseCount('tags', 1);
    expect($tag->slug)->toBe('nuevo');
});

it('resolves tag name from slug', function () {
    Tag::fromName('nuevo');

    $name = Tag::resolveName('nuevo');

    expect($name)->toBe('NUEVO');
});

it('returns null for unknown slug in resolveName', function () {
    $name = Tag::resolveName('nonexistent');

    expect($name)->toBeNull();
});

it('caches all tags and respects cache invalidation', function () {
    $tag = Tag::fromName('nuevo');

    $cached = Tag::allCached();
    expect($cached)->toHaveCount(1);

    $tag->name = 'UPDATED';
    $tag->save();

    // Cache should be cleared, so fresh query returns updated
    $cached = Tag::allCached();
    expect($cached->first()->name)->toBe('UPDATED');
});
