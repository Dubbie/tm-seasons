<?php

namespace Tests\Feature\Trackmania;

use App\DTOs\Trackmania\TrackmaniaLeaderboard;
use App\DTOs\Trackmania\TrackmaniaLeaderboardEntry;
use App\Models\ClubMember;
use App\Models\LeaderboardPoll;
use App\Models\LeaderboardSnapshot;
use App\Models\Map;
use App\Models\Season;
use App\Models\SeasonMapPlayerRecord;
use App\Models\TrackmaniaClub;
use App\Models\TrackmaniaPlayer;
use App\Services\Trackmania\SeasonLeaderboardPollingService;
use App\Services\Trackmania\TrackmaniaClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class SeasonLeaderboardPollingServiceTest extends TestCase
{
    use RefreshDatabase;

    private TrackmaniaClient $client;

    private SeasonLeaderboardPollingService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = Mockery::mock(TrackmaniaClient::class);
        $this->service = new SeasonLeaderboardPollingService($this->client);
    }

    private function createClubWithPlayer(): TrackmaniaPlayer
    {
        $club = TrackmaniaClub::query()->create(['club_id' => '123', 'name' => 'Club', 'is_primary' => true]);
        $player = TrackmaniaPlayer::query()->create(['account_id' => 'player-1', 'display_name' => 'Player 1']);
        ClubMember::query()->create([
            'trackmania_club_id' => $club->id,
            'trackmania_player_id' => $player->id,
            'is_active' => true,
        ]);

        return $player;
    }

    public function test_poll_creates_leaderboard_polls_row(): void
    {
        $season = Season::query()->create(['name' => 'Test Season', 'is_active' => true]);

        $this->service->pollSeason($season);

        $this->assertDatabaseHas('leaderboard_polls', [
            'season_id' => $season->id,
            'status' => 'completed',
        ]);
    }

    public function test_poll_creates_snapshots(): void
    {
        $season = Season::query()->create(['name' => 'Test Season', 'is_active' => true]);
        $map = Map::query()->create(['uid' => 'map-1', 'name' => 'Map 1']);
        $season->maps()->attach($map->id, ['order_index' => 1, 'is_active' => true]);

        $player = $this->createClubWithPlayer();

        $this->client->shouldReceive('getMapLeaderboard')
            ->once()
            ->with('map-1', 100, 0)
            ->andReturn(new TrackmaniaLeaderboard(
                groupUid: 'Personal_Best',
                mapUid: 'map-1',
                entries: [
                    new TrackmaniaLeaderboardEntry(
                        accountId: 'player-1',
                        position: 1,
                        score: 45000,
                        timestamp: 1000000,
                        zoneId: 'world',
                        zoneName: 'World',
                    ),
                ],
            ));

        $this->service->pollSeason($season);

        $this->assertDatabaseHas('leaderboard_snapshots', [
            'map_id' => $map->id,
            'trackmania_player_id' => $player->id,
            'position' => 1,
            'time_ms' => 45000,
            'zone_name' => 'World',
        ]);
    }

    public function test_poll_updates_records(): void
    {
        $season = Season::query()->create(['name' => 'Test Season', 'is_active' => true]);
        $map = Map::query()->create(['uid' => 'map-1', 'name' => 'Map 1']);
        $season->maps()->attach($map->id, ['order_index' => 1, 'is_active' => true]);

        $player = $this->createClubWithPlayer();

        $this->client->shouldReceive('getMapLeaderboard')
            ->once()
            ->with('map-1', 100, 0)
            ->andReturn(new TrackmaniaLeaderboard(
                groupUid: 'Personal_Best',
                mapUid: 'map-1',
                entries: [
                    new TrackmaniaLeaderboardEntry('player-1', 1, 45000, 1000000, 'world', 'World'),
                ],
            ));

        $this->service->pollSeason($season);

        $this->assertDatabaseHas('season_map_player_records', [
            'season_id' => $season->id,
            'map_id' => $map->id,
            'trackmania_player_id' => $player->id,
            'global_position' => 1,
            'time_ms' => 45000,
            'total_improvements' => 0,
        ]);
    }

    public function test_lower_time_counts_as_improvement(): void
    {
        $season = Season::query()->create(['name' => 'Test Season', 'is_active' => true]);
        $map = Map::query()->create(['uid' => 'map-1', 'name' => 'Map 1']);
        $season->maps()->attach($map->id, ['order_index' => 1, 'is_active' => true]);

        $player = $this->createClubWithPlayer();

        $this->client->shouldReceive('getMapLeaderboard')
            ->twice()
            ->with('map-1', 100, 0)
            ->andReturn(
                new TrackmaniaLeaderboard('Personal_Best', 'map-1', [
                    new TrackmaniaLeaderboardEntry('player-1', 1, 50000, 1000000, 'world', 'World'),
                ]),
                new TrackmaniaLeaderboard('Personal_Best', 'map-1', [
                    new TrackmaniaLeaderboardEntry('player-1', 1, 45000, 1000001, 'world', 'World'),
                ]),
            );

        $this->service->pollSeason($season);
        $this->service->pollSeason($season);

        $record = SeasonMapPlayerRecord::query()
            ->where('season_id', $season->id)
            ->where('map_id', $map->id)
            ->where('trackmania_player_id', $player->id)
            ->firstOrFail();

        $this->assertSame(45000, $record->time_ms);
        $this->assertSame(1, $record->total_improvements);
    }

    public function test_equal_time_does_not_increment_improvements(): void
    {
        $season = Season::query()->create(['name' => 'Test Season', 'is_active' => true]);
        $map = Map::query()->create(['uid' => 'map-1', 'name' => 'Map 1']);
        $season->maps()->attach($map->id, ['order_index' => 1, 'is_active' => true]);

        $player = $this->createClubWithPlayer();

        $this->client->shouldReceive('getMapLeaderboard')
            ->twice()
            ->with('map-1', 100, 0)
            ->andReturn(
                new TrackmaniaLeaderboard('Personal_Best', 'map-1', [
                    new TrackmaniaLeaderboardEntry('player-1', 1, 45000, 1000000, 'world', 'World'),
                ]),
                new TrackmaniaLeaderboard('Personal_Best', 'map-1', [
                    new TrackmaniaLeaderboardEntry('player-1', 1, 45000, 1000001, 'world', 'World'),
                ]),
            );

        $this->service->pollSeason($season);
        $this->service->pollSeason($season);

        $record = SeasonMapPlayerRecord::query()
            ->where('season_id', $season->id)
            ->where('map_id', $map->id)
            ->where('trackmania_player_id', $player->id)
            ->firstOrFail();

        $this->assertSame(45000, $record->time_ms);
        $this->assertSame(0, $record->total_improvements);
    }

    public function test_worse_time_ignored(): void
    {
        $season = Season::query()->create(['name' => 'Test Season', 'is_active' => true]);
        $map = Map::query()->create(['uid' => 'map-1', 'name' => 'Map 1']);
        $season->maps()->attach($map->id, ['order_index' => 1, 'is_active' => true]);

        $player = $this->createClubWithPlayer();

        $this->client->shouldReceive('getMapLeaderboard')
            ->twice()
            ->with('map-1', 100, 0)
            ->andReturn(
                new TrackmaniaLeaderboard('Personal_Best', 'map-1', [
                    new TrackmaniaLeaderboardEntry('player-1', 1, 45000, 1000000, 'world', 'World'),
                ]),
                new TrackmaniaLeaderboard('Personal_Best', 'map-1', [
                    new TrackmaniaLeaderboardEntry('player-1', 2, 46000, 1000001, 'world', 'World'),
                ]),
            );

        $this->service->pollSeason($season);
        $this->service->pollSeason($season);

        $record = SeasonMapPlayerRecord::query()
            ->where('season_id', $season->id)
            ->where('map_id', $map->id)
            ->where('trackmania_player_id', $player->id)
            ->firstOrFail();

        $this->assertSame(45000, $record->time_ms);
        $this->assertSame(0, $record->total_improvements);
        $this->assertSame(2, $record->global_position);
    }

    public function test_snapshots_store_immutable_time_history(): void
    {
        $season = Season::query()->create(['name' => 'Test Season', 'is_active' => true]);
        $map = Map::query()->create(['uid' => 'map-1', 'name' => 'Map 1']);
        $season->maps()->attach($map->id, ['order_index' => 1, 'is_active' => true]);

        $player = $this->createClubWithPlayer();

        $this->client->shouldReceive('getMapLeaderboard')
            ->twice()
            ->with('map-1', 100, 0)
            ->andReturn(
                new TrackmaniaLeaderboard('Personal_Best', 'map-1', [
                    new TrackmaniaLeaderboardEntry('player-1', 1, 50000, 1000000, 'world', 'World'),
                ]),
                new TrackmaniaLeaderboard('Personal_Best', 'map-1', [
                    new TrackmaniaLeaderboardEntry('player-1', 1, 45000, 1000001, 'world', 'World'),
                ]),
            );

        $this->service->pollSeason($season);
        $this->service->pollSeason($season);

        $snapshots = LeaderboardSnapshot::query()
            ->where('trackmania_player_id', $player->id)
            ->orderBy('id')
            ->get();

        $this->assertCount(2, $snapshots);
        $this->assertSame(50000, $snapshots[0]->time_ms);
        $this->assertSame(45000, $snapshots[1]->time_ms);
    }

    public function test_non_club_players_ignored(): void
    {
        $season = Season::query()->create(['name' => 'Test Season', 'is_active' => true]);
        $map = Map::query()->create(['uid' => 'map-1', 'name' => 'Map 1']);
        $season->maps()->attach($map->id, ['order_index' => 1, 'is_active' => true]);

        TrackmaniaClub::query()->create(['club_id' => '123', 'name' => 'Club', 'is_primary' => true]);

        $clubPlayer = TrackmaniaPlayer::query()->create(['account_id' => 'club-player', 'display_name' => 'Club Player']);
        $unknownPlayer = TrackmaniaPlayer::query()->create(['account_id' => 'unknown', 'display_name' => 'Unknown']);

        ClubMember::query()->create([
            'trackmania_club_id' => TrackmaniaClub::primary()->id,
            'trackmania_player_id' => $clubPlayer->id,
            'is_active' => true,
        ]);

        $this->client->shouldReceive('getMapLeaderboard')
            ->once()
            ->with('map-1', 100, 0)
            ->andReturn(new TrackmaniaLeaderboard('Personal_Best', 'map-1', [
                new TrackmaniaLeaderboardEntry('club-player', 1, 45000, 1000000, 'world', 'World'),
                new TrackmaniaLeaderboardEntry('unknown', 2, 46000, 1000001, 'world', 'World'),
            ]));

        $this->service->pollSeason($season);

        $this->assertDatabaseHas('leaderboard_snapshots', [
            'trackmania_player_id' => $clubPlayer->id,
        ]);

        $this->assertDatabaseMissing('leaderboard_snapshots', [
            'trackmania_player_id' => $unknownPlayer->id,
        ]);
    }

    public function test_inactive_players_ignored(): void
    {
        $season = Season::query()->create(['name' => 'Test Season', 'is_active' => true]);
        $map = Map::query()->create(['uid' => 'map-1', 'name' => 'Map 1']);
        $season->maps()->attach($map->id, ['order_index' => 1, 'is_active' => true]);

        $club = TrackmaniaClub::query()->create(['club_id' => '123', 'name' => 'Club', 'is_primary' => true]);
        $active = TrackmaniaPlayer::query()->create(['account_id' => 'active', 'display_name' => 'Active', 'is_active' => true]);
        $inactivePlayer = TrackmaniaPlayer::query()->create(['account_id' => 'inactive', 'display_name' => 'Inactive', 'is_active' => false]);

        ClubMember::query()->create([
            'trackmania_club_id' => $club->id,
            'trackmania_player_id' => $active->id,
            'is_active' => true,
        ]);
        ClubMember::query()->create([
            'trackmania_club_id' => $club->id,
            'trackmania_player_id' => $inactivePlayer->id,
            'is_active' => true,
        ]);

        $this->client->shouldReceive('getMapLeaderboard')
            ->once()
            ->with('map-1', 100, 0)
            ->andReturn(new TrackmaniaLeaderboard('Personal_Best', 'map-1', [
                new TrackmaniaLeaderboardEntry('active', 1, 45000, 1000000, 'world', 'World'),
                new TrackmaniaLeaderboardEntry('inactive', 2, 46000, 1000001, 'world', 'World'),
            ]));

        $this->service->pollSeason($season);

        $this->assertDatabaseHas('leaderboard_snapshots', [
            'trackmania_player_id' => $active->id,
        ]);

        $this->assertDatabaseMissing('leaderboard_snapshots', [
            'trackmania_player_id' => $inactivePlayer->id,
        ]);
    }

    public function test_duplicate_records_prevented(): void
    {
        $season = Season::query()->create(['name' => 'Test Season', 'is_active' => true]);
        $map = Map::query()->create(['uid' => 'map-1', 'name' => 'Map 1']);
        $season->maps()->attach($map->id, ['order_index' => 1, 'is_active' => true]);

        $player = $this->createClubWithPlayer();

        $this->client->shouldReceive('getMapLeaderboard')
            ->twice()
            ->with('map-1', 100, 0)
            ->andReturn(
                new TrackmaniaLeaderboard('Personal_Best', 'map-1', [
                    new TrackmaniaLeaderboardEntry('player-1', 1, 45000, 1000000, 'world', 'World'),
                ]),
                new TrackmaniaLeaderboard('Personal_Best', 'map-1', [
                    new TrackmaniaLeaderboardEntry('player-1', 2, 46000, 1000001, 'world', 'World'),
                ]),
            );

        $this->service->pollSeason($season);
        $this->service->pollSeason($season);

        $this->assertSame(
            1,
            SeasonMapPlayerRecord::query()
                ->where('season_id', $season->id)
                ->where('map_id', $map->id)
                ->where('trackmania_player_id', $player->id)
                ->count(),
        );
    }

    public function test_poll_failure_handled_cleanly(): void
    {
        $season = Season::query()->create(['name' => 'Test Season', 'is_active' => true]);
        $map = Map::query()->create(['uid' => 'map-1', 'name' => 'Map 1']);
        $season->maps()->attach($map->id, ['order_index' => 1, 'is_active' => true]);
        $this->createClubWithPlayer();

        $this->client->shouldReceive('getMapLeaderboard')
            ->once()
            ->andThrow(new \RuntimeException('API failure'));

        $this->service->pollSeason($season);

        $poll = LeaderboardPoll::query()->first();
        $this->assertNotNull($poll);
        $this->assertSame('completed', $poll->status);
        $this->assertNotNull($poll->error_message);
        $this->assertNotNull($poll->finished_at);
        $this->assertSame(0, $poll->maps_polled_count);
        $this->assertSame(0, $poll->players_processed_count);
    }

    public function test_malformed_response_handled(): void
    {
        $season = Season::query()->create(['name' => 'Test Season', 'is_active' => true]);
        $map = Map::query()->create(['uid' => 'map-1', 'name' => 'Map 1']);
        $season->maps()->attach($map->id, ['order_index' => 1, 'is_active' => true]);

        $this->createClubWithPlayer();

        $this->client->shouldReceive('getMapLeaderboard')
            ->once()
            ->with('map-1', 100, 0)
            ->andReturn(new TrackmaniaLeaderboard('Personal_Best', 'map-1', []));

        $result = $this->service->pollSeason($season);

        $this->assertSame(1, $result['maps_processed']);
        $this->assertSame(0, $result['snapshots_created']);
    }

    public function test_empty_leaderboard_handled(): void
    {
        $season = Season::query()->create(['name' => 'Test Season', 'is_active' => true]);
        $map = Map::query()->create(['uid' => 'map-1', 'name' => 'Map 1']);
        $season->maps()->attach($map->id, ['order_index' => 1, 'is_active' => true]);

        $this->createClubWithPlayer();

        $this->client->shouldReceive('getMapLeaderboard')
            ->once()
            ->with('map-1', 100, 0)
            ->andReturn(new TrackmaniaLeaderboard('Personal_Best', 'map-1', []));

        $result = $this->service->pollSeason($season);

        $this->assertSame(1, $result['maps_processed']);
        $this->assertSame(0, $result['snapshots_created']);
    }

    public function test_poll_with_no_active_maps(): void
    {
        $season = Season::query()->create(['name' => 'Test Season', 'is_active' => true]);
        $map = Map::query()->create(['uid' => 'map-1', 'name' => 'Map 1']);
        $season->maps()->attach($map->id, ['order_index' => 1, 'is_active' => false]);

        $result = $this->service->pollSeason($season);

        $this->assertSame(0, $result['maps_processed']);
        $this->assertSame(0, $result['snapshots_created']);
    }

    public function test_poll_with_no_primary_club(): void
    {
        $season = Season::query()->create(['name' => 'Test Season', 'is_active' => true]);
        $map = Map::query()->create(['uid' => 'map-1', 'name' => 'Map 1']);
        $season->maps()->attach($map->id, ['order_index' => 1, 'is_active' => true]);

        $result = $this->service->pollSeason($season);

        $this->assertSame(0, $result['maps_processed']);
        $this->assertSame(0, $result['snapshots_created']);
    }
}
