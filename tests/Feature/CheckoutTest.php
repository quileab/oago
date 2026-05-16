<?php

use App\Enums\Role;
use App\Models\User;
use Illuminate\Support\Facades\Session;
use Livewire\Volt\Volt;

it('requires shipping and payment methods', function () {
    $user = User::factory()->create(['role' => Role::CUSTOMER]);

    Session::put('cart', [
        1 => [
            'product_id' => 1,
            'name' => 'Test Product',
            'price' => 100,
            'quantity' => 1,
        ],
    ]);

    $this->actingAs($user);

    Volt::test('checkout')
        ->set('data.sending_method', '')
        ->set('data.payment_method', '')
        ->call('save')
        ->assertHasErrors([
            'data.sending_method' => 'required',
            'data.payment_method' => 'required',
        ]);
});

it('requires transport details if not default shipping', function () {
    $user = User::factory()->create(['role' => Role::CUSTOMER]);

    Session::put('cart', [
        1 => [
            'product_id' => 1,
            'name' => 'Test Product',
            'price' => 100,
            'quantity' => 1,
        ],
    ]);

    $this->actingAs($user);

    Volt::test('checkout')
        ->set('data.sending_method', 'Retiro en Depósito')
        ->set('data.transport_detail', '')
        ->call('save')
        ->assertHasErrors(['data.transport_detail' => 'required']);
});

it('requires alternative address details if selected', function () {
    $user = User::factory()->create(['role' => Role::CUSTOMER]);

    Session::put('cart', [
        1 => [
            'product_id' => 1,
            'name' => 'Test Product',
            'price' => 100,
            'quantity' => 1,
        ],
    ]);

    $this->actingAs($user);

    Volt::test('checkout')
        ->set('data.sending_method', 'Envío a cargo de la Empresa (Dirección Alternativa)')
        ->set('data.contact_name', '')
        ->set('data.contact_number', '')
        ->set('data.sending_address', '')
        ->set('data.sending_city', '')
        ->call('save')
        ->assertHasErrors([
            'data.contact_name' => 'required',
            'data.contact_number' => 'required',
            'data.sending_address' => 'required',
            'data.sending_city' => 'required',
        ]);
});
