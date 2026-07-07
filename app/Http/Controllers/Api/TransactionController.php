<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTransactionRequest;
use App\Http\Resources\TransactionResource;
use App\Models\Transaction;
use App\Services\SalesTransactionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);

        $perPage = min(max($request->integer('per_page', 15), 1), 100);

        $transactions = Transaction::with(['user', 'transactionDetails.product'])
            ->when($request->filled('date_from'), function ($query) use ($request) {
                $query->whereDate('transaction_date', '>=', $request->date('date_from'));
            })
            ->when($request->filled('date_to'), function ($query) use ($request) {
                $query->whereDate('transaction_date', '<=', $request->date('date_to'));
            })
            ->latest('transaction_date')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Transactions retrieved successfully.',
            'data' => TransactionResource::collection($transactions),
            'meta' => [
                'current_page' => $transactions->currentPage(),
                'last_page' => $transactions->lastPage(),
                'per_page' => $transactions->perPage(),
                'total' => $transactions->total(),
            ],
        ]);
    }

    public function store(StoreTransactionRequest $request, SalesTransactionService $salesTransactionService): JsonResponse
    {
        $transaction = $salesTransactionService->create(
            user: $request->user(),
            items: $request->validated('items'),
            customerMoney: (float) $request->validated('customer_money'),
        );

        $transaction->load(['user', 'transactionDetails.product']);

        return response()->json([
            'success' => true,
            'message' => 'Transaction created successfully.',
            'data' => new TransactionResource($transaction),
        ], 201);
    }

    public function show(Transaction $transaction): JsonResponse
    {
        $transaction->load(['user', 'transactionDetails.product']);

        return response()->json([
            'success' => true,
            'message' => 'Transaction retrieved successfully.',
            'data' => new TransactionResource($transaction),
        ]);
    }
}
