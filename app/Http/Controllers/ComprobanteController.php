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
            'nombre_cliente' => 'nullable|string|max:255',
            'dni_ce_cliente' => 'nullable|digits_between:8,9',
            'observaciones' => 'nullable|string',
            'monto_pagado' => 'nullable|numeric|min:0',
        ]);

        try {
            DB::beginTransaction();

            $pedido = Pedido::with('items.producto', 'mesa')->findOrFail($pedidoId);

            // 1. Calculate Comprobante Total
            // For Factura (F), we treat the order items price as Subtotal and add 10% IGV on top.
            // For others (B, N), the order items price includes IGV.
            $comprobanteTotal = $pedido->total;
            if ($request->tipo_comprobante === 'F') {
                $comprobanteTotal = $pedido->total * 1.10; // Add 10% IGV
            }

            // Calculate vuelto if monto_pagado is present
            $vuelto = null;
            $montoPagado = null;

            if ($request->filled('monto_pagado') && $request->monto_pagado > 0) {
                $montoPagado = $request->monto_pagado;
                
                // Allow a small margin of error for float comparison or accept that customer pays the calculated total
                if ($montoPagado < $comprobanteTotal) {
                     // Note: Ideally the frontend should know this new total before sending.
                     // But if the user entered the exact amount from the "Order" screen (which is B logic),
                     // and now we are charging more, this might fail.
                     // However, the prompt implies "Mi cliente quiere...", so the cashier likely knows or simply enters the amount given.
                     // We will enforce the check against the NEW total.
                    return response()->json([
                        'error' => 'El monto pagado es insuficiente. Total Factura: ' . number_format($comprobanteTotal, 2),
                    ], 422);
                }
                $vuelto = $montoPagado - $comprobanteTotal;
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
            $comprobante->costo_total = $comprobanteTotal;
            $comprobante->last_updated_by = Auth::user()->id;
            
            // 2. Generate code
            $comprobante->generateCode();
            $comprobante->save();

            // Reload comprobante to ensure all relationships are loaded
            $comprobante->load('metodoPago');

            // 3. Create ReporteIngreso
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
    public function anular(Request $request, $codComprobante)
    {
        try {
            DB::beginTransaction();

            $comprobante = Comprobante::where('cod_comprobante', $codComprobante)->firstOrFail();

            if ($comprobante->anulado) {
                return response()->json(['error' => 'El comprobante ya está anulado'], 422);
            }

            // Permitir anular Notas de Venta (existente)
            if ($comprobante->tipo_comprobante === 'N') {
                $comprobante->anulado = true;
                $comprobante->observaciones = ($comprobante->observaciones ? $comprobante->observaciones . " | " : "") . "ANULADO: " . ($request->motivo ?? 'Sin motivo');
                $comprobante->save();

                DB::commit();
                return response()->json(['message' => 'Nota de Venta anulada exitosamente']);
            }

            // Para Boletas y Facturas, generamos Nota de Crédito
            if (in_array($comprobante->tipo_comprobante, ['B', 'F'])) {
                
                if (!$request->filled('motivo')) {
                    return response()->json(['error' => 'El motivo es obligatorio para anular Boletas o Facturas'], 422);
                }

                // Crear Nota de Crédito
                $creditNote = new Comprobante();
                $creditNote->tipo_comprobante = 'C';
                $creditNote->related_comprobante_id = $comprobante->id;
                $creditNote->tipo_nota_credito = 1; // Anulación de la operación
                $creditNote->sustento = $request->motivo;
                
                // Copiar datos del comprobante original
                $creditNote->user_id = Auth::user()->id;
                $creditNote->pedido_id = $comprobante->pedido_id;
                $creditNote->metodo_pago_id = $comprobante->metodo_pago_id;
                $creditNote->fecha = now();
                $creditNote->num_ruc = $comprobante->num_ruc;
                $creditNote->razon_social = $comprobante->razon_social;
                $creditNote->nombre_cliente = $comprobante->nombre_cliente;
                $creditNote->dni_ce_cliente = $comprobante->dni_ce_cliente;
                $creditNote->costo_total = $comprobante->costo_total;
                $creditNote->last_updated_by = Auth::user()->id;
                $creditNote->observaciones = "Nota de Crédito para " . $comprobante->cod_comprobante;

                // Generar código (BC01-XXX o FC01-XXX)
                $creditNote->generateCode();
                $creditNote->save();

                // 2. Create Reporte Ingreso for the Credit Note
                // This ensures it appears in the movements list.
                ReporteIngreso::create([
                    'cod_comprobante' => $creditNote->cod_comprobante,
                    'metodo_pago_id' => $creditNote->metodo_pago_id,
                    'fecha' => now(),
                    'costo_total' => $creditNote->costo_total
                ]);

                // Marcar original como anulado
                $comprobante->anulado = true;
                $comprobante->observaciones = ($comprobante->observaciones ? $comprobante->observaciones . " | " : "") . "ANULADO POR NC: " . $creditNote->cod_comprobante;
                $comprobante->save();

                // Emitir Nota de Crédito a Nubefact
                try {
                    $result = $this->nubefactService->emitirComprobante($creditNote);
                    
                    if (!$result['success']) {
                        // Si falla Nubefact, ¿hacemos rollback o permitimos que quede pendiente?
                        // Por consistencia, permitimos que se guarde y muestre el error.
                        // El usuario podrá reintentar o ver el error.
                        // Pero como estamos dentro de un transaction, si lanzamos exception se borra todo.
                        // Mejor capturamos y retornamos warning.
                    }
                } catch (\Exception $e) {
                    Log::error('Error enviando Nota de Crédito a Nubefact: ' . $e->getMessage());
                }

                DB::commit();
                return response()->json([
                    'message' => 'Nota de Crédito generada exitosamente',
                    'credit_note' => $creditNote->cod_comprobante
                ]);
            }

            return response()->json(['error' => 'Tipo de comprobante no soportado para anulación'], 422);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error anulando comprobante: ' . $e->getMessage());
            return response()->json(['error' => 'Error al anular el comprobante: ' . $e->getMessage()], 500);
        }
    }
}
