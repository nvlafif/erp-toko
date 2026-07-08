<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StockMovement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StockMovementController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = min(max($request->integer('per_page', 15), 1), 100);

        $movements = StockMovement::with('product')
            ->when($request->filled('product_id'), fn ($query) => $query->where('product_id', $request->integer('product_id')))
            ->latest('movement_date')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Stock movements retrieved successfully.',
            'data' => $movements->getCollection()->map(fn (StockMovement $movement) => [
                'id' => $movement->id,
                'product' => [
                    'id' => $movement->product?->id,
                    'product_name' => $movement->product?->product_name,
                ],
                'movement_type' => $movement->movement_type,
                'quantity' => $movement->quantity,
                'reference_type' => $movement->reference_type,
                'reference_id' => $movement->reference_id,
                'movement_date' => $movement->movement_date,
                'description' => $movement->description,
            ]),
            'meta' => [
                'current_page' => $movements->currentPage(),
                'last_page' => $movements->lastPage(),
                'per_page' => $movements->perPage(),
                'total' => $movements->total(),
            ],
        ]);
    }
}
