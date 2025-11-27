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

use App\Services\NubefactService;
use App\Rules\RucValidation;

class ComprobanteController extends Controller
{
    protected $nubefactService;

    public function __construct(NubefactService $nubefactService)
    {
        $this->nubefactService = $nubefactService;
    }



    public function create(Request $request, $pedidoId)
    {
        $request->validate([
            'tipo_comprobante' => 'required|string|in:B,F,N',
            'metodo_pago_id' => 'required|integer|exists:metodo_pago,id',
            'num_ruc' => ['nullable', 'required_if:tipo_comprobante,F', 'digits:11', new RucValidation],
            'razon_social' => 'nullable|required_if:tipo_comprobante,F|string|max:255',
            'nombre_cliente' => 'nullable|required_if:tipo_comprobante,B|string|max:255',
            'dni_ce_cliente' => 'nullable|required_if:tipo_comprobante,B|digits_between:8,9',
            'observaciones' => 'nullable|string',
            'monto_pagado' => 'nullable|numeric|min:0',
        ]);

        try {
            DB::beginTransaction();

            $pedido = Pedido::with('items.producto', 'mesa')->findOrFail($pedidoId);

            // Calculate vuelto if monto_pagado is present
            $vuelto = null;
            $montoPagado = null;

            if ($request->filled('monto_pagado') && $request->monto_pagado > 0) {
                $montoPagado = $request->monto_pagado;
                if ($montoPagado < $pedido->total) {
                    return response()->json([
                        'error' => 'El monto pagado es insuficiente',
                    ], 422);
                }
                $vuelto = $montoPagado - $pedido->total;
            }

            // 1. Create Comprobante
            $comprobante = new Comprobante();
            $comprobante->tipo_comprobante = $request->tipo_comprobante;
            $comprobante->metodo_pago_id = $request->metodo_pago_id;
            $comprobante->num_ruc = $request->num_ruc;
            $comprobante->razon_social = $request->razon_social;
            $comprobante->nombre_cliente = $request->nombre_cliente;
            $comprobante->dni_ce_cliente = $request->dni_ce_cliente;
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

            // 4. Mark pedido as cerrado (paid) and save payment details
            $pedido->estado = 'C'; // Cerrado
            $pedido->fecha_cierre = now();
            $pedido->monto_pagado = $montoPagado;
            $pedido->vuelto = $vuelto;
            $pedido->save();

            // 5. Mark mesa as disponible (empty) - only for presencial orders
            if ($pedido->tipo_atencion === 'P' && $pedido->mesa) {
                $pedido->mesa->estado = 'D'; // Disponible
                $pedido->mesa->save();
            }

            DB::commit();

            // 6. Emitir a Nubefact (Solo si es Boleta o Factura)
            if (in_array($comprobante->tipo_comprobante, ['B', 'F'])) {
                try {
                    $this->nubefactService->emitirComprobante($comprobante);
                } catch (\Exception $e) {
                    Log::error('Error enviando a Nubefact: ' . $e->getMessage());
                    // No fallamos la request, solo logueamos el error. El comprobante ya existe localmente.
                }
            }

            // 7. Generate PDF (after successful transaction)
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
                $baseHeight = 360; // base points
                $perItem = 25;     // per item points
                $qrHeight = 100;   // QR code + margins
                
                // Extra space for delivery/pickup client info
                $clientInfoExtra = 0;
                if ($pedido->tipo_atencion === 'D') {
                    $clientInfoExtra = 50; // name + phone + address (increased for safety)
                } elseif ($pedido->tipo_atencion === 'R') {
                    $clientInfoExtra = 30; // name + phone
                }

                // Extra height for delivery cost line
                $deliveryCostExtra = 0;
                if (($pedido->costo_delivery ?? 0) > 0) {
                    $deliveryCostExtra = 15;
                }

                // Extra height for payment details (cash)
                $paymentDetailsExtra = 0;
                if (stripos($comprobante->metodoPago->nom_metodo_pago ?? '', 'efectivo') !== false && ($pedido->monto_pagado ?? 0) > 0) {
                    $paymentDetailsExtra = 25;
                }

                $dynamicHeight = max(450, $baseHeight + ($itemsCount * $perItem) + $descExtra + $qrHeight + $clientInfoExtra + $deliveryCostExtra + $paymentDetailsExtra);                $pdf = Pdf::loadView('pdf.comprobante', [
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

            // Extra space for delivery/pickup client info
            $clientInfoExtra = 0;
            if ($pedido->tipo_atencion === 'D') {
                $clientInfoExtra = 50; // name + phone + address
            } elseif ($pedido->tipo_atencion === 'R') {
                $clientInfoExtra = 30; // name + phone
            }

            // Extra height for delivery cost line
            $deliveryCostExtra = 0;
            if (($pedido->costo_delivery ?? 0) > 0) {
                $deliveryCostExtra = 15;
            }

            // Extra height for payment details (cash)
            $paymentDetailsExtra = 0;
            if (stripos($comprobante->metodoPago->nom_metodo_pago ?? '', 'efectivo') !== false && ($pedido->monto_pagado ?? 0) > 0) {
                $paymentDetailsExtra = 25;
            }

            $dynamicHeight = max(360, $baseHeight + ($itemsCount * $perItem) + $descExtra + $qrHeight + $clientInfoExtra + $deliveryCostExtra + $paymentDetailsExtra);

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

    /**
     * Anular un comprobante (Solo Nota de Venta por ahora).
     */
    public function anular($codComprobante)
    {
        try {
            DB::beginTransaction();

            $comprobante = Comprobante::where('cod_comprobante', $codComprobante)->firstOrFail();

            if ($comprobante->anulado) {
                return response()->json(['error' => 'El comprobante ya está anulado'], 422);
            }

            // Por ahora solo permitimos anular Notas de Venta
            if ($comprobante->tipo_comprobante !== 'N') {
                return response()->json(['error' => 'Solo se pueden anular Notas de Venta'], 422);
            }

            $comprobante->anulado = true;
            $comprobante->save();

            // También debemos actualizar el pedido relacionado si es necesario,
            // pero por ahora solo marcamos el comprobante como anulado.
            // Si quisiéramos revertir el stock o el estado del pedido, lo haríamos aquí.
            // En este caso, el pedido ya fue "Cerrado" y pagado.
            // Al anular la nota de venta, asumimos que es una corrección administrativa
            // y no necesariamente reabre el pedido.

            DB::commit();

            return response()->json(['message' => 'Comprobante anulado exitosamente']);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error anulando comprobante: ' . $e->getMessage());
            return response()->json(['error' => 'Error al anular el comprobante'], 500);
        }
    }
}
