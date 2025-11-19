<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ProductController extends Controller
{
    /**
     * Listar productos por categoría
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $categoryId = $request->get('category_id');
            
            $products = Product::with('category')
                ->when($categoryId, function ($query, $categoryId) {
                    return $query->where('category_id', $categoryId);
                })
                ->available()
                ->ordered()
                ->get();

            return response()->json(
                $products->map(function ($product) {
                    return [
                        'id' => $product->id,
                        'nombre' => $product->name,
                        'descripcion' => $product->description,
                        'precio' => $product->price,
                        'precio_formateado' => $product->formatted_price,
                        'categoria' => [
                            'id' => $product->category->id,
                            'nombre' => $product->category->name
                        ],
                        'disponible' => $product->is_available
                    ];
                })
            );

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al obtener productos',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Listar categorías con productos
     */
    public function categories(): JsonResponse
    {
        try {
            $categories = Category::with(['activeProducts' => function($query) {
                $query->available()->ordered();
            }])
            ->active()
            ->ordered()
            ->get();

            return response()->json(
                $categories->map(function ($category) {
                    return [
                        'id' => $category->id,
                        'nombre' => $category->name,
                        'descripcion' => $category->description,
                        'orden' => $category->order,
                        'productos' => $category->activeProducts->map(function ($product) {
                            return [
                                'id' => $product->id,
                                'nombre' => $product->name,
                                'descripcion' => $product->description,
                                'precio' => $product->price,
                                'precio_formateado' => $product->formatted_price,
                                'disponible' => $product->is_available
                            ];
                        })
                    ];
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
     * Listar todos los productos con paginación (para admin)
     */
    public function adminIndex(Request $request): JsonResponse
    {
        try {
            $perPage = $request->get('per_page', 15);
            $search = $request->get('search');
            $categoryId = $request->get('category_id');

            $products = Product::with(['category', 'creator', 'updater'])
                ->when($search, function ($query, $search) {
                    return $query->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                })
                ->when($categoryId, function ($query, $categoryId) {
                    return $query->where('category_id', $categoryId);
                })
                ->orderBy('category_id')
                ->orderBy('order')
                ->paginate($perPage);

            return response()->json([
                'data' => $products->map(function ($product) {
                    return $this->transformProductAdmin($product);
                }),
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al obtener productos',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener un producto específico
     */
    public function show(Product $product): JsonResponse
    {
        try {
            $product->load(['category', 'creator', 'updater']);
            return response()->json($this->transformProductAdmin($product));
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al obtener producto',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crear un nuevo producto
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'price' => 'required|numeric|min:0',
                'category_id' => 'required|exists:categories,id',
                'is_available' => 'boolean',
                'order' => 'nullable|integer|min:0',
            ]);

            $validated['created_by'] = $request->user()->id;
            $validated['updated_by'] = $request->user()->id;
            
            // Si no se proporciona orden, usar el siguiente disponible
            if (!isset($validated['order'])) {
                $validated['order'] = Product::where('category_id', $validated['category_id'])
                    ->max('order') + 1;
            }

            $product = Product::create($validated);
            $product->load(['category', 'creator', 'updater']);

            return response()->json([
                'message' => 'Producto creado exitosamente',
                'product' => $this->transformProductAdmin($product)
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al crear producto',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar un producto existente
     */
    public function update(Request $request, Product $product): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'sometimes|required|string|max:255',
                'description' => 'nullable|string',
                'price' => 'sometimes|required|numeric|min:0',
                'category_id' => 'sometimes|required|exists:categories,id',
                'is_available' => 'boolean',
                'order' => 'nullable|integer|min:0',
            ]);

            $validated['updated_by'] = $request->user()->id;

            $product->update($validated);
            $product->load(['category', 'creator', 'updater']);

            return response()->json([
                'message' => 'Producto actualizado exitosamente',
                'product' => $this->transformProductAdmin($product)
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al actualizar producto',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar un producto
     */
    public function destroy(Product $product): JsonResponse
    {
        try {
            $product->delete();

            return response()->json([
                'message' => 'Producto eliminado exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al eliminar producto',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Transformar producto para admin
     */
    private function transformProductAdmin(Product $product): array
    {
        return [
            'id' => $product->id,
            'name' => $product->name,
            'description' => $product->description,
            'price' => (float) $product->price,
            'price_formatted' => 'S/ ' . number_format($product->price, 2),
            'category_id' => $product->category_id,
            'category' => [
                'id' => $product->category->id,
                'name' => $product->category->name
            ],
            'is_available' => $product->is_available,
            'order' => $product->order,
            'created_by' => $product->creator?->name,
            'updated_by' => $product->updater?->name,
            'created_at' => $product->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $product->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
