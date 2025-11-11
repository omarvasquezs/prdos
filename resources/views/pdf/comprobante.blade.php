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
        
        html, body {
            width: 58mm;
            height: auto;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: 'Courier New', monospace;
            font-size: 8px;
            background: white;
            padding: 1mm;
        }
        
        .ticket {
            width: 100%;
            max-width: 56mm;
            margin: 0;
            padding: 0;
            background: white;
        }
        
        .header {
            text-align: center;
            border-bottom: 1px dashed #000;
            padding-bottom: 1mm;
            margin-bottom: 1mm;
        }
        
        .header h2 {
            font-size: 9px;
            margin: 0.2mm 0;
            line-height: 1;
        }
        
        .header h3 {
            font-size: 8px;
            margin: 0.2mm 0;
            font-weight: bold;
        }
        
        .header p {
            font-size: 6px;
            margin: 0.1mm 0;
            line-height: 1;
        }
        
        .content {
            margin: 0.5mm 0;
        }
        
        .section {
            margin: 0.5mm 0;
            padding: 0.5mm 0;
            border-bottom: 1px dashed #000;
        }
        
        .section-title {
            font-weight: bold;
            font-size: 6px;
            margin-bottom: 0.3mm;
        }
        
        .section-content {
            font-size: 6px;
            line-height: 1.2;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 0.3mm 0;
            font-size: 6px;
        }
        
        thead {
            border-bottom: 1px solid #000;
        }
        
        th, td {
            padding: 0.2mm;
            text-align: left;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }
        
        th {
            font-weight: bold;
            background: #f5f5f5;
            font-size: 5px;
        }
        
        td.quantity, td.price {
            text-align: right;
        }
        
        .total-section {
            border-top: 1px double #000;
            padding-top: 0.3mm;
            margin-top: 0.3mm;
            font-weight: bold;
        }
        
        .total-section td {
            padding: 0.2mm;
            font-size: 7px;
        }
        
        .footer {
            text-align: center;
            margin-top: 0.5mm;
            padding-top: 0.5mm;
            border-top: 1px dashed #000;
            font-size: 6px;
        }
        
        .footer p {
            margin: 0.1mm 0;
            line-height: 1;
        }
        
        .divider {
            border-bottom: 1px dashed #000;
            margin: 0.5mm 0;
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
                    <strong>RUC:</strong> {{ $comprobante->num_ruc }}<br>
                    <strong>Razón Social:</strong> {{ substr($comprobante->razon_social ?? '', 0, 30) }}<br>
                @endif
            </div>
        </div>

        <!-- Items -->
        <div class="section">
            <div class="section-title">DETALLE</div>
            <table>
                <thead>
                    <tr>
                        <th style="width: 50%">Desc.</th>
                        <th style="width: 20%; text-align: center">Cant</th>
                        <th style="width: 30%; text-align: right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($pedido->items ?? [] as $item)
                        <tr>
                            <td>{{ substr($item->producto->nombre ?? 'Prod', 0, 12) }}</td>
                            <td style="text-align: center">{{ $item->cantidad ?? 0 }}</td>
                            <td style="text-align: right">S/ {{ number_format(($item->precio_unitario ?? 0) * ($item->cantidad ?? 0), 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" style="text-align: center; font-size: 6px">Sin items</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Totales -->
        <div class="section">
            <div class="total-section">
                <table style="margin: 0">
                    <tr>
                        <td style="text-align: left; width: 60%">Subtotal:</td>
                        <td style="text-align: right; width: 40%">S/ {{ number_format($pedido->total ?? 0, 2) }}</td>
                    </tr>
                    <tr>
                        <td style="text-align: left">IGV (0%):</td>
                        <td style="text-align: right">S/ 0.00</td>
                    </tr>
                    <tr style="border-top: 1px solid #000; font-size: 8px">
                        <td style="text-align: left"><strong>TOTAL:</strong></td>
                        <td style="text-align: right"><strong>S/ {{ number_format($pedido->total ?? 0, 2) }}</strong></td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Método de Pago -->
        <div class="section">
            <div class="section-content">
                <strong>Pago:</strong> {{ substr($comprobante->metodoPago->nom_metodo_pago ?? 'N/A', 0, 20) }}
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>¡Gracias!</p>
            <p>{{ now()->format('d/m/Y H:i:s') }}</p>
        </div>
    </div>
</body>
</html>
