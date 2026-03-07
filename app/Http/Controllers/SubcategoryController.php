<?php

namespace App\Http\Controllers;

use App\Models\Subcategory;
use Illuminate\Http\JsonResponse;

class SubcategoryController extends Controller
{
    /**
     * Return all subcategories.
     */
    public function index(): JsonResponse
    {
        $subcategories = Subcategory::with('category')->get();

        return response()->json([
            'data' => $subcategories,
        ]);
    }

    /**
     * Display the specified subcategory.
     */
    public function show(string $id): JsonResponse
    {
        $subcategory = Subcategory::with('category')->find($id);

        if (!$subcategory) {
            return response()->json([
                'message' => 'Subcategory not found',
            ], 404);
        }

        return response()->json([
            'data' => $subcategory,
        ]);
    }

    /**
     * Get all products for a specific subcategory.
     */
    public function products(string $id): JsonResponse
    {
        $subcategory = Subcategory::find($id);

        if (!$subcategory) {
            return response()->json([
                'message' => 'Subcategory not found',
            ], 404);
        }

        $products = $subcategory->products()
            ->with(['category', 'subcategory'])
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'data' => $products,
        ]);
    }
}
