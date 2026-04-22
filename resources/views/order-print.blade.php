<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comprobante de Pedido #{{ $isAlt ? 'ALT-' : '' }}{{ $order->id }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 1cm;
        }
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            color: #333; 
            line-height: 1.4; 
            padding: 0; 
            margin: 0;
            background: white; 
        }
        .container { 
            width: 100%; 
            max-width: 210mm; /* Ancho A4 */
            margin: 0 auto; 
            padding: 10mm;
        }
        .header { 
            display: flex; 
            justify-content: space-between; 
            padding-bottom: 10px; 
            margin-bottom: 20px; 
            align-items: flex-end;
        }
        .logo { 
            height: 2cm; /* Requisito: Max 2cm de alto */
            width: auto;
            object-fit: contain;
        }
        .order-info { text-align: right; }
        .order-info h1 { margin: 0; color: #002b6b; font-size: 20px; text-transform: uppercase; }
        .details-grid { 
            display: grid; 
            grid-template-columns: 1fr 1fr; 
            gap: 20px; 
            margin-bottom: 30px; 
        }
        .details-box h3 { 
            border-bottom: 1px solid #ddd; 
            text-transform: uppercase; 
            font-size: 11px; 
            color: #666; 
            margin-bottom: 5px; 
            padding-bottom: 2px;
        }
        .details-box p { margin: 2px 0; font-size: 13px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th { 
            background: #f1f5f9; 
            text-align: left; 
            padding: 8px 5px; 
            border: 1px solid #e2e8f0; 
            font-size: 11px; 
            text-transform: uppercase; 
        }
        td { 
            padding: 6px 5px; 
            border: 1px solid #e2e8f0; 
            font-size: 12px; 
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .total-row { background: #f8f9fa; font-weight: bold; font-size: 16px; }
        .footer { 
            margin-top: 30px; 
            text-align: center; 
            font-size: 11px; 
            color: #999; 
            padding-top: 10px; 
        }
        .btn-print { 
            background: #002b6b; 
            color: white; 
            border: none; 
            padding: 8px 16px; 
            border-radius: 4px; 
            cursor: pointer; 
            font-weight: bold; 
            margin: 20px 0;
            text-transform: uppercase;
        }
        @media print { 
            .btn-print { display: none; } 
            .container { border: none; padding: 5mm; }
            body { padding: 0; }
        }
    </style>
</head>
<body>
    <div class="text-center">
        <button class="btn-print" onclick="window.print()">IMPRIMIR COMPROBANTE</button>
    </div>

    <div class="container">
        <div class="header">
            <div>
                <img src="{{ asset('imgs/brand-logo.webp') }}" class="logo" alt="Logo">
                <p style="margin-top: 10px; font-size: 10px; color: #666;">DOCUMENTO NO VÁLIDO COMO FACTURA</p>
            </div>
            <div class="order-info">
                <h1>Pedido #{{ $isAlt ? 'ALT-' : '' }}{{ $order->id }}</h1>
                <p><strong>Fecha:</strong> {{ $order->created_at->format('d/m/Y H:i') }}</p>
                <p><strong>Estado:</strong> {{ \App\Models\Order::orderStates($order->status) }}</p>
            </div>
        </div>

        <div class="details-grid">
            <div class="details-box">
                <h3>Datos del Cliente</h3>
                <p><strong>{{ $order->user->lastname }}, {{ $order->user->name }}</strong></p>
                <p>{{ $order->user->address }}</p>
                <p>{{ $order->user->city }} ({{ $order->user->postal_code }})</p>
                <p>Tel: {{ $order->user->phone }}</p>
                <p>Email: {{ $order->user->email }}</p>
            </div>
            <div class="details-box">
                <h3>Información de Entrega y Pago</h3>
                <p><strong>Método de Envío:</strong> {{ $order->sending_method ?? 'No especificado' }}</p>
                
                @if($order->shipping)
                    <p><strong>Contacto:</strong> {{ $order->shipping->contact_name ?? $order->user->name }}</p>
                    <p><strong>Dirección:</strong> {{ $order->shipping->address }}, {{ $order->shipping->city }}</p>
                    <p><strong>Tel. Entrega:</strong> {{ $order->shipping->phone }}</p>
                @elseif($order->sending_method == 'Envío a cargo de la Empresa a Dirección Registrada')
                    <p><strong>Contacto:</strong> {{ $order->user->name }}</p>
                    <p><strong>Dirección:</strong> {{ $order->user->address }}, {{ $order->user->city }} ({{ $order->user->postal_code }})</p>
                    <p><strong>Tel. Entrega:</strong> {{ $order->user->phone }}</p>
                @endif

                <p style="margin-top: 10px;"><strong>Forma de Pago:</strong> {{ $order->payment_method ?? 'No especificado' }}</p>
                @if($order->payment_detail)
                    <p><strong>Detalle Pago:</strong> {{ $order->payment_detail }}</p>
                @endif
                @if($order->transport_detail)
                    <p><strong>Transporte:</strong> {{ $order->transport_detail }}</p>
                @endif
            </div>
        </div>

        @if($order->information)
            <div style="margin-bottom: 20px; padding: 10px; border: 1px dashed #ccc; border-radius: 5px;">
                <h3 style="font-size: 11px; text-transform: uppercase; color: #666; margin: 0 0 5px 0; border-bottom: 1px solid #eee;">Información Adicional del Pedido</h3>
                <p style="font-size: 12px; margin: 0; font-style: italic;">"{{ $order->information }}"</p>
            </div>
        @endif

        <table>
            <thead>
                <tr>
                    <th width="10%">Cod.</th>
                    <th>Descripción del Producto</th>
                    <th width="10%" class="text-center">Cant.</th>
                    <th width="15%" class="text-right">Precio Unit.</th>
                    <th width="20%" class="text-right">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->items as $item)
                    <tr>
                        <td class="text-center">{{ $item->product_id }}</td>
                        <td>{{ $item->product->description }}</td>
                        <td class="text-center">{{ $item->quantity }}</td>
                        <td class="text-right">$ {{ number_format($item->price, 2, ',', '.') }}</td>
                        <td class="text-right">$ {{ number_format($item->price * $item->quantity, 2, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="total-row">
                    <td colspan="4" class="text-right">TOTAL DEL PEDIDO:</td>
                    <td class="text-right">$ {{ number_format($order->total_price, 2, ',', '.') }}</td>
                </tr>
            </tfoot>
        </table>

        <div class="footer">
            <p>Gracias por su compra.</p>
            <p>{{ config('app.name') }} - Generado el {{ now()->format('d/m/Y H:i:s') }}</p>
        </div>
    </div>

    <script>
        // Opcional: auto-disparar el menú de impresión al cargar
        // window.onload = function() { window.print(); }
    </script>
</body>
</html>