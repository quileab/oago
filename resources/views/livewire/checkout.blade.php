<?php

use Livewire\Volt\Component;
use Mary\Traits\Toast;

new class extends Component {
    use Toast;
    public $transportation = [
        [
            'id' => 1,
            'name' => 'Transporte Propio',
        ],
        [
            'id' => 2,
            'name' => 'Transporte Tercerizado',
        ],
        [
            'id' => 3,
            'name' => 'Transporte OAgostini',
        ],
    ];

    public $paymentOptions = [
        [
            'id' => 1,
            'name' => 'Contado',
        ],
        [
            'id' => 2,
            'name' => 'Efectivo/Cheque',
        ],
        [
            'id' => 3,
            'name' => 'Transferencia Bancaria',
        ],
    ];

    public $data = [
        'sending_method' => 1,
        'sending_address' => null,
        'sending_city' => null,
        'contact_name' => null,
        'contact_number' => null,
        'transport_detail' => null,
        'payment_method' => null,
        'payment_detail' => null,
        'information' => null,
        'status' => 'pending',
    ];

    public $cart_content = null;
    public $total = 0;

    public function mount()
    {
        // check if cart is empty
        if (session()->get('cart') == null) {
            return redirect('/');
        }
        $this->cart_content = session()->get('cart');
        $this->total = 0;
        foreach ($this->cart_content as $item) {
            $this->total += $item['price'] * $item['quantity'];
        }

        // data address & city take from auth user
        $this->data['sending_address'] = auth()->user()->address;
        $this->data['sending_city'] = auth()->user()->city . ' (' . auth()->user()->postal_code . ')';
    }

    public function save()
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }
        // remove tags & sanitize data from "information"
        $this->data['information'] = strip_tags($this->data['information']);
        // TODO: validate data
        \App\Models\Order::placeOrder(shipping: $this->data);
    }

}; ?>

<div>
    <x-card title="Detalle de la Compra {{ Session::has('updateOrder') ? '#' . Session::get('updateOrder') : '' }}"
        subtitle="Verifique que los datos sean correctos" shadow separator>
        {{-- cart content --}}
        <table class="table-auto md:table-fixed w-full text-xs ">
            <thead class="bg-gray-200/10">
                <tr>
                    <th class="w-1/12">#Cod.</th>
                    <th class="w-3/12">Descripción</th>
                    <th class="w-2/12">Precio</th>
                    <th class="w-2/12">Cantidad</th>
                    <th class="w-2/12">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($cart_content as $item)
                    <tr class="odd:bg-gray-100/5">
                        <td>{{ $item['product_id'] }}</td>
                        <td>{{ $item['name'] }}</td>
                        <td class="text-right">${{ number_format($item['price'], 2) }}</td>
                        <td class="text-center">{{ $item['quantity'] }}</td>
                        <td class="text-right">${{ number_format($item['price'] * $item['quantity'], 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot class="border-b bg-gray-200/10">
                <tr>
                    <td colspan="4" class="text-right font-bold">Total</td>
                    <td class="text-right font-bold">
                        ${{ number_format($total, 2) }}</td>
                </tr>
            </tfoot>
        </table>

        <x-form wire:submit.prevent="save">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-4">
                <x-select label="Transporte a Utilizar" wire:model.lazy="data.sending_method" :options="$transportation"
                    option-value="name" icon="o-truck" class="flex-1" />
                <x-input label="Datos del Transporte" wire:model="data.transport_detail"
                    icon="o-clipboard-document-list" />
            </div>
            @if($data['sending_method'] == 'Transporte OAgostini')
                <x-input label="Dirección de Entrega" wire:model="data.sending_address" icon="o-map-pin" maxlength="100" />
                <x-input label="Ciudad de Entrega" wire:model="data.sending_city" icon="o-map" maxlength="50" />
            @endif
            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-4">
                <x-input label="Persona a Cargo" wire:model="data.contact_name" icon="o-user" />
                <x-input label="Teléfono de Contacto" wire:model="data.contact_number" icon="o-phone" />
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-4">
                <x-select label="Forma de Pago" wire:model="data.payment_method" :options="$paymentOptions"
                    option-value="name" icon="o-currency-dollar" class="flex-1" />
                <x-input label="Detalles del Pago" wire:model="data.payment_detail" icon="o-document-text"
                    class="flex-1" maxlength="100" />
            </div>
            <x-textarea label="Información Adicional" wire:model="data.information" hint="Max 240 caracteres" rows="5"
                maxlength="240" />
            <x-slot:actions>
                <x-button wire:click="save" icon="o-check" class="btn-primary" type="submit" spinner="save"
                    label="Confirmar Pedido" />
            </x-slot:actions>
        </x-form>
    </x-card>
</div>