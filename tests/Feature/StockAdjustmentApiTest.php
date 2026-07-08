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

class StockAdjustmentApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_adjust_product_stock_and_record_movement(): void
    {
        Sanctum::actingAs(User::factory()->owner()->create());

        $product = $this->createProduct(['stock' => 10]);

        $response = $this->postJson('/api/products/'.$product->id.'/adjust-stock', [
            'quantity' => -2,
            'reason' => 'Damaged item',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.new_stock', 8);

        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'movement_type' => 'adjustment',
            'quantity' => -2,
            'description' => 'Damaged item',
        ]);
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
