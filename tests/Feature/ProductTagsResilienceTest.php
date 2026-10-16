<?php

use App\Enums\Role;
use App\Models\Product;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

/**
 * @return array<string, mixed>
 */
function productPayload(array $overrides = []): array
{
    return array_merge([
        'description' => 'CANASTA "3" (20 articulos)',
        'sku' => 'CAN-3',
        'published' => true,
        'featured' => false,
        'visibility' => 'visible',
        'tax_status' => 'taxable',
        'in_stock' => true,
        'stock' => 10,
        'allow_reservation' => false,
        'qtty_package' => 1,
        'qtty_unit' => 1,
    ], $overrides);
}

function adminUser(): User
{
    return User::factory()->create(['role' => Role::ADMIN]);
}

/**
 * Register the tag with its real display name, then attach it by slug.
 */
function productWithNavidadTag(): Product
{
    Tag::fromName('CANASTAS NAVIDEÑAS');
    Tag::fromName('NUEVO');

    $product = makeProduct(['description' => 'CANASTA "3" (20 articulos)']);
    $product->syncTagsBySlug(['canastas-navidenas', 'nuevo']);

    return $product;
}

/*
|--------------------------------------------------------------------------
| Regression: the ERP posts the full product payload without `tags`
|--------------------------------------------------------------------------
|
| The external integration hits POST /api/products ~20k times a day. It never
| sends tags, and before the fix the FormRequest merged `tags => ''`
| unconditionally, which rebuilt the pivot from an empty column and silently
| detached every tag.
|
*/

it('keeps existing tags when the API updates a product without sending tags', function () {
    $admin = adminUser();

    $product = productWithNavidadTag();

    $response = $this->actingAs($admin, 'sanctum')
        ->postJson('/api/products', productPayload([
            'id' => $product->id,
            'stock' => 42,
        ]));

    $response->assertStatus(200);

    $fresh = $product->fresh();
    expect($fresh->tags_array)->toEqualCanonicalizing(['NUEVO', 'CANASTAS NAVIDEÑAS']);
    expect($fresh->stock)->toBe(42);
});

it('keeps existing tags on a PUT that omits tags', function () {
    $admin = adminUser();

    $product = productWithNavidadTag();

    $this->actingAs($admin, 'sanctum')
        ->putJson('/api/products/'.$product->id, productPayload([
            'description' => 'CANASTA "3" renombrada',
        ]))
        ->assertStatus(200);

    expect($product->fresh()->tags_array)->toEqualCanonicalizing(['CANASTAS NAVIDEÑAS', 'NUEVO']);
});

it('clears the tags when the API explicitly sends an empty tags value', function () {
    $admin = adminUser();

    $product = productWithNavidadTag();

    $this->actingAs($admin, 'sanctum')
        ->postJson('/api/products', productPayload([
            'id' => $product->id,
            'tags' => '',
        ]))
        ->assertStatus(200);

    expect($product->fresh()->tags_array)->toBeEmpty();
});

it('replaces the tags when the API sends a new set', function () {
    $admin = adminUser();

    $product = makeProduct();
    $product->syncTagsBySlug(['nuevo']);

    $this->actingAs($admin, 'sanctum')
        ->postJson('/api/products', productPayload([
            'id' => $product->id,
            'tags' => 'CANASTAS NAVIDEÑAS,OFERTA',
        ]))
        ->assertStatus(200);

    expect($product->fresh()->tags_array)->toBe(['CANASTAS NAVIDEÑAS', 'OFERTA']);
});

it('does not lose tags across a long sequence of ERP style syncs', function () {
    $admin = adminUser();

    $product = productWithNavidadTag();

    for ($i = 0; $i < 5; $i++) {
        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/products', productPayload([
                'id' => $product->id,
                'stock' => 10 + $i,
            ]))
            ->assertStatus(200);
    }

    expect($product->fresh()->tags_array)->toEqualCanonicalizing(['CANASTAS NAVIDEÑAS', 'NUEVO']);
});

/*
|--------------------------------------------------------------------------
| Admin CRUD
|--------------------------------------------------------------------------
*/

it('keeps the pivot intact when saving a product from the admin CRUD', function () {
    $product = productWithNavidadTag();
    $canastas = Tag::fromName('CANASTAS NAVIDEÑAS');
    $nuevo = Tag::fromName('NUEVO');

    Livewire::test('products.crud', ['id' => $product->id])
        ->assertSet('selectedTags', [$canastas->id, $nuevo->id])
        ->set('selectedTags', [$canastas->id, $nuevo->id])
        ->call('save')
        ->assertHasNoErrors();

    expect($product->fresh()->tags_array)->toBe(['CANASTAS NAVIDEÑAS', 'NUEVO']);
});

it('always offers the product current tags as options even if the cache is cold', function () {
    $product = productWithNavidadTag();
    $canastas = Tag::fromName('CANASTAS NAVIDEÑAS');

    // Simulate a stale Tag::allCached() that does not know the tag yet.
    Cache::forget('tags.all');

    $component = Livewire::test('products.crud', ['id' => $product->id]);

    $optionIds = collect($component->instance()->availableTags())->pluck('id');

    expect($optionIds)->toContain($canastas->id);
});

/*
|--------------------------------------------------------------------------
| API response contract
|--------------------------------------------------------------------------
*/

it('still exposes a pipe-delimited tags field in API responses', function () {
    $admin = adminUser();

    $product = productWithNavidadTag();

    $response = $this->actingAs($admin, 'sanctum')->getJson('/api/products/'.$product->id);

    $response->assertStatus(200)->assertJsonPath('tags_string', 'CANASTAS NAVIDEÑAS|NUEVO');
});

it('returns tags for a whole product listing without dropping them', function () {
    $admin = adminUser();

    Tag::fromName('CANASTAS NAVIDEÑAS');

    makeProduct(['description' => 'Canasta 1'])->syncTagsBySlug(['canastas-navidenas']);
    makeProduct(['description' => 'Canasta 2'])->syncTagsBySlug(['canastas-navidenas']);

    $response = $this->actingAs($admin, 'sanctum')->getJson('/api/products');

    $response->assertStatus(200);
    expect($response->json())->toHaveCount(2);
    expect(collect($response->json())->pluck('tags_string')->filter()->unique())->toHaveCount(1);
});

/*
|--------------------------------------------------------------------------
| Catalog reads
|--------------------------------------------------------------------------
*/

it('finds a product by its tag name through the customer API search', function () {
    $customer = User::factory()->create(['role' => Role::CUSTOMER]);

    attachTags(makeProduct([
        'description' => 'CANASTA "3" (20 articulos)',
        'visibility' => 'visible',
    ]), ['CANASTAS NAVIDEÑAS']);

    makeProduct([
        'description' => 'Producto sin etiqueta',
        'visibility' => 'visible',
    ]);

    $response = $this->actingAs($customer, 'sanctum')
        ->getJson('/api/customer/products?search='.urlencode('NAVIDEÑAS'));

    $response->assertStatus(200);

    $payload = $response->json();
    $items = $payload['data'] ?? $payload;
    expect($items)->toHaveCount(1);
    expect($items[0]['description'])->toBe('CANASTA "3" (20 articulos)');
});

it('lists the tag names of a product in the customer API response', function () {
    $customer = User::factory()->create(['role' => Role::CUSTOMER]);

    $product = attachTags(makeProduct([
        'description' => 'CANASTA "3" (20 articulos)',
        'visibility' => 'visible',
    ]), ['CANASTAS NAVIDEÑAS']);

    $response = $this->actingAs($customer, 'sanctum')
        ->getJson('/api/customer/products/'.$product->id);

    $response->assertStatus(200);

    $payload = $response->json();
    expect($payload['data'] ?? $payload)->toHaveKey('tags_string', 'CANASTAS NAVIDEÑAS');
});
