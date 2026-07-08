<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OperatingCost;
use App\Models\Transaction;
use App\Models\TransactionDetail;
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

    public function dashboard(Request $request): JsonResponse
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

        $transactions = $query->with('transactionDetails.product')->get();
        $totalSales = (float) $transactions->sum('total_payment');

        $topProducts = TransactionDetail::query()
            ->selectRaw('product_id, SUM(quantity) as total_quantity, SUM(subtotal) as total_sales')
            ->when($request->filled('date_from'), function ($query) use ($request) {
                $query->whereHas('transaction', function ($transactionQuery) use ($request) {
                    $transactionQuery->whereDate('transaction_date', '>=', $request->date('date_from'));
                });
            })
            ->when($request->filled('date_to'), function ($query) use ($request) {
                $query->whereHas('transaction', function ($transactionQuery) use ($request) {
                    $transactionQuery->whereDate('transaction_date', '<=', $request->date('date_to'));
                });
            })
            ->groupBy('product_id')
            ->with('product')
            ->orderByDesc('total_quantity')
            ->limit(5)
            ->get()
            ->map(function ($detail) {
                return [
                    'product_id' => $detail->product_id,
                    'product_name' => $detail->product?->product_name,
                    'quantity_sold' => (int) $detail->total_quantity,
                    'sales_amount' => number_format((float) $detail->total_sales, 2, '.', ''),
                ];
            });

        return response()->json([
            'success' => true,
            'message' => 'Dashboard report retrieved successfully.',
            'data' => [
                'total_sales' => number_format($totalSales, 2, '.', ''),
                'transaction_count' => $transactions->count(),
                'top_products' => $topProducts,
            ],
        ]);
    }
}
