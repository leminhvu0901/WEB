<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    /**
     * Return all categories.
     */
    public function index(): JsonResponse
    {
        $categories = Category::with('subcategories')->get();

        return response()->json([
            'data' => $categories,
        ]);
    }

    /**
     * Display the specified category with its subcategories.
     */
    public function show(string $id): JsonResponse
    {
        $category = Category::with('subcategories')->find($id);

        if (!$category) {
            return response()->json([
                'message' => 'Category not found',
            ], 404);
        }

        return response()->json([
            'data' => $category,
        ]);
    }

    /**
     * Get all products for a specific category.
     */
    public function products(string $id): JsonResponse
    {
        $category = Category::find($id);

        if (!$category) {
            return response()->json([
                'message' => 'Category not found',
            ], 404);
        }

        $products = $category->products()
            ->with(['category', 'subcategory'])
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'data' => $products,
        ]);
    }
}
