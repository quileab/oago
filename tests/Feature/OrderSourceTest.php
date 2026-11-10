<?php

use App\Enums\OrderSource;
use App\Enums\Role;
use App\Models\Order;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\postJson;

uses(RefreshDatabase::class);

function sourceCartItem(array $product, int $quantity = 1): array
{
    return [
        'product_id' => $product['id'],
        'name' => $product['description'],
        'price' => (float) $product['price'],
        'bulkQuantity' => 1,
        'quantity' => $quantity,
    ];
}

it('marks orders created from the mobile API as mobile_api', function () {
    Mail::fake();

    $user = User::factory()->create(['role' => Role::CUSTOMER]);
    $product = makeProduct(['price' => 100, 'stock' => 10, 'qtty_package' => 1]);

    actingAs($user, 'sanctum');

    postJson('/api/customer/orders', [
        'items' => [
            ['product_id' => $product->id, 'quantity' => 2],
        ],
        'shipping' => [
            'sending_method' => 'Envío a cargo de la Empresa a Dirección Registrada',
            'payment_method' => 'Contado / Efectivo',
        ],
    ])->assertSuccessful();

    $order = Order::firstOrFail();

    expect($order->source)->toBe(OrderSource::MOBILE_API)
        ->and($order->getRawOriginal('source'))->toBe('mobile_api');
});

it('defaults orders placed from the website to the web channel', function () {
    Mail::fake();

    $user = User::factory()->create(['role' => Role::CUSTOMER]);
    $product = makeProduct(['price' => 100, 'stock' => 10, 'qtty_package' => 1]);

    Session::put('cart', [$product->id => sourceCartItem($product->toArray(), 2)]);
    $this->actingAs($user);

    app(OrderService::class)->placeOrder(draftShipping(), redirect: false);

    $order = Order::firstOrFail();

    expect($order->source)->toBe(OrderSource::WEB)
        ->and($order->getRawOriginal('source'))->toBe('web');
});

it('keeps the source when an existing order is resumed and saved again', function () {
    Mail::fake();

    $user = User::factory()->create(['role' => Role::CUSTOMER]);
    $product = makeProduct(['price' => 100, 'stock' => 10, 'qtty_package' => 1]);

    $order = Order::create([
        'user_id' => $user->id,
        'total_price' => 100,
        'status' => 'pending',
        'payment_method' => 'Contado / Efectivo',
        'source' => OrderSource::MOBILE_API,
    ]);
    $order->items()->create(['product_id' => $product->id, 'quantity' => 1, 'price' => 100]);

    Session::put('cart', [$product->id => sourceCartItem($product->toArray(), 1)]);
    Session::put('updateOrder', $order->id);
    $this->actingAs($user);

    app(OrderService::class)->placeOrder(draftShipping() + ['status' => 'pending'], redirect: false);

    expect(Order::firstOrFail()->source)->toBe(OrderSource::MOBILE_API);
});
