<?php

use App\Mail\AltUserWelcomeMail;
use App\Models\AltUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Volt\Volt;

uses(RefreshDatabase::class);

function makeAltUser(array $attributes = []): AltUser
{
    return AltUser::create(array_merge([
        'name' => 'Invitado',
        'lastname' => 'Prueba',
        'email' => 'invitado@example.com',
        'password' => 'secret',
        'role' => 'guest',
        'is_internal' => false,
    ], $attributes));
}

it('generates an activation token and sends it in the welcome email', function () {
    Mail::fake();

    $altUser = makeAltUser();

    Volt::test('users.alts.crud', ['id' => $altUser->id])
        ->call('sendWelcomeEmail')
        ->assertHasNoErrors();

    $altUser->refresh();

    expect($altUser->role->value)->toBe('guest')
        ->and($altUser->activation_token)->not->toBeNull()
        ->and($altUser->activation_token)->toHaveLength(60);

    Mail::assertQueued(AltUserWelcomeMail::class, function (AltUserWelcomeMail $mail) use ($altUser) {
        return $mail->token === $altUser->activation_token
            && $mail->hasTo($altUser->email);
    });
});

it('renders the welcome email with a working activation link', function () {
    $altUser = makeAltUser(['email' => 'link@example.com']);
    $token = str_repeat('a', 60);

    $html = (new AltUserWelcomeMail($altUser, 'temp-pass', $token))->render();

    expect($html)->toContain(url('/activate-account/'.$token));
});
