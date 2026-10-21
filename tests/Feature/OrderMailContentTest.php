<?php

use App\Enums\Role;
use App\Mail\OrderMail;
use App\Models\Order;
use App\Models\Product;
use App\Models\ShippingDetail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

function mailOrderWithShipping(User $user, Product $product): Order
{
    $order = Order::create([
        'user_id' => $user->id,
        'total_price' => 200,
        'status' => 'pending',
        'payment_method' => 'Contado / Efectivo',
        'sending_method' => 'Envío a cargo de la Empresa a Dirección Registrada',
    ]);

    $order->items()->create([
        'product_id' => $product->id,
        'quantity' => 2,
        'price' => 100,
    ]);

    ShippingDetail::create([
        'order_id' => $order->id,
        'contact_name' => 'Contacto Especial',
        'address' => 'Calle Falsa 123',
        'city' => 'Ciudad Ejemplo',
        'phone' => '555-0001',
        'shipping_status' => 'pending',
    ]);

    return $order->refresh();
}

it('renders the shipping address and items inside the email body', function () {
    Mail::fake();

    $user = User::factory()->create(['role' => Role::CUSTOMER]);
    $product = makeProduct(['description' => 'Producto visible en mail', 'price' => 100]);
    $order = mailOrderWithShipping($user, $product);

    $html = (new OrderMail($order->id))->render();

    expect($html)->toContain('Calle Falsa 123');
    expect($html)->toContain('Contacto Especial');
    expect($html)->toContain('Producto visible en mail');
});

it('renders error-safe when an item product no longer exists', function () {
    Mail::fake();

    $user = User::factory()->create(['role' => Role::CUSTOMER]);
    $product = makeProduct(['description' => 'Producto por borrar', 'price' => 100]);
    $order = mailOrderWithShipping($user, $product);

    // Con la FK onDelete(cascade), eliminar el producto elimina también
    // sus order_items; el mail debe renderizarse sin error y sin filas.
    $product->delete();
    expect($order->fresh()->items()->count())->toBe(0);

    $html = (new OrderMail($order->id))->render();

    expect($html)->toContain('Calle Falsa 123');
    expect($html)->not->toContain('Producto por borrar');
});
