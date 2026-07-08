<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OperatingCost;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function summary(Request $request): JsonResponse
    {
        $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);

        $query = Transaction::query();

        if ($request->filled('date_from')) {
            $query->whereDate('transaction_date', '>=', $request->date('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('transaction_date', '<=', $request->date('date_to'));
        }

        $grossSales = (float) $query->sum('total_payment');
        $transactionCount = (int) $query->count();

        $operatingCostsQuery = OperatingCost::query();

        if ($request->filled('date_from')) {
            $operatingCostsQuery->whereDate('expense_date', '>=', $request->date('date_from'));
        }

        if ($request->filled('date_to')) {
            $operatingCostsQuery->whereDate('expense_date', '<=', $request->date('date_to'));
        }

        $operatingCosts = (float) $operatingCostsQuery->sum('amount');

        return response()->json([
            'success' => true,
            'message' => 'Financial summary retrieved successfully.',
            'data' => [
                'gross_sales' => number_format($grossSales, 2, '.', ''),
                'operating_costs' => number_format($operatingCosts, 2, '.', ''),
                'net_profit' => number_format($grossSales - $operatingCosts, 2, '.', ''),
                'transaction_count' => $transactionCount,
            ],
        ]);
    }
}
