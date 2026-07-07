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

class MasterDataApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_read_master_data(): void
    {
        Sanctum::actingAs(User::factory()->create([
            'role' => 'kasir',
        ]));

        Category::create(['category_name' => 'Minuman']);
        Supplier::create(['supplier_name' => 'Supplier Utama']);
        Unit::create(['unit_name' => 'pcs']);

        $this->getJson('/api/categories')
            ->assertOk()
            ->assertJsonPath('data.0.category_name', 'Minuman');

        $this->getJson('/api/suppliers')
            ->assertOk()
            ->assertJsonPath('data.0.supplier_name', 'Supplier Utama');

        $this->getJson('/api/units')
            ->assertOk()
            ->assertJsonPath('data.0.unit_name', 'pcs');
    }

    public function test_owner_can_create_update_and_delete_category(): void
    {
        Sanctum::actingAs(User::factory()->owner()->create());

        $createResponse = $this->postJson('/api/categories', [
            'category_name' => 'Makanan',
        ]);

        $createResponse
            ->assertCreated()
            ->assertJsonPath('data.category_name', 'Makanan');

        $category = Category::first();

        $this->putJson("/api/categories/{$category->id}", [
            'category_name' => 'Makanan Ringan',
        ])
            ->assertOk()
            ->assertJsonPath('data.category_name', 'Makanan Ringan');

        $this->deleteJson("/api/categories/{$category->id}")
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_kasir_cannot_change_master_data(): void
    {
        Sanctum::actingAs(User::factory()->create([
            'role' => 'kasir',
        ]));

        $this->postJson('/api/categories', [
            'category_name' => 'Makanan',
        ])->assertForbidden();
    }

    public function test_admin_gudang_can_create_product_and_filter_products(): void
    {
        Sanctum::actingAs(User::factory()->create([
            'role' => 'admin_gudang',
        ]));

        $category = Category::create(['category_name' => 'Minuman']);
        $supplier = Supplier::create(['supplier_name' => 'Supplier Utama']);
        $unit = Unit::create(['unit_name' => 'botol']);

        $this->postJson('/api/products', [
            'barcode' => '899000000001',
            'product_name' => 'Teh Botol',
            'category_id' => $category->id,
            'supplier_id' => $supplier->id,
            'stock' => 8,
            'expired_date' => '2027-01-01',
            'unit_id' => $unit->id,
            'purchase_price' => 3000,
            'selling_price' => 5000,
        ])
            ->assertCreated()
            ->assertJsonPath('data.product_name', 'Teh Botol')
            ->assertJsonPath('data.category.category_name', 'Minuman');

        Product::create([
            'barcode' => '899000000002',
            'product_name' => 'Kopi Sachet',
            'category_id' => $category->id,
            'supplier_id' => $supplier->id,
            'stock' => 50,
            'unit_id' => $unit->id,
            'purchase_price' => 1000,
            'selling_price' => 1500,
        ]);

        $this->getJson('/api/products?q=teh&stock_status=low&per_page=5')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.product_name', 'Teh Botol');
    }

    public function test_master_data_cannot_be_deleted_when_used_by_product(): void
    {
        Sanctum::actingAs(User::factory()->owner()->create());

        $category = Category::create(['category_name' => 'Minuman']);
        $supplier = Supplier::create(['supplier_name' => 'Supplier Utama']);
        $unit = Unit::create(['unit_name' => 'botol']);

        Product::create([
            'barcode' => '899000000003',
            'product_name' => 'Air Mineral',
            'category_id' => $category->id,
            'supplier_id' => $supplier->id,
            'stock' => 20,
            'unit_id' => $unit->id,
            'purchase_price' => 2000,
            'selling_price' => 3500,
        ]);

        $this->deleteJson("/api/categories/{$category->id}")
            ->assertStatus(422)
            ->assertJsonPath('success', false);
    }
}
