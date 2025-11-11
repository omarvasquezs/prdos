<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CajaAperturaCierre;
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

    private function transformCaja(CajaAperturaCierre $caja): array
    {
        return [
            'id' => $caja->id,
            'datetime_apertura' => $caja->datetime_apertura,
            'monto_apertura' => (float) $caja->monto_apertura,
            'id_usuario_apertura' => $caja->id_usuario_apertura,
            'usuario_apertura' => optional($caja->usuarioApertura)->name,
            'datetime_cierre' => $caja->datetime_cierre,
            'monto_cierre' => $caja->monto_cierre !== null ? (float) $caja->monto_cierre : null,
            'id_usuario_cierre' => $caja->id_usuario_cierre,
            'usuario_cierre' => optional($caja->usuarioCierre)->name,
        ];
    }
}
