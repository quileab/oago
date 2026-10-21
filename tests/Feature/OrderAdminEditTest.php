<?php

use App\Enums\OrderStatus;
use App\Enums\Role;
use App\Models\CustomerSalesAgent;
use App\Models\ListName;
use App\Models\ListPrice;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Session;
use Livewire\Volt\Volt;

uses(RefreshDatabase::class);

function editTargetFor(User $owner): Product
{
    $list = ListName::create(['name' => 'Lista Cliente']);
    $owner->update(['list_id' => $list->id]);
    $product = makeProduct(['description' => 'Preciado', 'price' => 90, 'stock' => 100]);
    ListPrice::create(['list_id' => $list->id, 'product_id' => $product->id, 'price' => 44.50, 'unit_price' => 50]);

    return $product;
}

it('an admin editing a customer order keeps the owner and bills with the owner list', function () {
    $owner = User::factory()->create(['role' => Role::CUSTOMER, 'address' => 'Calle Dueño 1', 'city' => 'Ciudad A', 'postal_code' => '3000']);
    $admin = User::factory()->create(['role' => Role::ADMIN]);
    $product = editTargetFor($owner);

    $order = Order::create([
        'user_id' => $owner->id,
        'total_price' => 100,
        'status' => 'pending',
        'payment_method' => 'Contado / Efectivo',
    ]);

    $this->actingAs($admin);
    Session::put('updateOrder', $order->id);
    // Precio viejo del admin (distinto del dueño): el drift no debe bloquear al admin.
    Session::put('cart', [$product->id => [
        'product_id' => $product->id,
        'name' => 'Preciado',
        'price' => 10.0,
        'bulkQuantity' => 1,
        'quantity' => 2,
    ]]);

    app(OrderService::class)->placeOrder(draftShipping(), redirect: false);

    $order->refresh();
    expect((int) $order->user_id)->toBe($owner->id);
    expect($order->status)->toBe(OrderStatus::PENDING);
    // Precios con la lista del dueño: 2 x 44.50.
    expect((float) $order->total_price)->toBe(89.0);
});

it('a sales agent can open and print an order of any managed customer', function () {
    $owner = User::factory()->create(['role' => Role::CUSTOMER]);
    $other = User::factory()->create(['role' => Role::CUSTOMER]);
    $sales = User::factory()->create(['role' => Role::SALES]);
    CustomerSalesAgent::create([
        'customer_id' => $owner->id,
        'sales_agent_id' => $sales->id,
        'sales_agent_type' => User::class,
    ]);

    $order = Order::create([
        'user_id' => $owner->id,
        'total_price' => 100,
        'status' => 'pending',
        'payment_method' => 'Contado / Efectivo',
    ]);

    $this->actingAs($sales);

    // Sin suplantación: el pedido de un cliente gestionado es accesible.
    Volt::test('orderitems', ['orderId' => $order->id]);
    $this->get(route('order.print', $order->id))->assertOk();

    // El pedido de un cliente NO gestionado sigue bloqueado.
    $foreignOrder = Order::create([
        'user_id' => $other->id,
        'total_price' => 100,
        'status' => 'pending',
        'payment_method' => 'Contado / Efectivo',
    ]);

    Volt::test('orderitems', ['orderId' => $foreignOrder->id])
        ->assertForbidden();
    $this->get(route('order.print', $foreignOrder->id))->assertForbidden();
});
