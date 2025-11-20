<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    /**
     * Listar todas las categorías
     */
    public function index(): JsonResponse
    {
        try {
            $categories = Category::with(['creator', 'updater'])
                ->orderBy('id')
                ->get();

            return response()->json(
                $categories->map(function ($category) {
                    return $this->transformCategory($category);
                })
            );

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al obtener categorías',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener una categoría específica
     */
    public function show(Category $category): JsonResponse
    {
        try {
            $category->load(['creator', 'updater']);
            return response()->json($this->transformCategory($category));
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al obtener categoría',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crear una nueva categoría
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'is_active' => 'boolean',
            ]);

            $validated['created_by'] = $request->user()->id;
            $validated['updated_by'] = $request->user()->id;

            $category = Category::create($validated);
            $category->load(['creator', 'updater']);

            return response()->json([
                'message' => 'Categoría creada exitosamente',
                'category' => $this->transformCategory($category)
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al crear categoría',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar una categoría existente
     */
    public function update(Request $request, Category $category): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'sometimes|required|string|max:255',
                'description' => 'nullable|string',
                'is_active' => 'boolean',
            ]);

            $validated['updated_by'] = $request->user()->id;

            $category->update($validated);
            $category->load(['creator', 'updater']);

            return response()->json([
                'message' => 'Categoría actualizada exitosamente',
                'category' => $this->transformCategory($category)
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al actualizar categoría',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar una categoría
     */
    public function destroy(Category $category): JsonResponse
    {
        try {
            // Verificar que no tenga productos
            if ($category->products()->count() > 0) {
                return response()->json([
                    'error' => 'No se puede eliminar una categoría con productos asociados'
                ], 422);
            }

            $category->delete();

            return response()->json([
                'message' => 'Categoría eliminada exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al eliminar categoría',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Transformar categoría
     */
    private function transformCategory(Category $category): array
    {
        return [
            'id' => $category->id,
            'name' => $category->name,
            'description' => $category->description,
            'is_active' => $category->is_active,
            'products_count' => $category->products()->count(),
            'created_by' => $category->creator?->name,
            'updated_by' => $category->updater?->name,
            'created_at' => $category->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $category->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
