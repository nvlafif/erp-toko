<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTransactionRequest;
use App\Http\Resources\TransactionResource;
use App\Models\Product;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

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

    public function store(StoreTransactionRequest $request): JsonResponse
    {
        $transaction = DB::transaction(function () use ($request) {
            $items = collect($request->validated('items'));
            $productIds = $items->pluck('product_id')->all();

            $products = Product::whereIn('id', $productIds)
                ->where('is_active', true)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($products->count() !== count(array_unique($productIds))) {
                throw ValidationException::withMessages([
                    'items' => ['One or more products are invalid or inactive.'],
                ]);
            }

            $totalPayment = 0;
            $details = [];

            foreach ($items as $index => $item) {
                $product = $products->get($item['product_id']);
                $quantity = (int) $item['quantity'];

                if ($product->stock < $quantity) {
                    throw ValidationException::withMessages([
                        "items.{$index}.quantity" => ["Stock for {$product->product_name} is not enough."],
                    ]);
                }

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

            $customerMoney = (float) $request->validated('customer_money');

            if ($customerMoney < $totalPayment) {
                throw ValidationException::withMessages([
                    'customer_money' => ['Customer money is not enough.'],
                ]);
            }

            $transaction = Transaction::create([
                'user_id' => $request->user()->id,
                'transaction_date' => now(),
                'total_payment' => $totalPayment,
                'customer_money' => $customerMoney,
                'change_money' => $customerMoney - $totalPayment,
            ]);

            foreach ($details as $detail) {
                $transaction->transactionDetails()->create($detail);
                $products->get($detail['product_id'])->decrement('stock', $detail['quantity']);
            }

            return $transaction;
        });

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
