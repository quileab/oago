<div style="font-family: sans-serif">
  <h3>Pedido # {{ $order->id }} - Cliente #{{ $order->user->id }}</h3>
  Nombre: {{ $order->user->lastname }} {{ $order->user->name }}<br>
  Dirección: {{ $order->user->address }} / {{ $order->user->city }}<br>
  Contacto: 📨{{ $order->user->email }} / 📞 {{ $order->user->phone }}
  <hr>
  Envío: {{ $order->sending_method }}<br>
  @if($order->shipping)
    Dirección: {{ $order->shipping->address }} / {{ $order->shipping->city }}<br>
    Contacto: {{ $order->shipping->contact_name }} / {{ $order->shipping->phone }}
  @else
    Dirección: {{ $order->sending_address }} / {{ $order->sending_city }}<br>
    Contacto: {{ $order->contact_name }} / {{ $order->contact_number }}
  @endif
  <hr>
  Pago: {{ $order->payment_method }}<br>
  Detalle: {{ $order->payment_detail }}<br>
  Observaciones: {!! $order->information !!}
  <hr>
  <table style="width: 100%; border: 1px solid #000; border-collapse: collapse; font-size: 12px;">
    <thead style="border: 1px solid #000; border-collapse: collapse;">
      <tr>
        <th>#</th>
        <th>Producto</th>
        <th>Cantidad</th>
        <th>Precio</th>
        <th>Subtotal</th>
      </tr>
    </thead>
    <tbody style="border: 1px solid #000; border-collapse: collapse;">
      @foreach ($order->items as $item)
        <tr>
        <td>{{ $item->product_id }}</td>
        <td>{{ $item->product?->brand }} : {{ $item->product?->description ?? 'Producto eliminado' }}</td>
        <td style="text-align: center;">{{ $item->quantity }}</td>
        <td style="text-align: right;">$ {{ number_format($item->price, 2) }}</td>
        <td style="text-align: right;">$ {{ number_format($item->quantity * $item->price, 2) }}</td>
        </tr>
    @endforeach
    </tbody>
    <tfoot style="border: 1px solid #000; border-collapse: collapse; font-weight: bold;">
      <tr>
        <td colspan="4" style="text-align: right;">Total</td>
        <td colspan="2" style="text-align: right;">
          $ {{ number_format($order->total_price, 2) }}</td>
      </tr>
    </tfoot>
  </table>

</div>
