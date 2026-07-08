<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DashboardReportApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_view_dashboard_report(): void
    {
        Sanctum::actingAs(User::factory()->owner()->create());

        $product = $this->createProduct(['stock' => 10]);

        $transaction = Transaction::create([
            'user_id' => auth()->id(),
            'transaction_date' => now()->subDay(),
            'total_payment' => 15000,
            'customer_money' => 20000,
            'change_money' => 5000,
        ]);

        TransactionDetail::create([
            'transaction_id' => $transaction->id,
            'product_id' => $product->id,
            'quantity' => 3,
            'selling_price' => 5000,
            'subtotal' => 15000,
        ]);

        $response = $this->getJson('/api/reports/dashboard');

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.total_sales', '15000.00')
            ->assertJsonPath('data.top_products.0.product_name', $product->product_name);
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
