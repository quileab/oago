<?php

use App\Helpers\SettingsHelper;
use App\Mail\OrderMail;
use App\Models\Order;
use App\Models\User;
use Illuminate\Contracts\Mail\Factory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;

uses(RefreshDatabase::class);

function createOrderMail(array $orderAttributes): OrderMail
{
    $user = User::factory()->create();
    $order = Order::create([
        'user_id' => $user->id,
        'total_price' => 100,
    ]);

    if (isset($orderAttributes['created_at'])) {
        $order = $order->forceFill($orderAttributes);
        $order->save();
    }

    return new OrderMail($order->id, false);
}

it('marks orders older than the configured limit as stale', function () {
    $mail = createOrderMail(['created_at' => now()->subDays(30)]);

    expect($mail->isStale())->toBeTrue();
});

it('marks fresh orders as not stale', function () {
    $mail = createOrderMail(['created_at' => now()]);

    expect($mail->isStale())->toBeFalse();
});

it('respects the setting threshold', function () {
    SettingsHelper::update_setting('order_mail_stale_days', 30);

    $mail = createOrderMail(['created_at' => now()->subDays(10)]);

    expect($mail->isStale())->toBeFalse();
});

it('disables the guard when the setting is zero or negative', function () {
    SettingsHelper::update_setting('order_mail_stale_days', 0);

    $mail = createOrderMail(['created_at' => now()->subDays(300)]);

    expect($mail->isStale())->toBeFalse();
});

it('skips send() for stale orders without dispatching the mail', function () {
    $mail = createOrderMail(['created_at' => now()->subDays(30)]);

    $sent = $mail->to('test@example.com')->send(app(Factory::class));

    expect($sent)->toBeNull();
});

it('sends fresh orders through the real send path', function () {
    Config::set('mail.default', 'array');

    $mail = createOrderMail(['created_at' => now()]);

    $sent = $mail->to('test@example.com')->send(app(Factory::class));

    expect($sent)->not->toBeNull();
});
