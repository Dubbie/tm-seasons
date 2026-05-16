<?php

namespace Tests\Feature\Admin;

use App\Domains\Identity\Models\User;
use App\Domains\Seasons\Models\Season;
use App\Domains\Trackmania\Models\Map;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSeasonMapControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_attach_update_and_detach_season_maps(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $season = Season::query()->create(['name' => 'Attach Test', 'created_by_user_id' => $admin->id]);
        $map = Map::query()->create(['uid' => 'map-1', 'name' => 'Map 1']);

        $this->actingAs($admin)
            ->postJson('/api/admin/seasons/'.$season->id.'/maps', [
                'map_id' => $map->id,
                'order_index' => 2,
            ])
            ->assertOk()
            ->assertJsonPath('data.maps.0.season_pivot.order_index', 2);

        $this->actingAs($admin)
            ->postJson('/api/admin/seasons/'.$season->id.'/maps', [
                'map_id' => $map->id,
                'order_index' => 3,
            ])
            ->assertUnprocessable();

        $this->actingAs($admin)
            ->patchJson('/api/admin/seasons/'.$season->id.'/maps/'.$map->id, [
                'order_index' => 7,
                'is_active' => false,
            ])
            ->assertOk()
            ->assertJsonPath('data.maps.0.season_pivot.order_index', 7)
            ->assertJsonPath('data.maps.0.season_pivot.is_active', false);

        $this->actingAs($admin)
            ->deleteJson('/api/admin/seasons/'.$season->id.'/maps/'.$map->id)
            ->assertNoContent();

        $this->assertDatabaseMissing('season_maps', [
            'season_id' => $season->id,
            'map_id' => $map->id,
        ]);
    }
}
