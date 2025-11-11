<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comprobante {{ $comprobante->cod_comprobante }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Courier New', monospace;
            font-size: 9px;
            background: white;
        }
        
        .ticket {
            width: 58mm;
            max-width: 58mm;
            margin: 0 auto;
            padding: 5mm;
            background: white;
        }
        
        .header {
            text-align: center;
            border-bottom: 1px dashed #000;
            padding-bottom: 3mm;
            margin-bottom: 3mm;
        }
        
        .header h2 {
            font-size: 11px;
            margin: 1mm 0;
        }
        
        .header h3 {
            font-size: 10px;
            margin: 1mm 0;
        }
        
        .header p {
            font-size: 8px;
            margin: 0.5mm 0;
            line-height: 1.2;
        }
        
        .content {
            margin: 3mm 0;
        }
        
        .section {
            margin: 2mm 0;
            padding: 2mm 0;
            border-bottom: 1px dashed #000;
        }
        
        .section-title {
            font-weight: bold;
            font-size: 8px;
            margin-bottom: 1mm;
        }
        
        .section-content {
            font-size: 8px;
            line-height: 1.4;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 2mm 0;
            font-size: 8px;
        }
        
        thead {
            border-bottom: 1px solid #000;
        }
        
        th, td {
            padding: 1mm;
            text-align: left;
        }
        
        th {
            font-weight: bold;
            background: #f5f5f5;
        }
        
        td.quantity, td.price {
            text-align: right;
        }
        
        .total-section {
            border-top: 1px double #000;
            padding-top: 2mm;
            margin-top: 2mm;
            font-weight: bold;
            text-align: right;
        }
        
        .footer {
            text-align: center;
            margin-top: 3mm;
            padding-top: 3mm;
            border-top: 1px dashed #000;
            font-size: 8px;
        }
        
        .footer p {
            margin: 1mm 0;
        }
        
        .divider {
            border-bottom: 1px dashed #000;
            margin: 2mm 0;
        }
    </style>
</head>
<body>
    <div class="ticket">
        <!-- Header -->
        <div class="header">
            <h2>Pollería P'rdos</h2>
            <p>RUC: 10458845125</p>
            <p>Av. Universitaria 123</p>
            <div class="divider"></div>
            <h3>{{ $comprobante->tipo_comprobante === 'B' ? 'BOLETA' : ($comprobante->tipo_comprobante === 'F' ? 'FACTURA' : 'NOTA DE VENTA') }}</h3>
            <p>{{ $comprobante->cod_comprobante }}</p>
        </div>

        <!-- Información General -->
        <div class="section">
            <div class="section-content">
                <strong>Fecha:</strong> {{ $comprobante->fecha->format('d/m/Y H:i') }}<br>
                @if($comprobante->tipo_comprobante === 'F')
                    <strong>RUC Cliente:</strong> {{ $comprobante->num_ruc }}<br>
                    <strong>Razón Social:</strong> {{ $comprobante->razon_social }}<br>
                @endif
            </div>
        </div>

        <!-- Items -->
        <div class="section">
            <div class="section-title">DETALLE</div>
            <table>
                <thead>
                    <tr>
                        <th style="width: 50%">Descripción</th>
                        <th style="width: 15%; text-align: center">Cant</th>
                        <th style="width: 35%; text-align: right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($pedido->items as $item)
                        <tr>
                            <td>{{ substr($item->producto->nombre, 0, 15) }}</td>
                            <td style="text-align: center">{{ $item->cantidad }}</td>
                            <td style="text-align: right">S/ {{ number_format($item->precio_unitario * $item->cantidad, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Totales -->
        <div class="section">
            <div class="total-section">
                <table style="margin: 0">
                    <tr>
                        <td style="text-align: left; padding: 1mm">Subtotal:</td>
                        <td style="text-align: right; padding: 1mm">S/ {{ number_format($pedido->total, 2) }}</td>
                    </tr>
                    <tr>
                        <td style="text-align: left; padding: 1mm">IGV (0%):</td>
                        <td style="text-align: right; padding: 1mm">S/ 0.00</td>
                    </tr>
                    <tr style="font-size: 9px; border-top: 1px solid #000">
                        <td style="text-align: left; padding: 1mm"><strong>TOTAL:</strong></td>
                        <td style="text-align: right; padding: 1mm"><strong>S/ {{ number_format($pedido->total, 2) }}</strong></td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Método de Pago -->
        <div class="section">
            <div class="section-content">
                <strong>Método de Pago:</strong> {{ $comprobante->metodoPago->nom_metodo_pago }}
            </div>
        </div>

        <!-- Observaciones -->
        @if($comprobante->observaciones)
            <div class="section">
                <div class="section-content">
                    <strong>Obs:</strong> {{ substr($comprobante->observaciones, 0, 40) }}
                </div>
            </div>
        @endif

        <!-- Footer -->
        <div class="footer">
            <p>¡Gracias por su compra!</p>
            <p>{{ now()->format('d/m/Y H:i:s') }}</p>
        </div>
    </div>
</body>
</html>
