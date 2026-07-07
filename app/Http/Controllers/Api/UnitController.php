<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UnitRequest;
use App\Http\Resources\UnitResource;
use App\Models\Unit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UnitController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = min(max($request->integer('per_page', 15), 1), 100);

        $units = Unit::query()
            ->when($request->filled('q'), function ($query) use ($request) {
                $query->where('unit_name', 'like', '%'.$request->string('q').'%');
            })
            ->orderBy('unit_name')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Units retrieved successfully.',
            'data' => UnitResource::collection($units),
            'meta' => [
                'current_page' => $units->currentPage(),
                'last_page' => $units->lastPage(),
                'per_page' => $units->perPage(),
                'total' => $units->total(),
            ],
        ]);
    }

    public function store(UnitRequest $request): JsonResponse
    {
        $unit = Unit::create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Unit created successfully.',
            'data' => new UnitResource($unit),
        ], 201);
    }

    public function show(Unit $unit): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Unit retrieved successfully.',
            'data' => new UnitResource($unit),
        ]);
    }

    public function update(UnitRequest $request, Unit $unit): JsonResponse
    {
        $unit->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Unit updated successfully.',
            'data' => new UnitResource($unit),
        ]);
    }

    public function destroy(Unit $unit): JsonResponse
    {
        if ($unit->products()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Unit cannot be deleted because it is used by products.',
            ], 422);
        }

        $unit->delete();

        return response()->json([
            'success' => true,
            'message' => 'Unit deleted successfully.',
        ]);
    }
}
