<?php

use App\Models\Product;
use App\Models\Tag;
use Illuminate\Support\Str;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(TestCase::class)
 // ->use(Illuminate\Foundation\Testing\RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}

/**
 * Build a valid product, since most product fields are required by the schema.
 *
 * @param  array<string, mixed>  $overrides
 */
function makeProduct(array $overrides = []): Product
{
    return Product::create(array_merge([
        'description' => 'Test Product',
        'price' => 100,
        'stock' => 10,
        'qtty_package' => 1,
        'published' => 1,
        'visibility' => 'visible',
        'tax_status' => 'taxable',
    ], $overrides));
}

/**
 * Attach tags by their display name, the way the admin and the API do.
 *
 * @param  array<int, string>  $names
 */
function attachTags(Product $product, array $names): Product
{
    foreach ($names as $name) {
        Tag::fromName($name);
    }

    $product->syncTagsBySlug(array_map(
        fn (string $name) => Str::slug($name),
        $names
    ));

    return $product;
}
