<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OperatingCostApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_manage_operating_costs(): void
    {
        Sanctum::actingAs(User::factory()->owner()->create());

        $response = $this->postJson('/api/operating-costs', [
            'expense_name' => 'Electricity',
            'amount' => 250000,
            'expense_date' => '2026-07-08',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('operating_costs', [
            'expense_name' => 'Electricity',
            'amount' => 250000.00,
        ]);
    }

    public function test_non_owner_cannot_manage_operating_costs(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'kasir']));

        $this->getJson('/api/operating-costs')
            ->assertForbidden();
    }
}
