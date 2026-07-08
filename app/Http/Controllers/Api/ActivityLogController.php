<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = min(max($request->integer('per_page', 15), 1), 100);

        $logs = ActivityLog::with('user')
            ->latest('activity_date')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Activity logs retrieved successfully.',
            'data' => $logs->getCollection()->map(function (ActivityLog $log) {
                return [
                    'id' => $log->id,
                    'user' => $log->user ? [
                        'id' => $log->user->id,
                        'name' => $log->user->name,
                        'username' => $log->user->username,
                        'role' => $log->user->role,
                    ] : null,
                    'activity' => $log->activity,
                    'activity_date' => $log->activity_date,
                    'created_at' => $log->created_at,
                ];
            }),
            'meta' => [
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
                'per_page' => $logs->perPage(),
                'total' => $logs->total(),
            ],
        ]);
    }
}
