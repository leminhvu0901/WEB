<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * Return every available product in the catalog.
     */
    public function index(): JsonResponse
    {
        $products = Product::query()
            ->with(['category', 'subcategory'])
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'data' => $products,
        ]);
    }

    /**
     * Store a newly created product.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'price' => 'required|numeric|min:0',
            'image' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'category_id' => 'required|integer|exists:categories,id',
            'subcategory_id' => 'required|integer|exists:subcategories,id',
            'location' => 'nullable|string|max:100',
            'tag' => 'nullable|string|max:50',
        ]);

        $product = Product::create($validated);

        return response()->json([
            'message' => 'Product created successfully',
            'data' => $product,
        ], 201);
    }

    /**
     * Display the specified product.
     */
    public function show(string $id): JsonResponse
    {
        $product = Product::with(['category', 'subcategory'])->find($id);

        if (!$product) {
            return response()->json([
                'message' => 'Product not found',
            ], 404);
        }

        return response()->json([
            'data' => $product,
        ]);
    }

    /**
     * Update the specified product.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json([
                'message' => 'Product not found',
            ], 404);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:150',
            'price' => 'sometimes|numeric|min:0',
            'image' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'category_id' => 'sometimes|integer|exists:categories,id',
            'subcategory_id' => 'sometimes|integer|exists:subcategories,id',
            'location' => 'nullable|string|max:100',
            'tag' => 'nullable|string|max:50',
        ]);

        $product->update($validated);

        return response()->json([
            'message' => 'Product updated successfully',
            'data' => $product,
        ]);
    }

    /**
     * Remove the specified product.
     */
    public function destroy(string $id): JsonResponse
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json([
                'message' => 'Product not found',
            ], 404);
        }

        $product->delete();

        return response()->json([
            'message' => 'Product deleted successfully',
        ]);
    }
}
