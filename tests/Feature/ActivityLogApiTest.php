<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ActivityLogApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_view_activity_logs(): void
    {
        $owner = User::factory()->owner()->create();
        Sanctum::actingAs($owner);

        ActivityLog::create([
            'user_id' => $owner->id,
            'activity' => 'Membuat transaksi penjualan #1',
            'activity_date' => now(),
        ]);

        $this->getJson('/api/activity-logs')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.activity', 'Membuat transaksi penjualan #1');
    }

    public function test_non_owner_cannot_view_activity_logs(): void
    {
        $kasir = User::factory()->create(['role' => 'kasir']);
        Sanctum::actingAs($kasir);

        $this->getJson('/api/activity-logs')
            ->assertForbidden();
    }
}
