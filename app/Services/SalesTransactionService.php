<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SalesTransactionService
{
    public function create(User $user, array|Collection $items, float $customerMoney): Transaction
    {
        return DB::transaction(function () use ($user, $items, $customerMoney) {
            $items = collect($items);
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

            if ($customerMoney < $totalPayment) {
                throw ValidationException::withMessages([
                    'customer_money' => ['Customer money is not enough.'],
                ]);
            }

            $transaction = Transaction::create([
                'user_id' => $user->id,
                'transaction_date' => now(),
                'total_payment' => $totalPayment,
                'customer_money' => $customerMoney,
                'change_money' => $customerMoney - $totalPayment,
            ]);

            foreach ($details as $detail) {
                $transaction->transactionDetails()->create($detail);
                $products->get($detail['product_id'])->decrement('stock', $detail['quantity']);
            }

            ActivityLog::create([
                'user_id' => $user->id,
                'activity' => "Membuat transaksi penjualan #{$transaction->id}",
                'activity_date' => now(),
            ]);

            return $transaction;
        });
    }
}
