<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductReturnRequest;
use App\Http\Resources\ProductReturnResource;
use App\Models\Product;
use App\Models\ProductReturn;
use App\Models\ProductReturnDetail;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProductReturnController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);

        $perPage = min(max($request->integer('per_page', 15), 1), 100);

        $returns = ProductReturn::with(['user', 'transaction', 'returnDetails.product'])
            ->when($request->filled('date_from'), function ($query) use ($request) {
                $query->whereDate('return_date', '>=', $request->date('date_from'));
            })
            ->when($request->filled('date_to'), function ($query) use ($request) {
                $query->whereDate('return_date', '<=', $request->date('date_to'));
            })
            ->latest('return_date')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Returns retrieved successfully.',
            'data' => ProductReturnResource::collection($returns),
            'meta' => [
                'current_page' => $returns->currentPage(),
                'last_page' => $returns->lastPage(),
                'per_page' => $returns->perPage(),
                'total' => $returns->total(),
            ],
        ]);
    }

    public function store(StoreProductReturnRequest $request): JsonResponse
    {
        $productReturn = DB::transaction(function () use ($request) {
            $transaction = Transaction::whereKey($request->validated('transaction_id'))
                ->lockForUpdate()
                ->firstOrFail();

            $items = collect($request->validated('items'));
            $productIds = $items->pluck('product_id')->all();

            $transactionDetails = TransactionDetail::where('transaction_id', $transaction->id)
                ->whereIn('product_id', $productIds)
                ->lockForUpdate()
                ->get()
                ->keyBy('product_id');

            if ($transactionDetails->count() !== count(array_unique($productIds))) {
                throw ValidationException::withMessages([
                    'items' => ['One or more products do not belong to this transaction.'],
                ]);
            }

            $returnedQuantities = ProductReturnDetail::query()
                ->whereHas('productReturn', function ($query) use ($transaction) {
                    $query->where('transaction_id', $transaction->id);
                })
                ->whereIn('product_id', $productIds)
                ->select('product_id', DB::raw('SUM(quantity) as total_quantity'))
                ->groupBy('product_id')
                ->pluck('total_quantity', 'product_id');

            $products = Product::whereIn('id', $productIds)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $returnTotal = 0;
            $details = [];

            foreach ($items as $index => $item) {
                $transactionDetail = $transactionDetails->get($item['product_id']);
                $quantity = (int) $item['quantity'];
                $alreadyReturned = (float) ($returnedQuantities[$item['product_id']] ?? 0);
                $remainingQuantity = (float) $transactionDetail->quantity - $alreadyReturned;

                if ($quantity > $remainingQuantity) {
                    throw ValidationException::withMessages([
                        "items.{$index}.quantity" => ['Return quantity exceeds the remaining transaction quantity.'],
                    ]);
                }

                $sellingPrice = (float) $transactionDetail->selling_price;
                $subtotal = $sellingPrice * $quantity;
                $returnTotal += $subtotal;

                $details[] = [
                    'product_id' => $transactionDetail->product_id,
                    'quantity' => $quantity,
                    'selling_price' => $sellingPrice,
                    'subtotal' => $subtotal,
                ];
            }

            $productReturn = ProductReturn::create([
                'transaction_id' => $transaction->id,
                'user_id' => $request->user()->id,
                'return_date' => now(),
                'return_total' => $returnTotal,
            ]);

            foreach ($details as $detail) {
                $productReturn->returnDetails()->create($detail);
                $products->get($detail['product_id'])->increment('stock', $detail['quantity']);
            }

            return $productReturn;
        });

        $productReturn->load(['user', 'transaction', 'returnDetails.product']);

        return response()->json([
            'success' => true,
            'message' => 'Return created successfully.',
            'data' => new ProductReturnResource($productReturn),
        ], 201);
    }

    public function show(ProductReturn $productReturn): JsonResponse
    {
        $productReturn->load(['user', 'transaction', 'returnDetails.product']);

        return response()->json([
            'success' => true,
            'message' => 'Return retrieved successfully.',
            'data' => new ProductReturnResource($productReturn),
        ]);
    }
}
