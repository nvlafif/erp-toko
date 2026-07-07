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

class HoldTransactionApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_kasir_can_hold_transaction_without_reducing_stock(): void
    {
        Sanctum::actingAs(User::factory()->create([
            'role' => 'kasir',
        ]));

        $product = $this->createProduct([
            'stock' => 10,
            'selling_price' => 5000,
        ]);

        $response = $this->postJson('/api/hold-transactions', [
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
            ->assertJsonPath('data.total_payment', '10000.00')
            ->assertJsonPath('data.items.0.product.product_name', $product->product_name)
            ->assertJsonPath('data.items.0.quantity', '2.00');

        $this->assertDatabaseHas('hold_transaction_details', [
            'product_id' => $product->id,
            'quantity' => 2,
            'subtotal' => 10000,
        ]);

        $this->assertSame(10, $product->fresh()->stock);
    }

    public function test_hold_transaction_can_be_checked_out_and_removed(): void
    {
        Sanctum::actingAs(User::factory()->create([
            'role' => 'kasir',
        ]));

        $product = $this->createProduct([
            'stock' => 10,
            'selling_price' => 5000,
        ]);

        $holdId = $this->postJson('/api/hold-transactions', [
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 3,
                ],
            ],
        ])->json('data.id');

        $response = $this->postJson("/api/hold-transactions/{$holdId}/checkout", [
            'customer_money' => 20000,
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.total_payment', '15000.00')
            ->assertJsonPath('data.change_money', '5000.00');

        $this->assertDatabaseCount('hold_transactions', 0);
        $this->assertDatabaseCount('transactions', 1);
        $this->assertSame(7, $product->fresh()->stock);
    }

    public function test_failed_hold_checkout_keeps_hold_and_stock_unchanged(): void
    {
        Sanctum::actingAs(User::factory()->create([
            'role' => 'kasir',
        ]));

        $product = $this->createProduct([
            'stock' => 2,
            'selling_price' => 5000,
        ]);

        $holdId = $this->postJson('/api/hold-transactions', [
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 2,
                ],
            ],
        ])->json('data.id');

        $product->update(['stock' => 1]);

        $this->postJson("/api/hold-transactions/{$holdId}/checkout", [
            'customer_money' => 10000,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('items.0.quantity');

        $this->assertDatabaseCount('hold_transactions', 1);
        $this->assertDatabaseCount('transactions', 0);
        $this->assertSame(1, $product->fresh()->stock);
    }

    public function test_kasir_can_list_view_and_delete_hold_transaction(): void
    {
        Sanctum::actingAs(User::factory()->create([
            'role' => 'kasir',
        ]));

        $product = $this->createProduct();
        $holdId = $this->postJson('/api/hold-transactions', [
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 1,
                ],
            ],
        ])->json('data.id');

        $this->getJson('/api/hold-transactions')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.id', $holdId);

        $this->getJson("/api/hold-transactions/{$holdId}")
            ->assertOk()
            ->assertJsonPath('data.id', $holdId);

        $this->deleteJson("/api/hold-transactions/{$holdId}")
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseCount('hold_transactions', 0);
    }

    public function test_admin_gudang_cannot_access_hold_transactions(): void
    {
        Sanctum::actingAs(User::factory()->create([
            'role' => 'admin_gudang',
        ]));

        $this->getJson('/api/hold-transactions')->assertForbidden();
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
