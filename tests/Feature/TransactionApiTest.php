<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TransactionApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_kasir_can_create_sales_transaction_and_reduce_stock(): void
    {
        Sanctum::actingAs(User::factory()->create([
            'role' => 'kasir',
        ]));

        $product = $this->createProduct([
            'stock' => 10,
            'selling_price' => 5000,
        ]);

        $response = $this->postJson('/api/transactions', [
            'customer_money' => 20000,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 3,
                ],
            ],
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.total_payment', '15000.00')
            ->assertJsonPath('data.customer_money', '20000.00')
            ->assertJsonPath('data.change_money', '5000.00')
            ->assertJsonPath('data.items.0.product.product_name', $product->product_name)
            ->assertJsonPath('data.items.0.quantity', '3.00');

        $this->assertDatabaseHas('transaction_details', [
            'product_id' => $product->id,
            'quantity' => 3,
            'selling_price' => 5000,
            'subtotal' => 15000,
        ]);

        $this->assertSame(7, $product->fresh()->stock);
    }

    public function test_transaction_rejects_insufficient_customer_money(): void
    {
        Sanctum::actingAs(User::factory()->create([
            'role' => 'kasir',
        ]));

        $product = $this->createProduct([
            'stock' => 10,
            'selling_price' => 5000,
        ]);

        $response = $this->postJson('/api/transactions', [
            'customer_money' => 10000,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 3,
                ],
            ],
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors('customer_money');

        $this->assertDatabaseCount('transactions', 0);
        $this->assertSame(10, $product->fresh()->stock);
    }

    public function test_transaction_rejects_insufficient_stock_without_changing_stock(): void
    {
        Sanctum::actingAs(User::factory()->create([
            'role' => 'kasir',
        ]));

        $product = $this->createProduct([
            'stock' => 2,
            'selling_price' => 5000,
        ]);

        $response = $this->postJson('/api/transactions', [
            'customer_money' => 20000,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 3,
                ],
            ],
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors('items.0.quantity');

        $this->assertDatabaseCount('transactions', 0);
        $this->assertSame(2, $product->fresh()->stock);
    }

    public function test_admin_gudang_cannot_create_sales_transaction(): void
    {
        Sanctum::actingAs(User::factory()->create([
            'role' => 'admin_gudang',
        ]));

        $product = $this->createProduct();

        $this->postJson('/api/transactions', [
            'customer_money' => 10000,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 1,
                ],
            ],
        ])->assertForbidden();
    }

    public function test_owner_can_list_and_view_transactions(): void
    {
        Sanctum::actingAs(User::factory()->owner()->create());

        $product = $this->createProduct([
            'stock' => 5,
            'selling_price' => 4000,
        ]);

        $createResponse = $this->postJson('/api/transactions', [
            'customer_money' => 10000,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 2,
                ],
            ],
        ]);

        $transactionId = $createResponse->json('data.id');

        $this->getJson('/api/transactions')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.id', $transactionId);

        $this->getJson("/api/transactions/{$transactionId}")
            ->assertOk()
            ->assertJsonPath('data.id', $transactionId)
            ->assertJsonPath('data.items.0.subtotal', '8000.00');
    }

    private function createProduct(array $overrides = []): Product
    {
        $category = Category::create(['category_name' => 'Minuman']);
        $supplier = Supplier::create(['supplier_name' => 'Supplier Utama']);
        $unit = Unit::create(['unit_name' => 'pcs']);

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
