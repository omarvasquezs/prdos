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
            font-size: 7px;
            background: white;
            padding: 0;
            line-height: 1;
        }
        
        .ticket {
            width: 100%;
            max-width: 58mm;
            margin: 0;
            padding: 2mm;
            background: white;
        }
        
        .header {
            text-align: center;
            border-bottom: 1px dashed #000;
            padding-bottom: 1mm;
            margin-bottom: 1mm;
        }
        
        .header h2 {
            font-size: 8px;
            margin: 0;
            padding: 0;
            line-height: 1.1;
            font-weight: bold;
        }
        
        .header h3 {
            font-size: 7px;
            margin: 0.3mm 0 0 0;
            padding: 0;
            font-weight: bold;
            line-height: 1.1;
        }
        
        .header p {
            font-size: 6px;
            margin: 0;
            padding: 0;
            line-height: 1;
        }
        
        .divider {
            border-bottom: 1px dashed #000;
            margin: 0.5mm 0;
        }
        
        .info-line {
            font-size: 6px;
            margin: 0.3mm 0;
            line-height: 1;
        }
        
        .detalle-title {
            font-weight: bold;
            font-size: 7px;
            margin: 0.5mm 0 0.3mm 0;
            padding: 0;
        }
        
        .detalle-row {
            font-size: 6px;
            margin: 0.2mm 0;
            line-height: 1.1;
            padding: 0;
        }
        
        .detalle-header {
            font-size: 6px;
            font-weight: bold;
            margin: 0.2mm 0;
            padding: 0;
            border-bottom: 1px solid #000;
        }
        
        .totales-section {
            border-top: 1px dashed #000;
            margin: 0.5mm 0;
            padding: 0.3mm 0;
            font-size: 7px;
        }
        
        .totales-row {
            display: flex;
            justify-content: space-between;
            margin: 0.2mm 0;
            padding: 0;
        }
        
        .total-final {
            font-weight: bold;
            font-size: 8px;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            margin: 0.3mm 0;
            padding: 0.2mm 0;
        }
        
        .footer {
            text-align: center;
            margin-top: 0.5mm;
            padding-top: 0.5mm;
            border-top: 1px dashed #000;
            font-size: 6px;
        }
        
        .footer p {
            margin: 0.2mm 0;
            padding: 0;
            line-height: 1;
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
        <div class="divider"></div>

        <!-- Detalle -->
        <div class="detalle-title">DETALLE</div>
        <div class="detalle-header">DESCRIPCIÓN                    CANT    TOTAL</div>
        @forelse($pedido->items ?? [] as $item)
            <div class="detalle-row">
                {{ substr($item->producto->nombre ?? 'Producto', 0, 34) }}<br>
                <span style="text-align: right; float: right;">{{ $item->cantidad ?? 0 }} x S/ {{ number_format(($item->precio_unitario ?? 0), 2) }}</span>
            </div>
        @empty
            <div class="detalle-row">Sin items</div>
        @endforelse
        <div class="divider"></div>

        <!-- Totales -->
        <div class="totales-section">
            <div class="totales-row">
                <span>SUBTOTAL</span>
                <span style="text-align: right;">S/ {{ number_format($pedido->total ?? 0, 2) }}</span>
            </div>
            <div class="totales-row">
                <span>IGV (0%)</span>
                <span style="text-align: right;">S/ 0.00</span>
            </div>
            <div class="total-final">
                <div style="display: flex; justify-content: space-between;">
                    <span>TOTAL</span>
                    <span>S/ {{ number_format($pedido->total ?? 0, 2) }}</span>
                </div>
            </div>
        </div>

        <!-- Método de Pago -->
        <div class="info-line">
            <strong>Método Pago:</strong> {{ substr($comprobante->metodoPago->nom_metodo_pago ?? 'N/A', 0, 25) }}
        </div>
        <div class="divider"></div>

        <!-- Footer -->
        <div class="footer">
            <p>¡Gracias por su compra!</p>
            <p>{{ now()->format('d/m/Y H:i:s') }}</p>
        </div>
    </div>
</body>
</html>
