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

class StockMovementApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_stock_movements_are_recorded_for_sales_and_returns(): void
    {
        Sanctum::actingAs(User::factory()->owner()->create());

        $product = $this->createProduct([
            'stock' => 10,
            'selling_price' => 5000,
        ]);

        $saleResponse = $this->postJson('/api/transactions', [
            'customer_money' => 20000,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 3,
                ],
            ],
        ]);

        $saleResponse->assertCreated();

        $transactionId = $saleResponse->json('data.id');

        $returnResponse = $this->postJson('/api/returns', [
            'transaction_id' => $transactionId,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 1,
                ],
            ],
        ]);

        $returnResponse->assertCreated();

        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'movement_type' => 'sale',
            'quantity' => -3,
        ]);

        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'movement_type' => 'return',
            'quantity' => 1,
        ]);

        $this->getJson('/api/stock-movements')
            ->assertOk()
            ->assertJsonPath('meta.total', 2);
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
