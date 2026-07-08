<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Notification;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LowStockAlertApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_low_stock_products_create_notification_for_owner(): void
    {
        Sanctum::actingAs(User::factory()->owner()->create());

        $product = $this->createProduct(['stock' => 3]);

        $response = $this->postJson('/api/products/'.$product->id.'/check-low-stock');

        $response->assertOk()->assertJsonPath('success', true);

        $this->assertDatabaseHas('notifications', [
            'title' => 'Low stock alert',
            'type' => 'inventory',
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
