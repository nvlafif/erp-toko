<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CheckoutHoldTransactionRequest;
use App\Http\Requests\StoreHoldTransactionRequest;
use App\Http\Resources\HoldTransactionResource;
use App\Http\Resources\TransactionResource;
use App\Models\HoldTransaction;
use App\Models\Product;
use App\Services\SalesTransactionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class HoldTransactionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = min(max($request->integer('per_page', 15), 1), 100);

        $holdTransactions = HoldTransaction::with(['user', 'holdTransactionDetails.product'])
            ->latest('transaction_date')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Held transactions retrieved successfully.',
            'data' => HoldTransactionResource::collection($holdTransactions),
            'meta' => [
                'current_page' => $holdTransactions->currentPage(),
                'last_page' => $holdTransactions->lastPage(),
                'per_page' => $holdTransactions->perPage(),
                'total' => $holdTransactions->total(),
            ],
        ]);
    }

    public function store(StoreHoldTransactionRequest $request): JsonResponse
    {
        $holdTransaction = DB::transaction(function () use ($request) {
            $items = collect($request->validated('items'));
            $productIds = $items->pluck('product_id')->all();

            $products = Product::whereIn('id', $productIds)
                ->where('is_active', true)
                ->get()
                ->keyBy('id');

            if ($products->count() !== count(array_unique($productIds))) {
                throw ValidationException::withMessages([
                    'items' => ['One or more products are invalid or inactive.'],
                ]);
            }

            $totalPayment = 0;
            $details = [];

            foreach ($items as $item) {
                $product = $products->get($item['product_id']);
                $quantity = (int) $item['quantity'];
                $sellingPrice = (float) $product->selling_price;
                $subtotal = $sellingPrice * $quantity;
                $totalPayment += $subtotal;

                $details[] = [
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'selling_price' => $sellingPrice,
                    'subtotal' => $subtotal,
                ];
            }

            $holdTransaction = HoldTransaction::create([
                'user_id' => $request->user()->id,
                'transaction_date' => now(),
                'total_payment' => $totalPayment,
                'customer_money' => 0,
                'change_money' => 0,
            ]);

            foreach ($details as $detail) {
                $holdTransaction->holdTransactionDetails()->create($detail);
            }

            return $holdTransaction;
        });

        $holdTransaction->load(['user', 'holdTransactionDetails.product']);

        return response()->json([
            'success' => true,
            'message' => 'Transaction held successfully.',
            'data' => new HoldTransactionResource($holdTransaction),
        ], 201);
    }

    public function show(HoldTransaction $holdTransaction): JsonResponse
    {
        $holdTransaction->load(['user', 'holdTransactionDetails.product']);

        return response()->json([
            'success' => true,
            'message' => 'Held transaction retrieved successfully.',
            'data' => new HoldTransactionResource($holdTransaction),
        ]);
    }

    public function checkout(
        CheckoutHoldTransactionRequest $request,
        HoldTransaction $holdTransaction,
        SalesTransactionService $salesTransactionService
    ): JsonResponse {
        $transaction = DB::transaction(function () use ($request, $holdTransaction, $salesTransactionService) {
            $holdTransaction->load('holdTransactionDetails');

            $items = $holdTransaction->holdTransactionDetails->map(fn ($detail) => [
                'product_id' => $detail->product_id,
                'quantity' => (int) $detail->quantity,
            ]);

            $transaction = $salesTransactionService->create(
                user: $request->user(),
                items: $items,
                customerMoney: (float) $request->validated('customer_money'),
            );

            $holdTransaction->delete();

            return $transaction;
        });

        $transaction->load(['user', 'transactionDetails.product']);

        return response()->json([
            'success' => true,
            'message' => 'Held transaction checked out successfully.',
            'data' => new TransactionResource($transaction),
        ], 201);
    }

    public function destroy(HoldTransaction $holdTransaction): JsonResponse
    {
        $holdTransaction->delete();

        return response()->json([
            'success' => true,
            'message' => 'Held transaction deleted successfully.',
        ]);
    }
}
