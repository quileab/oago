<?php

use Livewire\Volt\Component;
use Mary\Traits\Toast;

new class extends Component {
    use Toast;
    public $transportation = [
        [
            'id' => 1,
            'name' => 'Envío a cargo de la Empresa a Dirección Registrada',
        ],
        [
            'id' => 2,
            'name' => 'Envío a cargo de la Empresa (Dirección Alternativa)',
        ],
        [
            'id' => 3,
            'name' => 'Retiro del Cliente / Transporte Propio',
        ],
        [
            'id' => 4,
            'name' => 'Envío Tercerizado (A designar por el cliente)',
        ],
    ];

    public $paymentOptions = [
        [
            'id' => 1,
            'name' => 'Contado / Efectivo',
        ],
        [
            'id' => 2,
            'name' => 'Cheque / Valores',
        ],
        [
            'id' => 3,
            'name' => 'Transferencia Bancaria',
        ],
    ];

    public $data = [
        'sending_method' => 'Envío a cargo de la Empresa a Dirección Registrada',
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

        $productIds = array_column($this->cart_content, 'product_id');
        $products = \App\Models\Product::whereIn('id', $productIds)->get()->keyBy('id');

        foreach ($this->cart_content as $productId => $item) {
            $product = $products->get($item['product_id']);
            $orderedQuantity = (int) $item['quantity'];
            $billableQuantity = $orderedQuantity;

            if ($product && $product->hasBonus() && $product->bonus_threshold > 0) {
                $bonusThreshold = $product->bonus_threshold + $product->bonus_amount;
                $timesBonusApplies = floor($orderedQuantity / $bonusThreshold);
                $freeUnits = $timesBonusApplies * $product->bonus_amount;
                $billableQuantity = $orderedQuantity - $freeUnits;
            }

            $itemSubtotal = (float) $item['price'] * $billableQuantity;
            
            // Enrich item
            $this->cart_content[$productId]['billable_quantity'] = $billableQuantity;
            $this->cart_content[$productId]['subtotal'] = $itemSubtotal;
            $this->cart_content[$productId]['is_bonus_applied'] = $orderedQuantity > $billableQuantity;

            $this->total += $itemSubtotal;
        }

        // data address & city take from auth user
        $currentUser = current_user();
        $this->data['sending_address'] = $currentUser->address;
        $this->data['sending_city'] = $currentUser->city . ' (' . $currentUser->postal_code . ')';
        
        // set default payment method
        $this->data['payment_method'] = 'Contado / Efectivo';
    }

    public function save()
    {
        if (!current_user()) {
            return redirect()->route('login');
        }

        $rules = [
            'data.sending_method' => 'required|string',
            'data.payment_method' => 'required|string',
            'data.information' => 'nullable|string|max:240',
            'data.payment_detail' => 'nullable|string|max:100',
        ];

        // Conditional rules for transportation
        if (($this->data['sending_method'] ?? '') !== 'Envío a cargo de la Empresa a Dirección Registrada') {
            $rules['data.transport_detail'] = 'required|string|max:100';
        }

        // Conditional rules for alternative address
        if (($this->data['sending_method'] ?? '') === 'Envío a cargo de la Empresa (Dirección Alternativa)') {
            $rules['data.contact_name'] = 'required|string|max:100';
            $rules['data.contact_number'] = 'required|string|max:50';
            $rules['data.sending_address'] = 'required|string|max:100';
            $rules['data.sending_city'] = 'required|string|max:50';
        }

        $this->validate($rules);

        // remove tags & sanitize data from "information"
        $this->data['information'] = strip_tags($this->data['information'] ?? '');
        
        try {
            if (Auth::guard('alt')->check()) {
                \App\Models\AltOrder::placeOrder(shipping: $this->data);
            } else {
                \App\Models\Order::placeOrder(shipping: $this->data);
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->error($e->getMessage(), position: 'toast-bottom');
        }
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
                        <td>
                            {{ $item['name'] }}
                            @if ($item['is_bonus_applied'] ?? false)
                                <span class="ml-2 badge badge-error badge-xs text-[9px] font-bold uppercase">Bonificado</span>
                            @endif
                        </td>
                        <td class="text-right">${{ number_format($item['price'], 2) }}</td>
                        <td class="text-center">{{ $item['quantity'] }}</td>
                        <td class="text-right font-bold text-green-600">${{ number_format($item['subtotal'], 2) }}</td>
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
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="flex flex-col">
                    <x-select label="Transporte a Utilizar" wire:model.live="data.sending_method" :options="$transportation"
                        option-value="name" icon="o-truck" class="flex-1" />

                    @if($data['sending_method'] == 'Envío a cargo de la Empresa a Dirección Registrada')
                        <div class="mt-2 p-4 bg-primary/5 rounded-xl border border-primary/10 flex items-start gap-3">
                            <x-icon name="o-information-circle" class="w-5 h-5 text-primary mt-0.5" />
                            <p class="text-sm leading-relaxed">
                                <span class="font-black uppercase text-[10px] tracking-tighter block mb-1">Entrega en domicilio registrado:</span>
                                <span class="italic opacity-80">{{ current_user()->address }}, {{ current_user()->city }} ({{ current_user()->postal_code }})</span>
                            </p>
                        </div>
                    @endif
                </div>

                @if($data['sending_method'] !== 'Envío a cargo de la Empresa a Dirección Registrada')
                    <x-input label="Datos del Transporte" wire:model="data.transport_detail"
                        icon="o-clipboard-document-list" placeholder="Ej: Comisionista, Empresa de transporte, etc." />
                @endif
            </div>

            @if($data['sending_method'] == 'Envío a cargo de la Empresa (Dirección Alternativa)')
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 p-6 rounded-2xl border border-base-300 mt-2 bg-base-200/20">
                    <div class="col-span-full font-black text-[10px] uppercase tracking-widest opacity-50">Dirección de entrega alternativa</div>
                    <x-input label="Nombre de Contacto" wire:model="data.contact_name" icon="o-user" placeholder="Persona que recibe" />
                    <x-input label="Teléfono de Contacto" wire:model="data.contact_number" icon="o-phone" />
                    <x-input label="Dirección de Entrega" wire:model="data.sending_address" icon="o-map-pin" maxlength="100" class="md:col-span-1" />
                    <x-input label="Ciudad de Entrega" wire:model="data.sending_city" icon="o-map" maxlength="50" class="md:col-span-1" />
                </div>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-select label="Forma de Pago" wire:model.live="data.payment_method" :options="$paymentOptions"
                    option-value="name" icon="o-currency-dollar" class="flex-1" />
                <x-input label="Detalles del Pago" wire:model="data.payment_detail" icon="o-document-text"
                    class="flex-1" maxlength="100" placeholder="Aclaraciones sobre el pago..." />
            </div>

            <x-textarea label="Información Adicional para el Pedido" wire:model="data.information" hint="Máximo 240 caracteres" rows="3"
                maxlength="240" />

            <x-slot:actions>
                <x-button wire:click="save" icon="o-check" class="btn-primary w-full md:w-auto px-12" type="submit" 
                    spinner="save" wire:loading.attr="disabled"
                    label="Confirmar Pedido" />
            </x-slot:actions>
        </x-form>
    </x-card>
</div>