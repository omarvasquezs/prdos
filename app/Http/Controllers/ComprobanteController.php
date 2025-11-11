<?php

namespace App\Http\Controllers;

use App\Models\Comprobante;
use App\Models\MetodoPago;
use App\Models\Pedido;
use App\Models\ReporteIngreso;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;

class ComprobanteController extends Controller
{
    public function create(Request $request, $pedidoId)
    {
        $request->validate([
            'tipo_comprobante' => 'required|string|in:B,F,N',
            'metodo_pago_id' => 'required|integer|exists:metodo_pago,id',
            'num_ruc' => 'nullable|required_if:tipo_comprobante,F|digits:11',
            'razon_social' => 'nullable|required_if:tipo_comprobante,F|string|max:255',
            'observaciones' => 'nullable|string',
        ]);

        $pedido = Pedido::with('items.producto')->findOrFail($pedidoId);

        // 1. Create Comprobante
        $comprobante = new Comprobante();
        $comprobante->fill($request->all());
        $comprobante->user_id = Auth::id();
        $comprobante->pedido_id = $pedido->id;
        $comprobante->fecha = now();
        $comprobante->costo_total = $pedido->total;
        $comprobante->last_updated_by = Auth::id();
        
        // 2. Generate code
        $comprobante->generateCode();
        $comprobante->save();

        // 3. Create Reporte Ingreso
        ReporteIngreso::create([
            'cod_comprobante' => $comprobante->cod_comprobante,
            'metodo_pago_id' => $comprobante->metodo_pago_id,
            'fecha' => now(),
            'costo_total' => $comprobante->costo_total
        ]);

        // 4. Mark pedido as pagado
        $pedido->estado = 'P'; // Pagado
        $pedido->save();

        // 5. Generate PDF
        $pdf = Pdf::loadView('pdf.comprobante', [
            'comprobante' => $comprobante,
            'pedido' => $pedido
        ])->setPaper([0, 0, 164.4, 841.89], 'portrait'); // 58mm width

        return $pdf->stream('comprobante-' . $comprobante->cod_comprobante . '.pdf');
    }

    public function getMetodosPago()
    {
        return MetodoPago::where('habilitado', true)->get();
    }
}
