<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CajaAperturaCierre;
use App\Models\Comprobante;
use App\Models\ReporteIngreso;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CajaController extends Controller
{
    /**
     * Obtener el estado de la caja para la fecha actual.
     */
    public function status(): JsonResponse
    {
        try {
            $today = now()->toDateString();

            $openRecord = CajaAperturaCierre::whereDate('datetime_apertura', $today)
                ->whereNull('datetime_cierre')
                ->orderByDesc('datetime_apertura')
                ->first();

            $latestRecord = $openRecord ?: CajaAperturaCierre::whereDate('datetime_apertura', $today)
                ->orderByDesc('datetime_apertura')
                ->first();

            return response()->json([
                'isOpen' => (bool) $openRecord,
                'hasRecordToday' => (bool) $latestRecord,
                'record' => $latestRecord ? $this->transformCaja($latestRecord) : null,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'No se pudo obtener el estado de la caja',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Registrar la apertura de caja.
     */
    public function abrir(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'monto_apertura' => 'required|numeric|min:0',
                'monto_apertura_billetes' => 'nullable|numeric|min:0',
                'monto_apertura_monedas' => 'nullable|numeric|min:0',
            ]);

            $today = now()->toDateString();

            $openRecord = CajaAperturaCierre::whereDate('datetime_apertura', $today)
                ->whereNull('datetime_cierre')
                ->orderByDesc('datetime_apertura')
                ->first();

            if ($openRecord) {
                return response()->json([
                    'error' => 'Ya existe una caja abierta en la fecha actual',
                ], 422);
            }

            $caja = CajaAperturaCierre::create([
                'datetime_apertura' => now(),
                'monto_apertura' => round($validated['monto_apertura'], 2),
                'monto_apertura_billetes' => isset($validated['monto_apertura_billetes']) ? round($validated['monto_apertura_billetes'], 2) : 0,
                'monto_apertura_monedas' => isset($validated['monto_apertura_monedas']) ? round($validated['monto_apertura_monedas'], 2) : 0,
                'id_usuario_apertura' => $request->user()->id,
            ]);

            $caja->load(['usuarioApertura', 'usuarioCierre']);

            return response()->json([
                'message' => 'Caja abierta correctamente',
                'caja' => $this->transformCaja($caja),
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'error' => 'Datos inválidos',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'No se pudo abrir la caja',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Registrar el cierre de la caja abierta.
     */
    public function cerrar(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'monto_cierre' => 'required|numeric|min:0',
                'monto_cierre_billetes' => 'nullable|numeric|min:0',
                'monto_cierre_monedas' => 'nullable|numeric|min:0',
            ]);

            $today = now()->toDateString();

            $caja = CajaAperturaCierre::whereDate('datetime_apertura', $today)
                ->whereNull('datetime_cierre')
                ->orderByDesc('datetime_apertura')
                ->first();

            if (! $caja) {
                return response()->json([
                    'error' => 'No hay una caja abierta para cerrar',
                ], 422);
            }

            $caja->update([
                'datetime_cierre' => now(),
                'monto_cierre' => round($validated['monto_cierre'], 2),
                'monto_cierre_billetes' => isset($validated['monto_cierre_billetes']) ? round($validated['monto_cierre_billetes'], 2) : 0,
                'monto_cierre_monedas' => isset($validated['monto_cierre_monedas']) ? round($validated['monto_cierre_monedas'], 2) : 0,
                'id_usuario_cierre' => $request->user()->id,
            ]);

            $caja->load(['usuarioApertura', 'usuarioCierre']);

            return response()->json([
                'message' => 'Caja cerrada correctamente',
                'caja' => $this->transformCaja($caja),
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'error' => 'Datos inválidos',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'No se pudo cerrar la caja',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtener los movimientos registrados para una fecha específica.
     */
    public function movimientos(Request $request): JsonResponse
    {
        try {
            // Obtener la fecha del query string, o usar la fecha actual por defecto
            $fecha = $request->query('fecha', now()->toDateString());
            
            // Validar que la fecha tenga formato correcto
            try {
                $fechaValidada = \Carbon\Carbon::parse($fecha)->toDateString();
            } catch (\Exception $e) {
                return response()->json([
                    'error' => 'Formato de fecha inválido',
                    'message' => 'La fecha debe tener el formato YYYY-MM-DD',
                ], 422);
            }

            $comprobantes = Comprobante::with(['user', 'pedido', 'metodoPago', 'pagos.metodoPago'])
                ->whereDate('fecha', $fechaValidada)
                ->orderByDesc('fecha')
                ->get();

            $records = $comprobantes->map(fn ($comprobante) => $this->transformComprobante($comprobante));

            $validComprobantes = $comprobantes->where('anulado', false)->where('tipo_comprobante', '!=', 'C');

            $pagosFlat = $validComprobantes->flatMap(function ($c) {
                if ($c->pagos->isNotEmpty()) {
                    return $c->pagos->map(fn ($p) => [
                        'metodo_pago_id' => $p->metodo_pago_id,
                        'metodo_pago' => $p->metodoPago?->nom_metodo_pago ?? 'No especificado',
                        'monto' => (float) $p->monto,
                    ]);
                }
                return collect([[
                    'metodo_pago_id' => $c->metodo_pago_id,
                    'metodo_pago' => $c->metodoPago?->nom_metodo_pago ?? 'No especificado',
                    'monto' => (float) $c->costo_total,
                ]]);
            });

            $porMetodo = $pagosFlat->groupBy('metodo_pago_id')->map(function ($items) {
                $first = $items->first();
                return [
                    'metodo_pago_id' => $first['metodo_pago_id'],
                    'metodo_pago' => $first['metodo_pago'],
                    'cantidad' => $items->count(),
                    'monto_total' => round($items->sum('monto'), 2),
                ];
            })->values();

            $summary = [
                'total_registros' => $validComprobantes->count(),
                'monto_total' => round((float) $validComprobantes->sum('costo_total'), 2),
                'por_metodo' => $porMetodo,
            ];

            return response()->json([
                'fecha_consultada' => $fechaValidada,
                'records' => $records,
                'summary' => $summary,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'No se pudieron obtener los movimientos',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtener el historial de aperturas y cierres de caja.
     */
    public function history(Request $request): JsonResponse
    {
        try {
            $limit = $request->query('limit', 10);
            
            $history = CajaAperturaCierre::with(['usuarioApertura', 'usuarioCierre'])
                ->orderByDesc('datetime_apertura')
                ->paginate($limit);

            $data = $history->getCollection()->map(fn ($caja) => $this->transformCaja($caja));

            return response()->json([
                'data' => $data,
                'current_page' => $history->currentPage(),
                'last_page' => $history->lastPage(),
                'total' => $history->total(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'No se pudo obtener el historial de caja',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Transform CajaAperturaCierre model to array.
     *
     * @param CajaAperturaCierre $caja
     * @return array<string, mixed>
     */
    private function transformCaja(CajaAperturaCierre $caja): array
    {
        return [
            'id' => $caja->id,
            'datetime_apertura' => $caja->datetime_apertura,
            'monto_apertura' => (float) $caja->monto_apertura,
            'monto_apertura_billetes' => (float) $caja->monto_apertura_billetes,
            'monto_apertura_monedas' => (float) $caja->monto_apertura_monedas,
            'id_usuario_apertura' => $caja->id_usuario_apertura,
            'usuario_apertura' => optional($caja->usuarioApertura)->name,
            'datetime_cierre' => $caja->datetime_cierre,
            'monto_cierre' => $caja->monto_cierre !== null ? (float) $caja->monto_cierre : null,
            'monto_cierre_billetes' => $caja->monto_cierre_billetes !== null ? (float) $caja->monto_cierre_billetes : null,
            'monto_cierre_monedas' => $caja->monto_cierre_monedas !== null ? (float) $caja->monto_cierre_monedas : null,
            'id_usuario_cierre' => $caja->id_usuario_cierre,
            'usuario_cierre' => optional($caja->usuarioCierre)->name,
        ];
    }

    /**
     * Transform Comprobante model to array.
     *
     * @param Comprobante $comprobante
     * @return array<string, mixed>
     */
    private function transformComprobante(Comprobante $comprobante): array
    {
        $usuario = $comprobante->user;
        $pedido = $comprobante->pedido;
        $pagos = $comprobante->pagos;

        $pagosArray = [];
        if ($pagos->isNotEmpty()) {
            $pagosArray = $pagos->map(function ($p) {
                return [
                    'metodo_pago_id' => $p->metodo_pago_id,
                    'metodo' => $p->metodoPago?->nom_metodo_pago ?? 'No especificado',
                    'monto' => (float) $p->monto,
                ];
            })->values()->toArray();
        } elseif ($comprobante->metodoPago) {
            $pagosArray = [
                [
                    'metodo_pago_id' => $comprobante->metodo_pago_id,
                    'metodo' => $comprobante->metodoPago->nom_metodo_pago,
                    'monto' => (float) $comprobante->costo_total,
                ]
            ];
        }

        $isMultiple = count($pagosArray) > 1;
        if ($isMultiple) {
            $metodoPagoTexto = 'MÚLTIPLE';
        } elseif (count($pagosArray) === 1) {
            $metodoPagoTexto = $pagosArray[0]['metodo'];
        } else {
            $metodoPagoTexto = $comprobante->metodoPago?->nom_metodo_pago ?? 'No especificado';
        }

        return [
            'id' => $comprobante->id,
            'fecha' => $comprobante->fecha,
            'cod_comprobante' => $comprobante->cod_comprobante,
            'monto' => (float) $comprobante->costo_total,
            'metodo_pago_id' => $isMultiple ? null : ($pagosArray[0]['metodo_pago_id'] ?? $comprobante->metodo_pago_id),
            'metodo_pago' => $metodoPagoTexto,
            'is_multiple' => $isMultiple,
            'pagos' => $pagosArray,
            'tipo_comprobante' => $comprobante->tipo_comprobante,
            'tipo_comprobante_nombre' => $comprobante->tipo_comprobante_name,
            'usuario' => $usuario->name ?? null,
            'tipo_atencion' => $pedido?->tipo_atencion ?? 'P', // P por defecto (Mesa)
            'sunat_success' => $comprobante->sunat_success,
            'sunat_error' => $comprobante->sunat_error,
            'sunat_description' => $comprobante->sunat_description,
            'anulado' => (bool) $comprobante->anulado,
        ];
    }
}
