<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class NotificationApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_list_and_mark_notifications_as_read(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $notification = Notification::create([
            'user_id' => $user->id,
            'title' => 'Stock low',
            'message' => 'Product stock is low.',
            'type' => 'inventory',
        ]);

        $this->getJson('/api/notifications')
            ->assertOk()
            ->assertJsonPath('meta.total', 1);

        $this->patchJson('/api/notifications/'.$notification->id.'/read')
            ->assertOk()
            ->assertJsonPath('data.is_read', true);

        $this->assertDatabaseHas('notifications', [
            'id' => $notification->id,
            'is_read' => true,
        ]);
    }
}
