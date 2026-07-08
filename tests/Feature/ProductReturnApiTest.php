<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Transaction;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProductReturnApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_kasir_can_return_transaction_item_and_restore_stock(): void
    {
        Sanctum::actingAs(User::factory()->create([
            'role' => 'kasir',
        ]));

        $product = $this->createProduct([
            'stock' => 7,
            'selling_price' => 5000,
        ]);
        $transaction = $this->createTransactionForProduct($product, quantity: 3, sellingPrice: 5000);

        $response = $this->postJson('/api/returns', [
            'transaction_id' => $transaction->id,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 2,
                ],
            ],
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.transaction.id', $transaction->id)
            ->assertJsonPath('data.return_total', '10000.00')
            ->assertJsonPath('data.items.0.quantity', '2.00')
            ->assertJsonPath('data.items.0.subtotal', '10000.00');

        $this->assertDatabaseHas('return_details', [
            'product_id' => $product->id,
            'quantity' => 2,
            'selling_price' => 5000,
            'subtotal' => 10000,
        ]);

        $this->assertSame(9, $product->fresh()->stock);
    }

    public function test_return_cannot_exceed_remaining_transaction_quantity(): void
    {
        Sanctum::actingAs(User::factory()->create([
            'role' => 'kasir',
        ]));

        $product = $this->createProduct([
            'stock' => 7,
            'selling_price' => 5000,
        ]);
        $transaction = $this->createTransactionForProduct($product, quantity: 3, sellingPrice: 5000);

        $this->postJson('/api/returns', [
            'transaction_id' => $transaction->id,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 2,
                ],
            ],
        ])->assertCreated();

        $this->postJson('/api/returns', [
            'transaction_id' => $transaction->id,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 2,
                ],
            ],
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('items.0.quantity');

        $this->assertDatabaseCount('returns', 1);
        $this->assertSame(9, $product->fresh()->stock);
    }

    public function test_return_rejects_product_that_is_not_in_transaction(): void
    {
        Sanctum::actingAs(User::factory()->create([
            'role' => 'kasir',
        ]));

        $soldProduct = $this->createProduct();
        $otherProduct = $this->createProduct();
        $transaction = $this->createTransactionForProduct($soldProduct, quantity: 1, sellingPrice: 5000);

        $this->postJson('/api/returns', [
            'transaction_id' => $transaction->id,
            'items' => [
                [
                    'product_id' => $otherProduct->id,
                    'quantity' => 1,
                ],
            ],
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('items');
    }

    public function test_owner_can_list_and_view_returns(): void
    {
        Sanctum::actingAs(User::factory()->owner()->create());

        $product = $this->createProduct([
            'stock' => 7,
            'selling_price' => 4000,
        ]);
        $transaction = $this->createTransactionForProduct($product, quantity: 2, sellingPrice: 4000);

        $returnId = $this->postJson('/api/returns', [
            'transaction_id' => $transaction->id,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 1,
                ],
            ],
        ])->json('data.id');

        $this->getJson('/api/returns')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.id', $returnId);

        $this->getJson("/api/returns/{$returnId}")
            ->assertOk()
            ->assertJsonPath('data.id', $returnId)
            ->assertJsonPath('data.items.0.subtotal', '4000.00');
    }

    public function test_admin_gudang_cannot_process_returns(): void
    {
        Sanctum::actingAs(User::factory()->create([
            'role' => 'admin_gudang',
        ]));

        $product = $this->createProduct();
        $transaction = $this->createTransactionForProduct($product, quantity: 1, sellingPrice: 5000);

        $this->postJson('/api/returns', [
            'transaction_id' => $transaction->id,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 1,
                ],
            ],
        ])->assertForbidden();
    }

    private function createTransactionForProduct(Product $product, int $quantity, float $sellingPrice): Transaction
    {
        $transaction = Transaction::create([
            'user_id' => User::factory()->create(['role' => 'kasir'])->id,
            'transaction_date' => now(),
            'total_payment' => $quantity * $sellingPrice,
            'customer_money' => $quantity * $sellingPrice,
            'change_money' => 0,
        ]);

        $transaction->transactionDetails()->create([
            'product_id' => $product->id,
            'quantity' => $quantity,
            'selling_price' => $sellingPrice,
            'subtotal' => $quantity * $sellingPrice,
        ]);

        return $transaction;
    }

    private function createProduct(array $overrides = []): Product
    {
        $category = Category::create(['category_name' => fake()->unique()->word()]);
        $supplier = Supplier::create(['supplier_name' => fake()->unique()->company()]);
        $unit = Unit::create(['unit_name' => fake()->unique()->word()]);

        return Product::create(array_merge([
            'barcode' => fake()->unique()->numerify('899#########'),
            'product_name' => fake()->words(2, true),
            'category_id' => $category->id,
            'supplier_id' => $supplier->id,
            'stock' => 10,
            'unit_id' => $unit->id,
            'purchase_price' => 3000,
            'selling_price' => 5000,
        ], $overrides));
    }
}
