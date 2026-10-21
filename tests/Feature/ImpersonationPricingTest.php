<?php

use App\Enums\Role;
use App\Livewire\WebNavbar;
use App\Models\CustomerSalesAgent;
use App\Models\ListName;
use App\Models\ListPrice;
use App\Models\Order;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Session;
use Livewire\Livewire;
use Livewire\Volt\Volt;

uses(RefreshDatabase::class);

it('sales impersonation prices the cart with the customer list', function () {
    $customerList = ListName::create(['name' => 'Customer List']);
    $customer = User::factory()->create(['role' => Role::CUSTOMER, 'list_id' => $customerList->id]);
    $sales = User::factory()->create(['role' => Role::SALES]);

    CustomerSalesAgent::create([
        'customer_id' => $customer->id,
        'sales_agent_id' => $sales->id,
        'sales_agent_type' => User::class,
    ]);

    $product = makeProduct(['price' => 10, 'description' => 'Preciado', 'stock' => 100]);
    ListPrice::create([
        'list_id' => $customerList->id,
        'product_id' => $product->id,
        'price' => 50.50,
        'unit_price' => 53,
    ]);

    session()->put('sales_acting_as_customer_id', $customer->id);
    $this->actingAs($sales);

    Volt::test('cart')->dispatch('addToCart', product: $product->id, quantity: 2);

    $cart = Session::get('cart');
    expect($cart[$product->id]['price'])->toBe(50.50);

    // El pedido se factura con la lista del cliente, no con la del vendedor.
    app(OrderService::class)->placeOrder(draftShipping(), redirect: false);

    $order = Order::where('user_id', $customer->id)->firstOrFail();
    expect((float) $order->total_price)->toBe(101.0);
});

it('sales auto-selection does not drag over the sales cart', function () {
    $customer = User::factory()->create(['role' => Role::CUSTOMER]);
    $sales = User::factory()->create(['role' => Role::SALES]);

    CustomerSalesAgent::create([
        'customer_id' => $customer->id,
        'sales_agent_id' => $sales->id,
        'sales_agent_type' => User::class,
    ]);

    $product = makeProduct(['price' => 10]);
    Session::put('cart', [$product->id => [
        'product_id' => $product->id,
        'name' => $product->description,
        'price' => 10.0,
        'bulkQuantity' => 1,
        'quantity' => 3,
    ]]);
    Session::put('updateOrder', 55);

    $this->actingAs($sales);

    Livewire::test(WebNavbar::class);

    // Primer cliente asignado se auto-selecciona y se limpia el carrito del vendedor.
    expect((int) session('sales_acting_as_customer_id'))->toBe($customer->id);
    expect(session('cart'))->toBeNull();
    expect(session('updateOrder'))->toBeNull();
});

it('switching customer keeps an already selected one and clears the cart', function () {
    $sales = User::factory()->create(['role' => Role::SALES]);
    $customer1 = User::factory()->create(['role' => Role::CUSTOMER]);
    $customer2 = User::factory()->create(['role' => Role::CUSTOMER]);
    CustomerSalesAgent::create([
        'customer_id' => $customer1->id,
        'sales_agent_id' => $sales->id,
        'sales_agent_type' => User::class,
    ]);
    CustomerSalesAgent::create([
        'customer_id' => $customer2->id,
        'sales_agent_id' => $sales->id,
        'sales_agent_type' => User::class,
    ]);

    $product = makeProduct(['price' => 5]);
    Session::put('cart', [$product->id => [
        'product_id' => $product->id,
        'name' => $product->description,
        'price' => 5.0,
        'bulkQuantity' => 1,
        'quantity' => 1,
    ]]);

    $this->actingAs($sales);

    Livewire::test(WebNavbar::class)
        ->call('setActingCustomer', $customer2->id);

    // Se cambió de cliente y el carrito quedó limpio (evita mezclar precios de listas).
    expect((int) session('sales_acting_as_customer_id'))->toBe($customer2->id);
    expect(session('cart'))->toBeNull();
});
