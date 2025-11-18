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
        
        @page {
            margin: 6mm 3mm 3mm 3mm;
        }

        html, body {
            width: 58mm;
            height: auto;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 8px;
            background: white;
            padding: 0;
            line-height: 1.1;
        }

        .ticket {
            width: 100%;
            max-width: 50mm;
            margin: 6mm auto 0 auto;
            padding: 0;
            background: white;
        }
        
        .header {
            text-align: center;
            border-bottom: 1px dashed #000;
            padding-bottom: 1.5mm;
            margin-bottom: 1.5mm;
        }
        
        .header .logo {
            width: 35mm;
            height: auto;
            margin: 0 auto 1mm auto;
            display: block;
        }
        
        .header h2 {
            font-size: 10px;
            margin: 0;
            padding: 0;
            line-height: 1.2;
            font-weight: bold;
        }
        
        .header h3 {
            font-size: 8.5px;
            margin: 0.8mm 0 0 0;
            padding: 0;
            font-weight: bold;
            line-height: 1.2;
        }
        
        .header p {
            font-size: 7.5px;
            margin: 0;
            padding: 0;
            line-height: 1.3;
        }
        
        .divider {
            border-bottom: 1px dashed #000;
            margin: 1mm 0;
        }
        
        .info-line {
            font-size: 7px;
            margin: 0.3mm 0;
            line-height: 1.3;
        }
        
        .detalle-title {
            font-weight: bold;
            font-size: 8.5px;
            margin: 0.8mm 0 0.3mm 0;
            padding: 0 0 0.3mm;
            text-transform: uppercase;
        }
        
        /* Compact table for items */
        table.items {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            font-size: 7.5px;
            margin: 0.5mm 0 1mm 0;
        }
        table.items thead th {
            font-weight: bold;
            padding: 0.4mm 0.2mm;
            text-align: center;
            background-color: #f5f5f5;
            text-transform: uppercase;
        }
        table.items td {
            padding: 0.35mm 0.2mm;
            vertical-align: top;
        }
        .col-desc { width: 60%; word-wrap: break-word; overflow-wrap: break-word; }
        .col-cant { width: 15%; text-align: center; white-space: nowrap; }
        .col-total { width: 25%; text-align: right; white-space: nowrap; }
        .item-desc-line { font-size:7px; line-height:1.2; }
        .item-name { font-weight: bold; display:block; margin-bottom:0.3mm; }
        
        .totales-section {
            border-top: 2px solid #000;
            margin: 1mm 0 0.5mm;
            padding-top: 0.5mm;
            font-size: 7.5px;
        }

        .totales-row {
            display: flex;
            justify-content: space-between;
            margin: 0.4mm 0;
        }

        .total-final {
            font-weight: bold;
            font-size: 8.5px;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            margin: 0.6mm 0 0;
            padding: 0.3mm 0;
        }
        
        .footer {
            text-align: center;
            margin-top: 1mm;
            padding-top: 0.5mm;
            font-size: 7px;
        }

        .footer p {
            margin: 0.3mm 0;
            padding: 0;
            line-height: 1.2;
        }
        
        .footer .qr-code {
            width: 23mm;
            height: 23mm;
            margin: 0.5mm auto;
            display: block;
        }
        
        .footer .thank-you {
            font-size: 7.5px;
            font-weight: bold;
            margin: 0.5mm 0 0.5mm 0;
        }
    </style>
</head>
<body>
    <div class="ticket">
        <!-- Header -->
        <div class="header">
            <img src="{{ public_path('prdos_chicken_logo_optimizado.jpg') }}" alt="Logo" class="logo">
            <h1>Pollería Pard's</h1>
            <h2>RUC: 10441973361</h2>
            <p>Mz. E Lote 11</p>
            <p>A.H. Señor de los Milagros</p>
            <p>Prov. Const. del Callao</p>
            <p>Carmen de la Legua - Reynoso</p>
            <div class="divider"></div>
            <h3>{{ $comprobante->tipo_comprobante === 'B' ? 'BOLETA' : ($comprobante->tipo_comprobante === 'F' ? 'FACTURA' : 'NOTA DE VENTA') }}</h3>
            <p>{{ $comprobante->cod_comprobante }}</p>
        </div>

        <!-- Información General -->
        <div class="info-line">
            <strong>Fecha y Hora:</strong> {{ $comprobante->fecha->format('d/m/Y - H:i') }}
        </div>
        @if($comprobante->tipo_comprobante === 'F')
            <div class="info-line">
                <strong>RUC Cliente:</strong> {{ $comprobante->num_ruc }}
            </div>
            <div class="info-line">
                <strong>Razón Social:</strong> {{ substr($comprobante->razon_social ?? '', 0, 35) }}
            </div>
        @endif
        
        <!-- Información de cliente para Delivery y Recojo -->
        @if($pedido->tipo_atencion === 'D')
            <div class="info-line">
                <strong>Cliente:</strong> {{ $pedido->cliente_nombre }}
            </div>
            <div class="info-line">
                <strong>Dirección:</strong> {{ substr($pedido->direccion_entrega ?? '', 0, 50) }}
            </div>
        @elseif($pedido->tipo_atencion === 'R')
            <div class="info-line">
                <strong>Cliente:</strong> {{ $pedido->cliente_nombre }}
            </div>
        @endif
        
        <div class="divider"></div>

        <!-- Detalle -->
        <div class="detalle-title">DETALLE</div>
        <table class="items">
            <thead>
                <tr>
                    <th class="col-desc">Descripción</th>
                    <th class="col-cant">Cant</th>
                    <th class="col-total">Total</th>
                </tr>
            </thead>
            <tbody>
            @forelse(($pedido->items ?? []) as $item)
                @php
                    $name = $item->producto->name ?? 'Producto';
                    $desc = $item->producto->description ?? '';
                    // Wrap description to ~32 chars per line for proportional font
                    $wrappedDesc = wordwrap($desc, 32, "\n", true);
                    $descLines = array_filter(explode("\n", $wrappedDesc));
                @endphp
                <tr>
                    <td class="col-desc">
                        <span class="item-name">{{ $name }}</span>
                        @if(!empty($descLines))
                            @foreach($descLines as $dLine)
                                <div class="item-desc-line">{{ $dLine }}</div>
                            @endforeach
                        @endif
                    </td>
                    <td class="col-cant">{{ (int)($item->cantidad ?? 0) }}</td>
                    <td class="col-total">S/ {{ number_format((($item->precio_unitario ?? 0) * ($item->cantidad ?? 0)), 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" style="text-align:center">Sin items</td>
                </tr>
            @endforelse
            </tbody>
        </table>

        <!-- Totales -->
        @php
            $total = $pedido->total ?? 0;
            $igvRate = 0.18; // 18%
            $igv = $total * $igvRate;
            $subtotal = $total - $igv;
        @endphp
        <div class="totales-section">
            <div class="totales-row">
                <span>Subtotal</span>
                <span>S/ {{ number_format($subtotal, 2) }}</span>
            </div>
            <div class="totales-row">
                <span>IGV (18%)</span>
                <span>S/ {{ number_format($igv, 2) }}</span>
            </div>
            <div class="total-final">
                <div style="display: flex; justify-content: space-between;">
                    <span>Total</span>
                    <span>S/ {{ number_format($total, 2) }}</span>
                </div>
            </div>
        </div>

        <!-- Método de Pago -->
        <div class="info-line">
            <strong>Método Pago:</strong> {{ substr($comprobante->metodoPago->nom_metodo_pago ?? 'N/A', 0, 25) }}
        </div>
        <div style="margin: 2mm 0;"></div>

        <!-- Footer -->
        <div class="footer">
            <p class="thank-you">¡Gracias por su preferencia, vuelva pronto!</p>
            @php
                $qrData = sprintf(
                    "RUC: %s\nTipo: %s\nCódigo: %s\nTotal: S/ %s\nFecha: %s",
                    '10441973361',
                    $comprobante->tipo_comprobante === 'B' ? 'BOLETA' : ($comprobante->tipo_comprobante === 'F' ? 'FACTURA' : 'NOTA DE VENTA'),
                    $comprobante->cod_comprobante,
                    number_format($total, 2),
                    $comprobante->fecha->format('d/m/Y H:i')
                );
                $qrCode = base64_encode(QrCode::format('png')->size(200)->generate($qrData));
            @endphp
            <img src="data:image/png;base64,{{ $qrCode }}" alt="QR Code" class="qr-code">
            <p>{{ now()->format('d/m/Y H:i:s') }}</p>
        </div>
    </div>
</body>
</html>
