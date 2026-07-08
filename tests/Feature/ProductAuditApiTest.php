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

class ProductAuditApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_view_product_audit_history(): void
    {
        Sanctum::actingAs(User::factory()->owner()->create());

        $product = $this->createProduct();
        $product->update(['product_name' => 'Updated Name']);

        $response = $this->getJson('/api/products/'.$product->id.'/audit');

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('meta.total', 1);
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
