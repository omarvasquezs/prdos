<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pedido;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PedidoController extends Controller
{
    /**
     * Obtener lista de pedidos por tipo de atención
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $tipoAtencion = $request->query('tipo_atencion', null);
            $estado = $request->query('estado', 'A'); // Default: Abiertos

            $query = Pedido::with(['items.producto'])
                ->where('estado', $estado);

            if ($tipoAtencion) {
                $query->where('tipo_atencion', $tipoAtencion);
            }

            $pedidos = $query->orderByDesc('fecha_apertura')->get();

            $pedidosFormateados = $pedidos->map(function ($pedido) {
                return $this->transformPedido($pedido);
            });

            return response()->json([
                'pedidos' => $pedidosFormateados,
                'total' => $pedidos->count(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'No se pudieron obtener los pedidos',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Crear un nuevo pedido de delivery o recojo
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'tipo_atencion' => 'required|in:D,R',
                'cliente_nombre' => 'required|string|max:255',
                'cliente_telefono' => 'required_if:tipo_atencion,D|nullable|string|max:20',
                'direccion_entrega' => 'required_if:tipo_atencion,D|nullable|string',
                'notas' => 'nullable|string',
            ]);

            DB::beginTransaction();

            $pedido = Pedido::create([
                'tipo_atencion' => $validated['tipo_atencion'],
                'cliente_nombre' => $validated['cliente_nombre'],
                'cliente_telefono' => $validated['cliente_telefono'],
                'direccion_entrega' => $validated['direccion_entrega'] ?? null,
                'notas' => $validated['notas'] ?? null,
                'comensales' => 1, // Default for delivery/recojo
                'estado' => 'A',
                'fecha_apertura' => now(),
                'total' => 0,
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Pedido creado correctamente',
                'pedido' => $this->transformPedido($pedido),
            ], 201);
        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Datos inválidos',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'No se pudo crear el pedido',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtener un pedido específico
     */
    public function show($id): JsonResponse
    {
        try {
            $pedido = Pedido::with(['items.producto'])->findOrFail($id);

            return response()->json([
                'pedido' => $this->transformPedido($pedido),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Pedido no encontrado',
                'message' => $e->getMessage(),
            ], 404);
        }
    }

    /**
     * Agregar item a un pedido
     */
    public function addItem(Request $request, $pedidoId): JsonResponse
    {
        try {
            $validated = $request->validate([
                'producto_id' => 'required|exists:products,id',
                'cantidad' => 'required|integer|min:1',
            ]);

            $pedido = Pedido::findOrFail($pedidoId);

            if (!$pedido->isAbierto()) {
                return response()->json([
                    'error' => 'No se pueden agregar items a un pedido cerrado',
                ], 422);
            }

            $producto = Product::findOrFail($validated['producto_id']);

            DB::beginTransaction();

            $pedido->items()->create([
                'producto_id' => $producto->id,
                'cantidad' => $validated['cantidad'],
                'precio_unitario' => $producto->price,
                'subtotal' => $producto->price * $validated['cantidad'],
            ]);

            // Actualizar total del pedido
            $pedido->update(['total' => $pedido->calcularTotal()]);

            DB::commit();

            $pedido->load('items.producto');

            return response()->json([
                'message' => 'Item agregado correctamente',
                'pedido' => $this->transformPedido($pedido),
            ]);
        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Datos inválidos',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'No se pudo agregar el item',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Eliminar item de un pedido
     */
    public function removeItem($pedidoId, $itemId): JsonResponse
    {
        try {
            $pedido = Pedido::findOrFail($pedidoId);

            if (!$pedido->isAbierto()) {
                return response()->json([
                    'error' => 'No se pueden eliminar items de un pedido cerrado',
                ], 422);
            }

            DB::beginTransaction();

            $item = $pedido->items()->findOrFail($itemId);
            $item->delete();

            // Actualizar total del pedido
            $pedido->update(['total' => $pedido->calcularTotal()]);

            DB::commit();

            $pedido->load('items.producto');

            return response()->json([
                'message' => 'Item eliminado correctamente',
                'pedido' => $this->transformPedido($pedido),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'No se pudo eliminar el item',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Cancelar un pedido
     */
    public function cancel($pedidoId): JsonResponse
    {
        try {
            $pedido = Pedido::findOrFail($pedidoId);

            if (!$pedido->isAbierto()) {
                return response()->json([
                    'error' => 'Solo se pueden cancelar pedidos abiertos',
                ], 422);
            }

            DB::beginTransaction();

            $pedido->cancelar();

            DB::commit();

            return response()->json([
                'message' => 'Pedido cancelado correctamente',
                'pedido' => $this->transformPedido($pedido),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'No se pudo cancelar el pedido',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Transform Pedido model to array
     */
    private function transformPedido(Pedido $pedido): array
    {
        return [
            'id' => $pedido->id,
            'tipo_atencion' => $pedido->tipo_atencion,
            'tipo_atencion_texto' => $pedido->tipo_atencion_texto,
            'cliente_nombre' => $pedido->cliente_nombre,
            'cliente_telefono' => $pedido->cliente_telefono,
            'direccion_entrega' => $pedido->direccion_entrega,
            'notas' => $pedido->notas,
            'comensales' => $pedido->comensales,
            'estado' => $pedido->estado,
            'estado_texto' => $pedido->estado_texto,
            'fecha_apertura' => $pedido->fecha_apertura,
            'fecha_cierre' => $pedido->fecha_cierre,
            'total' => (float) $pedido->total,
            'items' => $pedido->items->map(function ($item) {
                return [
                    'id' => $item->id,
                    'producto_id' => $item->producto_id,
                    'producto_nombre' => $item->producto->name,
                    'cantidad' => $item->cantidad,
                    'precio_unitario' => (float) $item->precio_unitario,
                    'subtotal' => (float) $item->subtotal,
                ];
            }),
        ];
    }
}
