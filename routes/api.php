<?php

use App\Http\Controllers\Api\ActivityLogController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\HoldTransactionController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\OperatingCostController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ProductReturnController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\StockMovementController;
use App\Http\Controllers\Api\SupplierController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\UnitController;
use Illuminate\Support\Facades\Route;

Route::post('auth/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('auth/me', [AuthController::class, 'me']);
    Route::post('auth/logout', [AuthController::class, 'logout']);
    Route::get('notifications', [NotificationController::class, 'index']);
    Route::patch('notifications/{notification}/read', [NotificationController::class, 'markAsRead']);
    Route::post('auth/register', [AuthController::class, 'register'])->middleware('role:owner');
    Route::patch('users/{user}', [AuthController::class, 'updateUserStatus'])->middleware('role:owner');

    Route::get('activity-logs', [ActivityLogController::class, 'index'])->middleware('role:owner');
    Route::apiResource('operating-costs', OperatingCostController::class)->middleware('role:owner');
    Route::get('stock-movements', [StockMovementController::class, 'index'])->middleware('role:owner,admin_gudang');
    Route::get('reports/summary', [ReportController::class, 'summary'])->middleware('role:owner');
    Route::get('reports/dashboard', [ReportController::class, 'dashboard'])->middleware('role:owner');

    Route::apiResource('categories', CategoryController::class)->only(['index', 'show']);
    Route::apiResource('suppliers', SupplierController::class)->only(['index', 'show']);
    Route::apiResource('units', UnitController::class)->only(['index', 'show']);
    Route::apiResource('products', ProductController::class)->only(['index', 'show']);
    Route::post('products/{product}/check-low-stock', [ProductController::class, 'checkLowStock']);
    Route::post('products/{product}/adjust-stock', [ProductController::class, 'adjustStock'])->middleware('role:owner,admin_gudang');
    Route::get('products/{product}/audit', [ProductController::class, 'audit'])->middleware('role:owner');
    Route::apiResource('transactions', TransactionController::class)->only(['index', 'store', 'show'])
        ->middleware('role:owner,kasir');
    Route::apiResource('returns', ProductReturnController::class)->only(['index', 'store', 'show'])
        ->parameters(['returns' => 'productReturn'])
        ->middleware('role:owner,kasir');
    Route::post('hold-transactions/{holdTransaction}/checkout', [HoldTransactionController::class, 'checkout'])
        ->middleware('role:owner,kasir');
    Route::apiResource('hold-transactions', HoldTransactionController::class)->only(['index', 'store', 'show', 'destroy'])
        ->parameters(['hold-transactions' => 'holdTransaction'])
        ->middleware('role:owner,kasir');

    Route::middleware('role:owner,admin_gudang')->group(function () {
        Route::apiResource('categories', CategoryController::class)->except(['index', 'show']);
        Route::apiResource('suppliers', SupplierController::class)->except(['index', 'show']);
        Route::apiResource('units', UnitController::class)->except(['index', 'show']);
        Route::apiResource('products', ProductController::class)->except(['index', 'show']);
    });
});
