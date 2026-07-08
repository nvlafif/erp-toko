<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\OperatingCostRequest;
use App\Models\OperatingCost;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OperatingCostController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = min(max($request->integer('per_page', 15), 1), 100);

        $costs = OperatingCost::query()
            ->when($request->filled('q'), function ($query) use ($request) {
                $query->where('expense_name', 'like', '%'.$request->string('q').'%');
            })
            ->latest('expense_date')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Operating costs retrieved successfully.',
            'data' => $costs->getCollection()->map(fn (OperatingCost $cost) => [
                'id' => $cost->id,
                'expense_name' => $cost->expense_name,
                'amount' => $cost->amount,
                'expense_date' => $cost->expense_date,
                'created_at' => $cost->created_at,
                'updated_at' => $cost->updated_at,
            ]),
            'meta' => [
                'current_page' => $costs->currentPage(),
                'last_page' => $costs->lastPage(),
                'per_page' => $costs->perPage(),
                'total' => $costs->total(),
            ],
        ]);
    }

    public function store(OperatingCostRequest $request): JsonResponse
    {
        $cost = OperatingCost::create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Operating cost created successfully.',
            'data' => [
                'id' => $cost->id,
                'expense_name' => $cost->expense_name,
                'amount' => $cost->amount,
                'expense_date' => $cost->expense_date,
                'created_at' => $cost->created_at,
                'updated_at' => $cost->updated_at,
            ],
        ], 201);
    }

    public function show(OperatingCost $operatingCost): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Operating cost retrieved successfully.',
            'data' => [
                'id' => $operatingCost->id,
                'expense_name' => $operatingCost->expense_name,
                'amount' => $operatingCost->amount,
                'expense_date' => $operatingCost->expense_date,
                'created_at' => $operatingCost->created_at,
                'updated_at' => $operatingCost->updated_at,
            ],
        ]);
    }

    public function update(OperatingCostRequest $request, OperatingCost $operatingCost): JsonResponse
    {
        $operatingCost->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Operating cost updated successfully.',
            'data' => [
                'id' => $operatingCost->id,
                'expense_name' => $operatingCost->expense_name,
                'amount' => $operatingCost->amount,
                'expense_date' => $operatingCost->expense_date,
                'created_at' => $operatingCost->created_at,
                'updated_at' => $operatingCost->updated_at,
            ],
        ]);
    }

    public function destroy(OperatingCost $operatingCost): JsonResponse
    {
        $operatingCost->delete();

        return response()->json([
            'success' => true,
            'message' => 'Operating cost deleted successfully.',
        ]);
    }
}
