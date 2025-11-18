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

            // 4. Mark pedido as cerrado (paid)
            $pedido->estado = 'C'; // Cerrado
            $pedido->fecha_cierre = now();
            $pedido->save();

            // 5. Mark mesa as disponible (empty) - only for presencial orders
            if ($pedido->tipo_atencion === 'P' && $pedido->mesa) {
                $pedido->mesa->estado = 'D'; // Disponible
                $pedido->mesa->save();
            }

            DB::commit();

            // 5. Generate PDF (after successful transaction)
            try {
                // Calculate a dynamic paper height to avoid large empty space
                $itemsCount = $pedido->items->count();
                // Estimate extra height if descriptions exist
                $descExtra = 0;
                foreach ($pedido->items as $it) {
                    $desc = $it->produto->description ?? '';
                    if ($desc) {
                        // approx 34 chars per line -> add lines-1
                        $lines = (int) ceil(strlen($desc) / 32);
                        $descExtra += max(0, $lines) * 8; // 8pt per line
                    }
                }
                $baseHeight = 400; // base points (increased for logo + header)
                $perItem = 28;     // per item points (increased for better spacing)
                $qrHeight = 120;   // extra space for QR code + bottom margin
                $clientInfoExtra = 0;
                // Add extra space for delivery/pickup client info
                if ($pedido->tipo_atencion === 'D') {
                    $clientInfoExtra = 45; // name + phone + address
                } elseif ($pedido->tipo_atencion === 'R') {
                    $clientInfoExtra = 25; // name + phone
                }
                $dynamicHeight = max(450, $baseHeight + ($itemsCount * $perItem) + $descExtra + $qrHeight + $clientInfoExtra);
            if ($pedido->tipo_atencion === 'D') {
                $clientInfoExtra = 45;
            } elseif ($pedido->tipo_atencion === 'R') {
                $clientInfoExtra = 25;
            }
            $dynamicHeight = max(450, $baseHeight + ($itemsCount * $perItem) + $descExtra + $qrHeight + $clientInfoExtra);                $pdf = Pdf::loadView('pdf.comprobante', [
                    'comprobante' => $comprobante,
                    'pedido' => $pedido
                ])->setPaper([0, 0, 164.4, $dynamicHeight], 'portrait'); // 58mm width, dynamic height

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

    /**
     * Visualizar un comprobante existente por su código.
     */
    public function show($codComprobante)
    {
        try {
            $comprobante = Comprobante::where('cod_comprobante', $codComprobante)
                ->with('metodoPago')
                ->firstOrFail();

            $pedido = Pedido::with('items.producto', 'mesa')
                ->findOrFail($comprobante->pedido_id);

            // Calculate dynamic paper height
            $itemsCount = $pedido->items->count();
            $descExtra = 0;
            foreach ($pedido->items as $it) {
                $desc = $it->produto->description ?? '';
                if ($desc) {
                    $lines = (int) ceil(strlen($desc) / 32);
                    $descExtra += max(0, $lines) * 8;
                }
            }
            $baseHeight = 320; // increased for logo
            $perItem = 22;
            $qrHeight = 95;    // extra space for QR code + bottom margin
            $dynamicHeight = max(360, $baseHeight + ($itemsCount * $perItem) + $descExtra + $qrHeight);

            $pdf = Pdf::loadView('pdf.comprobante', [
                'comprobante' => $comprobante,
                'pedido' => $pedido
            ])->setPaper([0, 0, 164.4, $dynamicHeight], 'portrait');

            return $pdf->stream('comprobante-' . $comprobante->cod_comprobante . '.pdf');
        } catch (\Exception $e) {
            Log::error('PDF Show Error: ' . $e->getMessage());
            return response()->json(['error' => 'Comprobante no encontrado'], 404);
        }
    }
}
