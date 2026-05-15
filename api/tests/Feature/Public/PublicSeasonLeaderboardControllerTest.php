<?php

namespace Tests\Feature\Public;

use App\Models\Map;
use App\Models\Season;
use App\Models\SeasonMapPlayerRecord;
use App\Models\TrackmaniaPlayer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicSeasonLeaderboardControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_season_leaderboard_returns_grouped_data(): void
    {
        $season = Season::query()->create(['name' => 'Test Season', 'slug' => 'test-season', 'status' => 'active']);
        $map = Map::query()->create(['uid' => 'map-1', 'name' => 'Map 1']);
        $season->maps()->attach($map->id, ['order_index' => 1, 'is_active' => true]);

        $player = TrackmaniaPlayer::query()->create(['account_id' => 'player-1', 'display_name' => 'Player 1']);
        SeasonMapPlayerRecord::query()->create([
            'season_id' => $season->id,
            'map_id' => $map->id,
            'trackmania_player_id' => $player->id,
            'global_position' => 1,
            'time_ms' => 45000,
        ]);

        $response = $this->getJson("/api/seasons/{$season->slug}/leaderboard")
            ->assertOk();

        $this->assertArrayHasKey('season', $response->json('data'));
        $this->assertArrayHasKey('leaderboard', $response->json('data'));
        $this->assertCount(1, $response->json('data.leaderboard'));
    }

    public function test_map_leaderboard_returns_entries(): void
    {
        $season = Season::query()->create(['name' => 'Test Season', 'slug' => 'test-season', 'status' => 'active']);
        $map = Map::query()->create(['uid' => 'map-1', 'name' => 'Map 1']);

        $player = TrackmaniaPlayer::query()->create(['account_id' => 'player-1', 'display_name' => 'Player 1']);
        SeasonMapPlayerRecord::query()->create([
            'season_id' => $season->id,
            'map_id' => $map->id,
            'trackmania_player_id' => $player->id,
            'global_position' => 1,
            'time_ms' => 45000,
        ]);

        $this->getJson("/api/seasons/{$season->slug}/maps/{$map->id}/leaderboard")
            ->assertOk()
            ->assertJsonCount(1, 'data.entries');
    }

    public function test_season_leaderboard_returns_404_for_unknown_season(): void
    {
        $this->getJson('/api/seasons/unknown-season/leaderboard')
            ->assertNotFound();
    }

    public function test_season_leaderboard_returns_empty_for_no_records(): void
    {
        $season = Season::query()->create(['name' => 'Empty Season', 'slug' => 'empty-season', 'status' => 'active']);

        $response = $this->getJson("/api/seasons/{$season->slug}/leaderboard")
            ->assertOk();

        $this->assertEmpty($response->json('data.leaderboard'));
    }
}
