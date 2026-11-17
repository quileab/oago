<?php

use App\Enums\OrderStatus;
use App\Enums\Role;
use App\Helpers\SettingsHelper;
use App\Models\AltOrder;
use App\Models\AltUser;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\FreightService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;
use Livewire\Volt\Volt;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

uses(RefreshDatabase::class);

it('charges default 4 percent freight as an order item for both company shipping methods', function (string $sendingMethod) {
    $this->artisan('app:sync-settings')->assertSuccessful();

    $user = User::factory()->create(['role' => Role::CUSTOMER]);
    $product = makeProduct(['price' => 1000, 'stock' => 20, 'description' => 'Repuesto A']);

    $this->actingAs($user);
    Session::put('cart', [
        $product->id => [
            'product_id' => $product->id,
            'name' => $product->description,
            'price' => 1000.0,
            'bulkQuantity' => 1,
            'quantity' => 2,
        ],
    ]);

    $shipping = array_merge(draftShipping(), ['sending_method' => $sendingMethod]);
    $order = Order::placeOrder($shipping, redirect: false);

    // Subtotal = 2000; 4% = 80; Total = 2080
    expect((float) $order->total_price)->toBe(2080.0);
    expect($order->items)->toHaveCount(2);

    $freightProductId = (int) SettingsHelper::settings('freight_product_id');
    expect($freightProductId)->toBeGreaterThan(0);

    $freightProduct = Product::findOrFail($freightProductId);
    expect((bool) $freightProduct->published)->toBeFalse();
    expect($freightProduct->visibility)->toBe('hidden');
    expect($freightProduct->model)->toBe('consumo interno');
    expect((bool) $freightProduct->deduct_stock)->toBeFalse();
    expect((int) $freightProduct->stock)->toBe(0);

    $freightItem = $order->items->firstWhere('product_id', $freightProductId);
    expect($freightItem)->not->toBeNull();
    expect($freightItem->quantity)->toBe(1);
    expect((float) $freightItem->price)->toBe(80.0);
})->with([
    'registered address' => 'Envío a cargo de la Empresa a Dirección Registrada',
    'alternative address' => 'Envío a cargo de la Empresa (Dirección Alternativa)',
]);

it('does not charge freight when shipping is not handled by the company', function (string $sendingMethod) {
    $this->artisan('app:sync-settings')->assertSuccessful();

    $user = User::factory()->create(['role' => Role::CUSTOMER]);
    $product = makeProduct(['price' => 1000, 'stock' => 20, 'description' => 'Repuesto B']);

    $this->actingAs($user);
    Session::put('cart', [
        $product->id => [
            'product_id' => $product->id,
            'name' => $product->description,
            'price' => 1000.0,
            'bulkQuantity' => 1,
            'quantity' => 2,
        ],
    ]);

    $shipping = array_merge(draftShipping(), [
        'sending_method' => $sendingMethod,
        'transport_detail' => 'Transporte Test',
    ]);
    $order = Order::placeOrder($shipping, redirect: false);

    expect((float) $order->total_price)->toBe(2000.0);
    expect($order->items)->toHaveCount(1);
})->with([
    'customer pickup' => 'Retiro del Cliente / Transporte Propio',
    'third party' => 'Envío Tercerizado (A designar por el cliente)',
]);

it('applies customer-specific percentage, fixed amount, or zero-exempt freight overrides', function (?string $type, ?float $value, float $expectedTotal, int $expectedItemCount, ?float $expectedFreightPrice) {
    $this->artisan('app:sync-settings')->assertSuccessful();

    $user = User::factory()->create([
        'role' => Role::CUSTOMER,
        'freight_type' => $type,
        'freight_value' => $value,
    ]);
    $product = makeProduct(['price' => 1000, 'stock' => 20, 'description' => 'Repuesto C']);

    $this->actingAs($user);
    Session::put('cart', [
        $product->id => [
            'product_id' => $product->id,
            'name' => $product->description,
            'price' => 1000.0,
            'bulkQuantity' => 1,
            'quantity' => 1,
        ],
    ]);

    $order = Order::placeOrder(draftShipping(), redirect: false);

    expect((float) $order->total_price)->toBe($expectedTotal);
    expect($order->items)->toHaveCount($expectedItemCount);

    if ($expectedFreightPrice !== null) {
        $freightProductId = (int) SettingsHelper::settings('freight_product_id');
        $freightItem = $order->items->firstWhere('product_id', $freightProductId);
        expect($freightItem)->not->toBeNull();
        expect((float) $freightItem->price)->toBe($expectedFreightPrice);
    }
})->with([
    'custom percentage 6%' => ['percentage', 0.06, 1060.0, 2, 60.0],
    'fixed amount $2000' => ['fixed', 2000.0, 3000.0, 2, 2000.0],
    'exempt customer (0)' => ['percentage', 0.0, 1000.0, 1, null],
]);

it('charges freight on AltOrder for AltUser', function () {
    $this->artisan('app:sync-settings')->assertSuccessful();

    $altUser = AltUser::create([
        'name' => 'Invitado',
        'lastname' => 'Prueba',
        'address' => 'Calle 123',
        'city' => 'Santa Fe',
        'postal_code' => '3000',
        'phone' => '3424000000',
        'email' => 'alt-freight@example.com',
        'password' => bcrypt('secret123'),
        'role' => Role::CUSTOMER,
        'freight_type' => 'fixed',
        'freight_value' => 1500,
    ]);

    $product = makeProduct(['price' => 500, 'stock' => 10, 'description' => 'Repuesto Alt']);

    Auth::guard('alt')->login($altUser);
    Session::put('cart', [
        $product->id => [
            'product_id' => $product->id,
            'name' => $product->description,
            'price' => 500.0,
            'bulkQuantity' => 1,
            'quantity' => 2,
        ],
    ]);

    $order = AltOrder::placeOrder(draftShipping(), redirect: false);

    // Subtotal 1000 + flete fijo 1500 = 2500
    expect((float) $order->total_price)->toBe(2500.0);
    expect($order->items)->toHaveCount(2);

    $freightProductId = (int) SettingsHelper::settings('freight_product_id');
    $freightItem = $order->items->firstWhere('product_id', $freightProductId);
    expect($freightItem)->not->toBeNull();
    expect((float) $freightItem->price)->toBe(1500.0);
});

it('charges freight on mobile API placeOrder and recalculates on API updateOrderProducts', function () {
    $this->artisan('app:sync-settings')->assertSuccessful();

    $admin = User::factory()->create(['role' => Role::ADMIN]);
    $customer = User::factory()->create(['role' => Role::CUSTOMER]);
    $product = makeProduct(['price' => 1000, 'stock' => 50, 'description' => 'Repuesto API']);

    actingAs($customer, 'sanctum');

    $placeResponse = postJson('/api/customer/orders', [
        'items' => [
            ['product_id' => $product->id, 'quantity' => 2],
        ],
        'shipping' => [
            'sending_method' => 'Envío a cargo de la Empresa a Dirección Registrada',
            'payment_method' => 'Contado / Efectivo',
        ],
    ]);

    $placeResponse->assertCreated();
    $orderId = $placeResponse->json('order.id');

    $order = Order::with('items')->findOrFail($orderId);
    // 2 * 1000 = 2000 + 4% (80) = 2080
    expect((float) $order->total_price)->toBe(2080.0);
    expect($order->items)->toHaveCount(2);

    $freightProductId = (int) SettingsHelper::settings('freight_product_id');

    // Admin updates products via API including the existing freight item in payload
    actingAs($admin, 'sanctum');

    $updateResponse = putJson("/api/orders/{$order->id}/products", [
        'items' => [
            ['product_id' => $product->id, 'quantity' => 5, 'price' => 1000],
            ['product_id' => $freightProductId, 'quantity' => 1, 'price' => 80],
        ],
    ]);

    $updateResponse->assertSuccessful();

    $order->refresh();
    // New subtotal 5000 + 4% (200) = 5200
    expect((float) $order->total_price)->toBe(5200.0);
    expect($order->items)->toHaveCount(2);

    $freightItem = $order->items->firstWhere('product_id', $freightProductId);
    expect($freightItem)->not->toBeNull();
    expect((float) $freightItem->price)->toBe(200.0);
});

it('validates and stores freight configuration via users API including flete alias', function () {
    $admin = User::factory()->create(['role' => Role::ADMIN]);
    actingAs($admin, 'sanctum');

    // Explicit percentage + value
    postJson('/api/users', [
        'id' => 9001,
        'name' => 'Juan',
        'lastname' => 'Perez',
        'email' => 'juan9001@example.com',
        'role' => 'customer',
        'freight_type' => 'percentage',
        'freight_value' => 0.05,
    ])->assertCreated();

    $user1 = User::findOrFail(9001);
    expect($user1->freight_type)->toBe('percentage');
    expect((float) $user1->freight_value)->toBe(0.05);

    // Alias 'flete' with fixed amount > 1 infers 'fixed'
    putJson("/api/users/{$user1->id}", [
        'flete' => 2000,
    ])->assertSuccessful();

    $user1->refresh();
    expect($user1->freight_type)->toBe('fixed');
    expect((float) $user1->freight_value)->toBe(2000.0);

    // Percentage > 1 fails validation
    putJson("/api/users/{$user1->id}", [
        'freight_type' => 'percentage',
        'freight_value' => 1.5,
    ])->assertUnprocessable();
});

it('updates freight dynamically in checkout component and excludes freight item on loadCart', function () {
    $this->artisan('app:sync-settings')->assertSuccessful();

    $user = User::factory()->create(['role' => Role::CUSTOMER]);
    $product = makeProduct(['price' => 1000, 'stock' => 20, 'description' => 'Repuesto UI']);

    $this->actingAs($user);
    Session::put('cart', [
        $product->id => [
            'product_id' => $product->id,
            'name' => $product->description,
            'price' => 1000.0,
            'bulkQuantity' => 1,
            'quantity' => 2,
        ],
    ]);

    Volt::test('checkout')
        ->assertSet('freightAmount', 80.0)
        ->assertSet('freightLabel', 'Flete (4%)')
        ->assertSet('grandTotal', 2080.0)
        ->set('data.sending_method', 'Retiro del Cliente / Transporte Propio')
        ->assertSet('freightAmount', 0.0)
        ->assertSet('grandTotal', 2000.0);

    // Place order with freight, then resume/copy via orderitems and ensure freight is excluded from cart
    $freightProduct = app(FreightService::class)->getOrCreateFreightProduct();
    $order = Order::create([
        'user_id' => $user->id,
        'total_price' => 2080.0,
        'sending_method' => 'Envío a cargo de la Empresa a Dirección Registrada',
        'payment_method' => 'Contado / Efectivo',
        'status' => OrderStatus::SAVED,
    ]);
    $order->items()->create(['product_id' => $product->id, 'quantity' => 2, 'price' => 1000.0]);
    $order->items()->create(['product_id' => $freightProduct->id, 'quantity' => 1, 'price' => 80.0]);

    Session::forget('cart');

    Volt::test('orderitems', ['orderId' => $order->id])
        ->call('loadCart', true);

    $resumedCart = Session::get('cart');
    expect($resumedCart)->toHaveKey($product->id);
    expect($resumedCart)->not->toHaveKey($freightProduct->id);
    expect($resumedCart)->toHaveCount(1);
});

it('allows purchasing non-stock-deducting services at zero stock and excludes them from percentage freight base', function () {
    $this->artisan('app:sync-settings')->assertSuccessful();

    $user = User::factory()->create(['role' => Role::CUSTOMER]);
    $physical = makeProduct([
        'price' => 1000,
        'stock' => 10,
        'in_stock' => true,
        'deduct_stock' => true,
        'description' => 'Batería Física',
    ]);
    $service = makeProduct([
        'price' => 500,
        'stock' => 0,
        'in_stock' => true,
        'deduct_stock' => false,
        'allow_reservation' => false,
        'description' => 'Servicio Instalación',
    ]);

    expect($service->isOutOfStock())->toBeFalse();
    expect($service->hasAvailableStock(5))->toBeTrue();
    expect($service->stockBadge()['label'])->toBe('DISPONIBLE');

    $this->actingAs($user);
    Session::put('cart', [
        $physical->id => [
            'product_id' => $physical->id,
            'name' => $physical->description,
            'price' => 1000.0,
            'bulkQuantity' => 1,
            'quantity' => 1,
        ],
        $service->id => [
            'product_id' => $service->id,
            'name' => $service->description,
            'price' => 500.0,
            'bulkQuantity' => 1,
            'quantity' => 1,
        ],
    ]);

    // Checkout UI calculates 4% only on $1000 physical product = $40 (not $1500 = $60)
    Volt::test('checkout')
        ->assertSet('freightAmount', 40.0)
        ->assertSet('grandTotal', 1540.0);

    $order = Order::placeOrder(draftShipping(), redirect: false);
    expect((float) $order->total_price)->toBe(1540.0);
    expect($order->items)->toHaveCount(3);

    $freightProductId = (int) SettingsHelper::settings('freight_product_id');
    $freightItem = $order->items->firstWhere('product_id', $freightProductId);
    expect((float) $freightItem->price)->toBe(40.0);

    // Mobile API also allows zero-stock service and computes 4% only on physical product
    actingAs($user, 'sanctum');
    $apiResponse = postJson('/api/customer/orders', [
        'items' => [
            ['product_id' => $physical->id, 'quantity' => 1],
            ['product_id' => $service->id, 'quantity' => 1],
        ],
        'shipping' => [
            'sending_method' => 'Envío a cargo de la Empresa a Dirección Registrada',
            'payment_method' => 'Contado / Efectivo',
        ],
    ]);

    $apiResponse->assertCreated();
    $apiOrder = Order::with('items')->findOrFail($apiResponse->json('order.id'));
    expect((float) $apiOrder->total_price)->toBe(1540.0);
});

it('enforces in_stock and allow_reservation rules for physical products', function () {
    $this->artisan('app:sync-settings')->assertSuccessful();

    $user = User::factory()->create(['role' => Role::CUSTOMER]);
    $outOfStockProduct = makeProduct([
        'price' => 1000,
        'stock' => 0,
        'in_stock' => true,
        'deduct_stock' => true,
        'allow_reservation' => false,
        'description' => 'Sin Stock Sin Reserva',
    ]);
    $disabledInStockProduct = makeProduct([
        'price' => 1000,
        'stock' => 20,
        'in_stock' => false,
        'deduct_stock' => true,
        'allow_reservation' => false,
        'description' => 'Deshabilitado In Stock',
    ]);
    $reservableProduct = makeProduct([
        'price' => 1000,
        'stock' => 0,
        'in_stock' => true,
        'deduct_stock' => true,
        'allow_reservation' => true,
        'description' => 'Repuesto A Pedido',
    ]);

    expect($outOfStockProduct->isOutOfStock())->toBeTrue();
    expect($outOfStockProduct->stockBadge()['label'])->toBe('AGOTADO');

    expect($disabledInStockProduct->isOutOfStock())->toBeTrue();
    expect($disabledInStockProduct->stockBadge()['label'])->toBe('AGOTADO');

    expect($reservableProduct->isOutOfStock())->toBeFalse();
    expect($reservableProduct->hasAvailableStock(3))->toBeTrue();
    expect($reservableProduct->stockBadge()['label'])->toBe('A PEDIDO');

    $this->actingAs($user);

    // Out of stock fails
    Session::put('cart', [
        $outOfStockProduct->id => [
            'product_id' => $outOfStockProduct->id,
            'name' => $outOfStockProduct->description,
            'price' => 1000.0,
            'bulkQuantity' => 1,
            'quantity' => 1,
        ],
    ]);
    expect(fn () => Order::placeOrder(draftShipping(), redirect: false))
        ->toThrow(ValidationException::class);

    // in_stock = false fails even with stock > 0
    Session::put('cart', [
        $disabledInStockProduct->id => [
            'product_id' => $disabledInStockProduct->id,
            'name' => $disabledInStockProduct->description,
            'price' => 1000.0,
            'bulkQuantity' => 1,
            'quantity' => 1,
        ],
    ]);
    expect(fn () => Order::placeOrder(draftShipping(), redirect: false))
        ->toThrow(ValidationException::class);

    // allow_reservation = true succeeds with stock = 0
    Session::put('cart', [
        $reservableProduct->id => [
            'product_id' => $reservableProduct->id,
            'name' => $reservableProduct->description,
            'price' => 1000.0,
            'bulkQuantity' => 1,
            'quantity' => 2,
        ],
    ]);
    $order = Order::placeOrder(draftShipping(), redirect: false);
    expect((float) $order->total_price)->toBe(2080.0);
});

it('enforces freight_min_amount and free_freight_threshold settings', function () {
    $this->artisan('app:sync-settings')->assertSuccessful();

    $user = User::factory()->create(['role' => Role::CUSTOMER]);
    $product = makeProduct(['price' => 25000, 'stock' => 20, 'deduct_stock' => true, 'description' => 'Producto Test']);

    // 4% of 25000 = 1000, but min_amount is 5000 -> freight becomes 5000
    SettingsHelper::update_setting('freight_min_amount', 5000);
    SettingsHelper::update_setting('free_freight_threshold', 100000);

    $this->actingAs($user);
    Session::put('cart', [
        $product->id => [
            'product_id' => $product->id,
            'name' => $product->description,
            'price' => 25000.0,
            'bulkQuantity' => 1,
            'quantity' => 1,
        ],
    ]);

    Volt::test('checkout')
        ->assertSet('freightAmount', 5000.0)
        ->assertSet('freightLabel', 'Flete (4% - Mínimo)')
        ->assertSet('grandTotal', 30000.0);

    // Purchase >= 100000 (4 * 25000) -> free freight threshold applies ($0)
    Session::put('cart.'.$product->id.'.quantity', 4);
    Volt::test('checkout')
        ->assertSet('freightAmount', 0.0)
        ->assertSet('grandTotal', 100000.0);

    $order = Order::placeOrder(draftShipping(), redirect: false);
    expect((float) $order->total_price)->toBe(100000.0);
    expect($order->items)->toHaveCount(1);
});

it('protects system freight product from deletion and exposes effective_freight on /api/user', function () {
    $this->artisan('app:sync-settings')->assertSuccessful();
    SettingsHelper::update_setting('freight_min_amount', 5000);
    SettingsHelper::update_setting('free_freight_threshold', 150000);

    $admin = User::factory()->create(['role' => Role::ADMIN]);
    $customer = User::factory()->create(['role' => Role::CUSTOMER]);
    $freightProduct = app(FreightService::class)->getOrCreateFreightProduct();

    expect($freightProduct->isSystemProduct())->toBeTrue();

    // API DELETE rejects system product with 422
    actingAs($admin, 'sanctum');
    deleteJson("/api/products/{$freightProduct->id}")
        ->assertStatus(422);
    expect(Product::find($freightProduct->id))->not->toBeNull();

    // Volt CRUD delete rejects system product and preserves it
    $this->actingAs($admin);
    Volt::test('products.crud', ['id' => $freightProduct])
        ->call('delete')
        ->assertSee('Producto de Sistema (No eliminable)');
    expect(Product::find($freightProduct->id))->not->toBeNull();

    // GET /api/user includes resolved effective_freight metadata
    actingAs($customer, 'sanctum');
    getJson('/api/user')
        ->assertOk()
        ->assertJsonPath('effective_freight.type', 'percentage')
        ->assertJsonPath('effective_freight.value', 0.04)
        ->assertJsonPath('effective_freight.min_amount', 5000)
        ->assertJsonPath('effective_freight.free_threshold', 150000)
        ->assertJsonPath('effective_freight.label', 'Flete (4%)');
});
