<?php

namespace App\Http\Controllers;

use App\Models\Comprobante;
use App\Models\ComprobantePago;
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
            'metodo_pago_id' => 'nullable|integer|exists:metodo_pago,id',
            'pagos' => 'nullable|array|min:1',
            'pagos.*.metodo_pago_id' => 'required_with:pagos|integer|exists:metodo_pago,id',
            'pagos.*.monto' => 'required_with:pagos|numeric|min:0.01',
            'pagos.*.monto_recibido' => 'nullable|numeric|min:0',
            'num_ruc' => ['nullable', 'required_if:tipo_comprobante,F', 'digits:11', new RucValidation],
            'razon_social' => 'nullable|required_if:tipo_comprobante,F|string|max:255',
            'nombre_cliente' => 'nullable|string|max:255',
            'dni_ce_cliente' => 'nullable|digits_between:8,9',
            'observaciones' => 'nullable|string',
            'monto_pagado' => 'nullable|numeric|min:0',
        ]);

        if (!$request->filled('metodo_pago_id') && (!$request->has('pagos') || empty($request->pagos))) {
            return response()->json([
                'error' => 'Debe seleccionar al menos un método de pago.'
            ], 422);
        }

        try {
            DB::beginTransaction();

            $pedido = Pedido::with('items.producto', 'mesa')->findOrFail($pedidoId);

            // 1. Calculate Comprobante Total
            // For Factura (F), we treat the order items price as Subtotal and add 10.5% IGV on top.
            // For others (B, N), the order items price includes IGV.
            $comprobanteTotal = $pedido->total;
            if ($request->tipo_comprobante === 'F') {
                $comprobanteTotal = $pedido->total * 1.105; // Add 10.5% IGV
            }
            $comprobanteTotal = round($comprobanteTotal, 2);

            // Normalize pagos list
            if ($request->has('pagos') && count($request->pagos) > 0) {
                $pagosList = $request->pagos;
            } else {
                $pagosList = [[
                    'metodo_pago_id' => (int) $request->metodo_pago_id,
                    'monto' => $comprobanteTotal,
                    'monto_recibido' => $request->filled('monto_pagado') ? (float) $request->monto_pagado : null,
                ]];
            }

            // Validate that sum of pagos matches comprobanteTotal
            $sumaPagos = round(collect($pagosList)->sum('monto'), 2);
            if (abs($sumaPagos - $comprobanteTotal) > 0.05) {
                return response()->json([
                    'error' => 'La suma de los métodos de pago (S/ ' . number_format($sumaPagos, 2) . ') no coincide con el total a cobrar (S/ ' . number_format($comprobanteTotal, 2) . ').',
                ], 422);
            }

            // Calculate vueltos and validate received amounts
            $totalMontoRecibido = 0;
            $totalVuelto = 0;
            foreach ($pagosList as &$pagoItem) {
                $monto = (float) $pagoItem['monto'];
                $pagoItem['vuelto'] = null;
                if (isset($pagoItem['monto_recibido']) && $pagoItem['monto_recibido'] !== null && $pagoItem['monto_recibido'] > 0) {
                    $recibido = (float) $pagoItem['monto_recibido'];
                    if ($recibido < $monto) {
                        return response()->json([
                            'error' => 'El monto recibido es insuficiente para el método de pago especificado.',
                        ], 422);
                    }
                    $pagoItem['vuelto'] = round($recibido - $monto, 2);
                    $totalVuelto += $pagoItem['vuelto'];
                    $totalMontoRecibido += $recibido;
                } else {
                    $pagoItem['monto_recibido'] = null;
                    $totalMontoRecibido += $monto;
                }
            }
            unset($pagoItem);

            $primaryMetodoId = count($pagosList) === 1 ? $pagosList[0]['metodo_pago_id'] : null;

            // 1. Create Comprobante
            $comprobante = new Comprobante();
            $comprobante->tipo_comprobante = $request->tipo_comprobante;
            $comprobante->metodo_pago_id = $primaryMetodoId;
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

            // 3. Create ComprobantePago records
            foreach ($pagosList as $pagoItem) {
                ComprobantePago::create([
                    'comprobante_id' => $comprobante->id,
                    'metodo_pago_id' => $pagoItem['metodo_pago_id'],
                    'monto' => $pagoItem['monto'],
                    'monto_recibido' => $pagoItem['monto_recibido'],
                    'vuelto' => $pagoItem['vuelto'],
                ]);
            }

            // 4. Create ReporteIngreso records (one per payment method)
            foreach ($pagosList as $pagoItem) {
                ReporteIngreso::create([
                    'cod_comprobante' => $comprobante->cod_comprobante,
                    'metodo_pago_id' => $pagoItem['metodo_pago_id'],
                    'fecha' => now(),
                    'costo_total' => $pagoItem['monto']
                ]);
            }

            // 5. Mark pedido as cerrado (paid) and save payment details
            $pedido->estado = 'C'; // Cerrado
            $pedido->fecha_cierre = now();
            $pedido->metodo_pago_id = $primaryMetodoId;
            $pedido->monto_pagado = $totalMontoRecibido > 0 ? $totalMontoRecibido : null;
            $pedido->vuelto = $totalVuelto > 0 ? $totalVuelto : null;
            $pedido->save();

            // 6. Mark mesa as disponible (empty) - only for presencial orders
            if ($pedido->tipo_atencion === 'P' && $pedido->mesa) {
                $pedido->mesa->estado = 'D'; // Disponible
                $pedido->mesa->save();
            }

            DB::commit();

            // 7. Emitir a Nubefact (Solo si es Boleta o Factura)
            if (in_array($comprobante->tipo_comprobante, ['B', 'F'])) {
                try {
                    $this->nubefactService->emitirComprobante($comprobante);
                } catch (\Exception $e) {
                    Log::error('Error enviando a Nubefact: ' . $e->getMessage());
                }
            }

            // Reload comprobante with relationships
            $comprobante->load(['metodoPago', 'pagos.metodoPago']);

            // 8. Generate PDF (after successful transaction)
            try {
                $itemsCount = $pedido->items->count();
                $descExtra = 0;
                foreach ($pedido->items as $it) {
                    $desc = $it->produto->description ?? '';
                    if ($desc) {
                        $lines = (int) ceil(strlen($desc) / 32);
                        $descExtra += max(0, $lines) * 8;
                    }
                }
                $baseHeight = 360;
                $perItem = 25;
                $qrHeight = 100;

                $clientInfoExtra = 0;
                if ($pedido->tipo_atencion === 'D') {
                    $clientInfoExtra = 50;
                } elseif ($pedido->tipo_atencion === 'R') {
                    $clientInfoExtra = 30;
                }

                $deliveryCostExtra = 0;
                if (($pedido->costo_delivery ?? 0) > 0) {
                    $deliveryCostExtra = 15;
                }

                $paymentDetailsExtra = 0;
                if ($totalVuelto > 0) {
                    $paymentDetailsExtra = 25;
                }

                $pagosExtra = count($pagosList) * 16;

                $dynamicHeight = max(450, $baseHeight + ($itemsCount * $perItem) + $descExtra + $qrHeight + $clientInfoExtra + $deliveryCostExtra + $paymentDetailsExtra + $pagosExtra);
                $pdf = Pdf::loadView('pdf.comprobante', [
                    'comprobante' => $comprobante,
                    'pedido' => $pedido
                ])->setPaper([0, 0, 164.4, $dynamicHeight], 'portrait');

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
                ->with(['metodoPago', 'pagos.metodoPago'])
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

            // Extra height for payment details
            $paymentDetailsExtra = 0;
            if (($pedido->vuelto ?? 0) > 0) {
                $paymentDetailsExtra = 25;
            }

            $pagosCount = $comprobante->pagos ? $comprobante->pagos->count() : 1;
            $pagosExtra = $pagosCount * 16;

            $dynamicHeight = max(360, $baseHeight + ($itemsCount * $perItem) + $descExtra + $qrHeight + $clientInfoExtra + $deliveryCostExtra + $paymentDetailsExtra + $pagosExtra);

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

                // 2. Create Reporte Ingreso and ComprobantePago for the Credit Note
                if ($comprobante->pagos()->exists()) {
                    foreach ($comprobante->pagos as $p) {
                        ReporteIngreso::create([
                            'cod_comprobante' => $creditNote->cod_comprobante,
                            'metodo_pago_id' => $p->metodo_pago_id,
                            'fecha' => now(),
                            'costo_total' => $p->monto
                        ]);
                        ComprobantePago::create([
                            'comprobante_id' => $creditNote->id,
                            'metodo_pago_id' => $p->metodo_pago_id,
                            'monto' => $p->monto,
                        ]);
                    }
                } else {
                    ReporteIngreso::create([
                        'cod_comprobante' => $creditNote->cod_comprobante,
                        'metodo_pago_id' => $creditNote->metodo_pago_id,
                        'fecha' => now(),
                        'costo_total' => $creditNote->costo_total
                    ]);
                }

                // Marcar original como anulado
                $comprobante->anulado = true;
                $comprobante->observaciones = ($comprobante->observaciones ? $comprobante->observaciones . " | " : "") . "ANULADO POR NC: " . $creditNote->cod_comprobante;
                $comprobante->save();

                // Emitir Nota de Crédito a Nubefact
                try {
                    $result = $this->nubefactService->emitirComprobante($creditNote);
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
