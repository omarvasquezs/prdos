<?php

namespace App\Http\Controllers;

use App\Models\Comprobante;
use App\Models\MetodoPago;
use App\Models\Pedido;
use App\Models\ReporteIngreso;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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

        try {
            DB::beginTransaction();

            $pedido = Pedido::with('items.producto', 'mesa')->findOrFail($pedidoId);

            // 1. Create Comprobante
            $comprobante = new Comprobante();
            $comprobante->tipo_comprobante = $request->tipo_comprobante;
            $comprobante->metodo_pago_id = $request->metodo_pago_id;
            $comprobante->num_ruc = $request->num_ruc;
            $comprobante->razon_social = $request->razon_social;
            $comprobante->observaciones = $request->observaciones;
            $comprobante->user_id = Auth::user()->id;
            $comprobante->pedido_id = $pedido->id;
            $comprobante->fecha = now();
            $comprobante->costo_total = $pedido->total;
            $comprobante->last_updated_by = Auth::user()->id;
            
            // 2. Generate code
            $comprobante->generateCode();
            $comprobante->save();

            // Reload comprobante to ensure all relationships are loaded
            $comprobante->load('metodoPago');

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

            DB::commit();

            // 5. Generate PDF (after successful transaction)
            try {
                $pdf = Pdf::loadView('pdf.comprobante', [
                    'comprobante' => $comprobante,
                    'pedido' => $pedido
                ])->setPaper([0, 0, 164.4, 841.89], 'portrait'); // 58mm width

                return $pdf->stream('comprobante-' . $comprobante->cod_comprobante . '.pdf');
            } catch (\Exception $e) {
                Log::error('PDF Generation Error: ' . $e->getMessage());
                return response()->json(['error' => 'Error generando PDF: ' . $e->getMessage()], 500);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Comprobante Creation Error: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function getMetodosPago()
    {
        return MetodoPago::where('habilitado', true)->get();
    }
}
