<?php

namespace Tests\Feature\Admin;

use App\Models\Map;
use App\Models\Season;
use App\Models\SeasonMapPlayerRecord;
use App\Models\TrackmaniaClub;
use App\Models\TrackmaniaPlayer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSeasonPollControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Season $season;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['is_admin' => true]);
        $this->season = Season::query()->create(['name' => 'Test Season', 'is_active' => true]);
    }

    public function test_poll_endpoint_triggers_polling(): void
    {
        $map = Map::query()->create(['uid' => 'map-1', 'name' => 'Map 1']);
        $this->season->maps()->attach($map->id, ['order_index' => 1, 'is_active' => true]);

        TrackmaniaClub::query()->create(['club_id' => '123', 'name' => 'Club', 'is_primary' => true]);

        $this->actingAs($this->admin)
            ->postJson("/api/admin/seasons/{$this->season->id}/poll")
            ->assertOk()
            ->assertJsonStructure([
                'message',
                'total_maps',
                'maps_processed',
                'snapshots_created',
                'records_updated',
            ]);
    }

    public function test_poll_endpoint_requires_admin(): void
    {
        $nonAdmin = User::factory()->create(['is_admin' => false]);

        $this->actingAs($nonAdmin)
            ->postJson("/api/admin/seasons/{$this->season->id}/poll")
            ->assertForbidden();
    }

    public function test_records_endpoint_returns_paginated_records(): void
    {
        $map = Map::query()->create(['uid' => 'map-1', 'name' => 'Map 1']);
        $player = TrackmaniaPlayer::query()->create(['account_id' => 'player-1', 'display_name' => 'Player 1']);

        SeasonMapPlayerRecord::query()->create([
            'season_id' => $this->season->id,
            'map_id' => $map->id,
            'trackmania_player_id' => $player->id,
            'global_position' => 1,
            'time_ms' => 45000,
        ]);

        $this->actingAs($this->admin)
            ->getJson("/api/admin/seasons/{$this->season->id}/records")
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_records_endpoint_filters_by_map(): void
    {
        $mapA = Map::query()->create(['uid' => 'map-a', 'name' => 'Map A']);
        $mapB = Map::query()->create(['uid' => 'map-b', 'name' => 'Map B']);
        $player = TrackmaniaPlayer::query()->create(['account_id' => 'player-1', 'display_name' => 'Player 1']);

        SeasonMapPlayerRecord::query()->create([
            'season_id' => $this->season->id, 'map_id' => $mapA->id,
            'trackmania_player_id' => $player->id, 'global_position' => 1, 'time_ms' => 45000,
        ]);
        SeasonMapPlayerRecord::query()->create([
            'season_id' => $this->season->id, 'map_id' => $mapB->id,
            'trackmania_player_id' => $player->id, 'global_position' => 2, 'time_ms' => 46000,
        ]);

        $this->actingAs($this->admin)
            ->getJson("/api/admin/seasons/{$this->season->id}/records?map_id={$mapA->id}")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.map_id', $mapA->id);
    }

    public function test_records_endpoint_requires_admin(): void
    {
        $nonAdmin = User::factory()->create(['is_admin' => false]);

        $this->actingAs($nonAdmin)
            ->getJson("/api/admin/seasons/{$this->season->id}/records")
            ->assertForbidden();
    }
}
