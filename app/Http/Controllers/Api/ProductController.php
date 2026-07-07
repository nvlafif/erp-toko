<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\JsonResponse;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $products = Product::with(['category', 'supplier', 'unit'])
            ->where('is_active', true)
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Products retrieved successfully.',
            'data' => ProductResource::collection($products),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ProductRequest $request): JsonResponse
    {
        $product = Product::create($request->validated());

        $product->load([
            'category',
            'supplier',
            'unit',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Product created successfully.',
            'data' => new ProductResource($product),
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Product $product): JsonResponse
    {
        $product->load([
            'category',
            'supplier',
            'unit',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Product retrieved successfully.',
            'data' => new ProductResource($product),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ProductRequest $request, Product $product): JsonResponse
    {
        $product->update($request->validated());

        $product->load([
            'category',
            'supplier',
            'unit',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Product updated successfully.',
            'data' => new ProductResource($product),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product): JsonResponse
    {
        $product->update([
            'is_active' => false,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Product deactivated successfully.',
        ]);
    }
}
