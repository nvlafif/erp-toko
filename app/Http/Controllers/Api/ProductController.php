<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Notification;
use App\Models\Product;
use App\Models\ProductAudit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'stock_status' => ['nullable', 'in:low,out'],
            'expired_before' => ['nullable', 'date'],
        ]);

        $perPage = min(max($request->integer('per_page', 15), 1), 100);

        $products = Product::with(['category', 'supplier', 'unit'])
            ->where('is_active', true)
            ->when($request->filled('q'), function ($query) use ($request) {
                $keyword = $request->string('q');

                $query->where(function ($query) use ($keyword) {
                    $query
                        ->where('product_name', 'like', '%'.$keyword.'%')
                        ->orWhere('barcode', 'like', '%'.$keyword.'%');
                });
            })
            ->when($request->filled('category_id'), function ($query) use ($request) {
                $query->where('category_id', $request->integer('category_id'));
            })
            ->when($request->filled('supplier_id'), function ($query) use ($request) {
                $query->where('supplier_id', $request->integer('supplier_id'));
            })
            ->when($request->filled('unit_id'), function ($query) use ($request) {
                $query->where('unit_id', $request->integer('unit_id'));
            })
            ->when($request->input('stock_status') === 'out', function ($query) {
                $query->where('stock', 0);
            })
            ->when($request->input('stock_status') === 'low', function ($query) {
                $query->where('stock', '>', 0)->where('stock', '<=', 10);
            })
            ->when($request->filled('expired_before'), function ($query) use ($request) {
                $query->whereDate('expired_date', '<=', $request->date('expired_before'));
            })
            ->orderBy('product_name')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Products retrieved successfully.',
            'data' => ProductResource::collection($products),
            'meta' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
            ],
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

    public function checkLowStock(Product $product): JsonResponse
    {
        if ($product->stock <= 10) {
            Notification::create([
                'user_id' => auth()->id(),
                'title' => 'Low stock alert',
                'message' => $product->product_name.' is running low (current stock: '.$product->stock.').',
                'type' => 'inventory',
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Low stock check completed.',
            'data' => [
                'product_id' => $product->id,
                'stock' => $product->stock,
                'alert_created' => $product->stock <= 10,
            ],
        ]);
    }

    public function audit(Product $product): JsonResponse
    {
        $audits = ProductAudit::query()
            ->where('product_id', $product->id)
            ->with('user')
            ->latest()
            ->paginate(15);

        return response()->json([
            'success' => true,
            'message' => 'Product audit history retrieved successfully.',
            'data' => $audits->getCollection()->map(fn (ProductAudit $audit) => [
                'id' => $audit->id,
                'action' => $audit->action,
                'changes' => $audit->changes,
                'user' => $audit->user ? [
                    'id' => $audit->user->id,
                    'name' => $audit->user->name,
                    'username' => $audit->user->username,
                ] : null,
                'created_at' => $audit->created_at,
            ]),
            'meta' => [
                'current_page' => $audits->currentPage(),
                'last_page' => $audits->lastPage(),
                'per_page' => $audits->perPage(),
                'total' => $audits->total(),
            ],
        ]);
    }
}
