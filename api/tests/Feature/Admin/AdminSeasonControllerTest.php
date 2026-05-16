<?php

namespace Tests\Feature\Admin;

use App\Domains\Identity\Models\User;
use App\Domains\Seasons\Models\Season;
use App\Domains\Seasons\Models\SeasonMapPlayerRecord;
use App\Domains\Trackmania\Models\Map;
use App\Domains\Trackmania\Models\TrackmaniaPlayer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSeasonControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_crud_seasons(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $first = $this->actingAs($admin)
            ->postJson('/api/admin/seasons', [
                'name' => 'Summer Series',
                'status' => 'active',
                'starts_at' => now()->subDay()->toIso8601String(),
                'ends_at' => now()->addDay()->toIso8601String(),
            ])
            ->assertCreated()
            ->assertJsonPath('data.slug', 'summer-series')
            ->json('data');

        $second = $this->actingAs($admin)
            ->postJson('/api/admin/seasons', [
                'name' => 'Winter Series',
                'status' => 'draft',
            ])
            ->assertCreated()
            ->assertJsonPath('data.slug', 'winter-series')
            ->json('data');

        $this->assertDatabaseHas('seasons', ['id' => $first['id'], 'status' => 'active']);
        $this->assertDatabaseHas('seasons', ['id' => $second['id'], 'status' => 'draft']);

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

    public function test_admin_can_finalize_ended_season(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $season = Season::query()->create([
            'name' => 'Finalize Me',
            'status' => 'ended',
            'starts_at' => now()->subDays(2),
            'ends_at' => now()->subDay(),
            'created_by_user_id' => $admin->id,
        ]);
        $player = TrackmaniaPlayer::query()->create(['account_id' => 'p-1', 'display_name' => 'Player 1']);
        $map = Map::query()->create(['uid' => 'map-final', 'name' => 'Map Final']);

        SeasonMapPlayerRecord::query()->create([
            'season_id' => $season->id,
            'map_id' => $map->id,
            'trackmania_player_id' => $player->id,
            'time_ms' => 42000,
            'current_position' => 1,
            'first_seen_at' => now(),
            'last_seen_at' => now(),
        ]);

        $this->actingAs($admin)
            ->postJson('/api/admin/seasons/'.$season->id.'/finalize')
            ->assertOk()
            ->assertJsonPath('data.players_processed', 1);

        $this->assertDatabaseHas('seasons', ['id' => $season->id, 'status' => 'finalized']);
    }
}
