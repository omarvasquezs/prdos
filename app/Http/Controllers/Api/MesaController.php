<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Mesa;
use App\Models\Pedido;
use App\Models\PedidoItem;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class MesaController extends Controller
{
    /**
     * Listar todas las mesas
     */
    public function index(): JsonResponse
    {
        try {
            $mesas = Mesa::with('pedidoActivo')->get()->map(function ($mesa) {
                return [
                    'id' => $mesa->id,
                    'num_mesa' => $mesa->num_mesa,
                    'nombre' => $mesa->nombre,
                    'estado' => $mesa->estado,
                    'estado_texto' => $mesa->estado_texto,
                    'estado_color' => $mesa->estado_color,
                    'pedido_activo' => $mesa->pedidoActivo ? [
                        'id' => $mesa->pedidoActivo->id,
                        'comensales' => $mesa->pedidoActivo->comensales,
                        'fecha_apertura' => $mesa->pedidoActivo->fecha_apertura,
                        'total' => $mesa->pedidoActivo->calcularTotal()
                    ] : null
                ];
            });

            return response()->json($mesas);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al obtener las mesas',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener una mesa específica
     */
    public function show(Mesa $mesa): JsonResponse
    {
        try {
            $mesa->load(['pedidoActivo.items.producto']);

            return response()->json([
                'id' => $mesa->id,
                'num_mesa' => $mesa->num_mesa,
                'nombre' => $mesa->nombre,
                'estado' => $mesa->estado,
                'estado_texto' => $mesa->estado_texto,
                'estado_color' => $mesa->estado_color,
                'pedido_activo' => $mesa->pedidoActivo ? [
                    'id' => $mesa->pedidoActivo->id,
                    'comensales' => $mesa->pedidoActivo->comensales,
                    'fecha_apertura' => $mesa->pedidoActivo->fecha_apertura,
                    'total' => $mesa->pedidoActivo->calcularTotal(),
                    'items' => $mesa->pedidoActivo->items->map(function ($item) {
                        return [
                            'id' => $item->id,
                            'producto' => $item->producto->name,
                            'cantidad' => $item->cantidad,
                            'precio_unitario' => $item->precio_unitario,
                            'subtotal' => $item->subtotal
                        ];
                    })
                ] : null
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al obtener la mesa',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Ocupar una mesa
     */
    public function ocupar(Request $request, Mesa $mesa): JsonResponse
    {
        try {
            $validated = $request->validate([
                'comensales' => 'required|integer|min:1|max:20'
            ]);

            if ($mesa->isOcupada()) {
                return response()->json([
                    'error' => 'La mesa ya está ocupada'
                ], 422);
            }

            // Crear el pedido
            $pedido = Pedido::create([
                'tipo_atencion' => 'P', // Presencial
                'mesa_id' => $mesa->id,
                'comensales' => $validated['comensales'],
                'estado' => 'A',
                'fecha_apertura' => now(),
                'total' => 0.00
            ]);

            // Ocupar la mesa
            $mesa->ocupar();

            return response()->json([
                'message' => 'Mesa ocupada exitosamente',
                'mesa' => $mesa->fresh(),
                'pedido' => $pedido
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'error' => 'Datos inválidos',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al ocupar la mesa',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Liberar una mesa (cerrar pedido)
     */
    public function liberar(Mesa $mesa): JsonResponse
    {
        try {
            if ($mesa->isDisponible()) {
                return response()->json([
                    'error' => 'La mesa ya está disponible'
                ], 422);
            }

            $pedido = $mesa->pedidoActivo();
            if ($pedido) {
                $pedido->cerrar();
            }

            return response()->json([
                'message' => 'Mesa liberada exitosamente',
                'mesa' => $mesa->fresh()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al liberar la mesa',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener un pedido específico con sus items
     */
    public function getPedido(Pedido $pedido): JsonResponse
    {
        try {
            $pedido->load(['mesa', 'items.producto']);

            $response = [
                'id' => $pedido->id,
                'tipo_atencion' => $pedido->tipo_atencion,
                'comensales' => $pedido->comensales,
                'estado' => $pedido->estado,
                'estado_texto' => $pedido->estado_texto,
                'fecha_apertura' => $pedido->fecha_apertura,
                'fecha_cierre' => $pedido->fecha_cierre,
                'total' => $pedido->calcularTotal(),
                'items' => $pedido->items->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'producto_id' => $item->producto_id,
                        'producto' => $item->producto->name,
                        'cantidad' => $item->cantidad,
                        'precio_unitario' => $item->precio_unitario,
                        'subtotal' => $item->subtotal
                    ];
                })
            ];

            // Add mesa info only for presencial orders
            if ($pedido->tipo_atencion === 'P' && $pedido->mesa) {
                $response['mesa'] = [
                    'id' => $pedido->mesa->id,
                    'num_mesa' => $pedido->mesa->num_mesa,
                    'nombre' => "Mesa {$pedido->mesa->num_mesa}"
                ];
            }

            // Add delivery/recojo specific fields
            if ($pedido->tipo_atencion === 'D' || $pedido->tipo_atencion === 'R') {
                $response['cliente_nombre'] = $pedido->cliente_nombre;
                $response['cliente_telefono'] = $pedido->cliente_telefono;
                $response['notas'] = $pedido->notas;
                
                if ($pedido->tipo_atencion === 'D') {
                    $response['direccion_entrega'] = $pedido->direccion_entrega;
                    $response['costo_delivery'] = (float) ($pedido->costo_delivery ?? 0);
                }
            }

            return response()->json($response);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al obtener el pedido',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Agregar item a un pedido
     */
    public function agregarItem(Request $request, Pedido $pedido): JsonResponse
    {
        try {
            $validated = $request->validate([
                'producto_id' => 'required|exists:products,id',
                'cantidad' => 'required|integer|min:1'
            ]);

            // Obtener el producto para el precio actual
            $producto = \App\Models\Product::find($validated['producto_id']);

            // Verificar si el item ya existe en el pedido
            $itemExistente = $pedido->items()->where('producto_id', $producto->id)->first();

            if ($itemExistente) {
                // Si existe, incrementar la cantidad
                $itemExistente->update([
                    'cantidad' => $itemExistente->cantidad + $validated['cantidad']
                ]);
                $item = $itemExistente;
            } else {
                // Si no existe, crear nuevo item
                $item = $pedido->items()->create([
                    'producto_id' => $producto->id,
                    'cantidad' => $validated['cantidad'],
                    'precio_unitario' => $producto->price
                ]);
            }

            // Decrementar stock
            $producto->decrementStock($validated['cantidad']);

            // Actualizar el total del pedido
            $pedido->update(['total' => $pedido->calcularTotal()]);

            return response()->json([
                'message' => 'Item agregado exitosamente',
                'item' => [
                    'id' => $item->id,
                    'producto' => $producto->name,
                    'cantidad' => $item->cantidad,
                    'precio_unitario' => $item->precio_unitario,
                    'subtotal' => $item->subtotal
                ],
                'pedido_total' => $pedido->fresh()->calcularTotal()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al agregar item al pedido',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar item de un pedido
     */
    public function eliminarItem(Pedido $pedido, \App\Models\PedidoItem $item): JsonResponse
    {
        try {
            // Verificar que el item pertenece al pedido
            if ($item->pedido_id !== $pedido->id) {
                return response()->json([
                    'error' => 'El item no pertenece a este pedido'
                ], 422);
            }

            // Restaurar stock
            $item->producto->incrementStock($item->cantidad);

            $item->delete();

            // Actualizar el total del pedido
            $pedido->update(['total' => $pedido->calcularTotal()]);

            return response()->json([
                'message' => 'Item eliminado exitosamente',
                'pedido_total' => $pedido->fresh()->calcularTotal()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al eliminar item del pedido',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cerrar pedido (cobrar)
     */
    public function cobrarPedido(Pedido $pedido): JsonResponse
    {
        try {
            if ($pedido->estado !== 'A') {
                return response()->json([
                    'error' => 'Solo se pueden cobrar pedidos abiertos'
                ], 422);
            }

            $pedido->cerrar();

            return response()->json([
                'message' => 'Pedido cobrado exitosamente',
                'pedido' => [
                    'id' => $pedido->id,
                    'estado' => $pedido->estado,
                    'fecha_cierre' => $pedido->fecha_cierre,
                    'total' => $pedido->total
                ],
                'mesa' => $pedido->mesa->fresh()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al cobrar el pedido',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cancelar pedido
     */
    public function cancelarPedido(Pedido $pedido): JsonResponse
    {
        try {
            \Illuminate\Support\Facades\Log::info("Intentando cancelar pedido ID: {$pedido->id}, Estado: {$pedido->estado}");

            if ($pedido->estado !== 'A') {
                return response()->json([
                    'error' => 'Solo se pueden cancelar pedidos abiertos'
                ], 422);
            }

            \Illuminate\Support\Facades\DB::transaction(function () use ($pedido) {
                $pedido->cancelar();
            });

            return response()->json([
                'message' => 'Pedido cancelado exitosamente',
                'pedido' => [
                    'id' => $pedido->id,
                    'estado' => $pedido->estado,
                    'fecha_cierre' => $pedido->fecha_cierre
                ],
                'mesa' => $pedido->mesa?->fresh()
            ]);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Error al cancelar pedido ID {$pedido->id}: " . $e->getMessage());
            \Illuminate\Support\Facades\Log::error($e->getTraceAsString());
            
            return response()->json([
                'error' => 'Error al cancelar el pedido',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
