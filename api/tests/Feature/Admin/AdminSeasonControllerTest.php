<?php

namespace Tests\Feature\Admin;

use App\Models\Map;
use App\Models\Season;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSeasonControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_crud_seasons_and_only_one_active(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $first = $this->actingAs($admin)
            ->postJson('/api/admin/seasons', [
                'name' => 'Summer Series',
                'is_active' => true,
            ])
            ->assertCreated()
            ->assertJsonPath('data.slug', 'summer-series')
            ->json('data');

        $second = $this->actingAs($admin)
            ->postJson('/api/admin/seasons', [
                'name' => 'Winter Series',
                'is_active' => true,
            ])
            ->assertCreated()
            ->assertJsonPath('data.slug', 'winter-series')
            ->json('data');

        $this->assertDatabaseHas('seasons', ['id' => $second['id'], 'is_active' => true]);
        $this->assertDatabaseHas('seasons', ['id' => $first['id'], 'is_active' => false]);

        $this->actingAs($admin)
            ->patchJson('/api/admin/seasons/'.$second['id'], ['name' => 'Winter Split'])
            ->assertOk()
            ->assertJsonPath('data.slug', 'winter-split');

        $this->actingAs($admin)
            ->getJson('/api/admin/seasons')
            ->assertOk()
            ->assertJsonCount(2, 'data');

        $this->actingAs($admin)
            ->deleteJson('/api/admin/seasons/'.$first['id'])
            ->assertNoContent();
    }

    public function test_season_show_returns_ordered_maps(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $season = Season::query()->create(['name' => 'Ordered', 'created_by_user_id' => $admin->id]);
        $mapA = Map::query()->create(['uid' => 'A', 'name' => 'A']);
        $mapB = Map::query()->create(['uid' => 'B', 'name' => 'B']);

        $season->maps()->attach($mapA->id, ['order_index' => 5, 'is_active' => true]);
        $season->maps()->attach($mapB->id, ['order_index' => 1, 'is_active' => true]);

        $response = $this->actingAs($admin)
            ->getJson('/api/admin/seasons/'.$season->id)
            ->assertOk();

        $this->assertSame('B', $response->json('data.maps.0.uid'));
        $this->assertSame('A', $response->json('data.maps.1.uid'));
    }
}
