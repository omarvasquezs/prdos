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

            return response()->json([
                'products' => $products->map(function ($product) {
                    return [
                        'id' => $product->id,
                        'name' => $product->name,
                        'description' => $product->description,
                        'price' => $product->price,
                        'formatted_price' => $product->formatted_price,
                        'category' => [
                            'id' => $product->category->id,
                            'name' => $product->category->name
                        ],
                        'is_available' => $product->is_available
                    ];
                })
            ]);

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

            return response()->json([
                'categories' => $categories->map(function ($category) {
                    return [
                        'id' => $category->id,
                        'name' => $category->name,
                        'description' => $category->description,
                        'order' => $category->order,
                        'products' => $category->activeProducts->map(function ($product) {
                            return [
                                'id' => $product->id,
                                'name' => $product->name,
                                'description' => $product->description,
                                'price' => $product->price,
                                'formatted_price' => $product->formatted_price,
                                'is_available' => $product->is_available
                            ];
                        })
                    ];
                })
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al obtener categorías',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
