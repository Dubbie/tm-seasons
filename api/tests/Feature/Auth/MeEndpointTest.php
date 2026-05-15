<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MeEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_me_returns_unauthenticated_when_logged_out(): void
    {
        $response = $this->getJson('/api/me');

        $response->assertUnauthorized();
    }

    public function test_me_returns_user_when_authenticated(): void
    {
        $user = User::factory()->create([
            'discord_id' => '123456789',
            'discord_username' => 'tester',
            'discord_global_name' => 'Tester',
            'discord_avatar' => 'avatarhash',
            'is_admin' => true,
        ]);

        $response = $this->actingAs($user)->getJson('/api/me');

        $response->assertOk()->assertJson([
            'id' => $user->id,
            'discord_id' => '123456789',
            'discord_username' => 'tester',
            'discord_global_name' => 'Tester',
            'discord_avatar' => 'avatarhash',
            'is_admin' => true,
        ]);
    }
}
