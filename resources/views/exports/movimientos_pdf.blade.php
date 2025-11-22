<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reporte de Movimientos</title>
    <style>
        body {
            font-family: sans-serif;
            font-size: 12px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .header h1 {
            margin: 0;
            color: #333;
        }
        .meta {
            margin-bottom: 20px;
            color: #666;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        .text-right {
            text-align: right;
        }
        .total {
            font-size: 14px;
            font-weight: bold;
            text-align: right;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Reporte de Movimientos</h1>
        <p>Generado el: {{ $fecha_generacion->format('d/m/Y H:i:s') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Comprobante</th>
                <th>Tipo</th>
                <th>Método Pago</th>
                <th>Usuario</th>
                <th>Atención</th>
                <th class="text-right">Monto</th>
            </tr>
        </thead>
        <tbody>
            @foreach($movimientos as $mov)
            <tr>
                <td>{{ \Carbon\Carbon::parse($mov->fecha)->format('d/m/Y H:i') }}</td>
                <td>{{ $mov->cod_comprobante }}</td>
                <td>{{ $mov->comprobante?->tipo_comprobante_name ?? 'Nota de Venta' }}</td>
                <td>{{ $mov->metodoPago?->nom_metodo_pago ?? 'No especificado' }}</td>
                <td>{{ $mov->comprobante?->user?->name ?? 'Sistema' }}</td>
                <td>
                    @php
                        $tipo = $mov->comprobante?->pedido?->tipo_atencion ?? 'P';
                        $map = ['P' => 'PRESENCIAL', 'D' => 'DELIVERY', 'R' => 'RECOJO'];
                        echo $map[$tipo] ?? 'MESA';
                    @endphp
                </td>
                <td class="text-right">S/ {{ number_format($mov->costo_total, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="total">
        Total General: S/ {{ number_format($total, 2) }}
    </div>
</body>
</html>
