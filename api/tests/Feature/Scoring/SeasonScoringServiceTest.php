<?php

namespace Tests\Feature\Scoring;

use App\Domains\Activity\Models\PointEvent;
use App\Domains\Activity\Services\SeasonPointEventWriteService;
use App\Domains\Seasons\Models\PlayerMapMilestone;
use App\Domains\Seasons\Models\Season;
use App\Domains\Seasons\Models\SeasonMapPlayerRecord;
use App\Domains\Seasons\Services\SeasonScoringService;
use App\Domains\Seasons\Services\SeasonStandingsService;
use App\Domains\Trackmania\Models\Map;
use App\Domains\Trackmania\Models\TrackmaniaPlayer;
use App\Domains\Trackmania\Services\ActiveClubPlayerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeasonScoringServiceTest extends TestCase
{
    use RefreshDatabase;

    private SeasonScoringService $service;

    private Season $season;

    private Map $map;

    private TrackmaniaPlayer $player;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new SeasonScoringService(
            new ActiveClubPlayerService,
            new SeasonPointEventWriteService,
        );
        $this->season = Season::query()->create(['name' => 'Test Season', 'is_active' => true]);
        $this->map = Map::query()->create([
            'uid' => 'map-1',
            'name' => 'Map 1',
            'bronze_time' => 50000,
            'silver_time' => 45000,
            'gold_time' => 40000,
            'author_time' => 35000,
        ]);
        $this->player = TrackmaniaPlayer::query()->create(['account_id' => 'player-1', 'display_name' => 'Player 1']);
    }

    public function test_first_finish_points_awarded_once(): void
    {
        $this->service->evaluateNewRecord($this->season, $this->map, $this->player, 45000, 1, true);

        $this->assertDatabaseHas('point_events', [
            'season_id' => $this->season->id,
            'map_id' => $this->map->id,
            'trackmania_player_id' => $this->player->id,
            'type' => 'first_finish',
            'points' => 10,
        ]);
    }

    public function test_first_finish_not_awarded_twice(): void
    {
        $this->service->evaluateNewRecord($this->season, $this->map, $this->player, 45000, 1, true);
        // second call with same player, same map, same season, isNew=false
        $this->service->evaluateNewRecord($this->season, $this->map, $this->player, 44000, 1, false);

        $this->assertSame(
            1,
            PointEvent::query()
                ->where('season_id', $this->season->id)
                ->where('type', 'first_finish')
                ->count(),
        );
    }

    public function test_bronze_medal_awarded_for_bronze_time(): void
    {
        $this->service->evaluateNewRecord($this->season, $this->map, $this->player, 50000, 1, true);

        $this->assertDatabaseHas('point_events', [
            'type' => 'medal_bronze',
            'points' => 5,
        ]);
    }

    public function test_gold_awards_bronze_silver_gold_cumulatively(): void
    {
        $this->service->evaluateNewRecord($this->season, $this->map, $this->player, 40000, 1, true);

        $this->assertDatabaseHas('point_events', ['type' => 'medal_bronze', 'points' => 5]);
        $this->assertDatabaseHas('point_events', ['type' => 'medal_silver', 'points' => 10]);
        $this->assertDatabaseHas('point_events', ['type' => 'medal_gold', 'points' => 20]);
        $this->assertDatabaseMissing('point_events', ['type' => 'medal_author']);
    }

    public function test_author_awards_all_medals(): void
    {
        $this->service->evaluateNewRecord($this->season, $this->map, $this->player, 35000, 1, true);

        $this->assertDatabaseHas('point_events', ['type' => 'medal_bronze', 'points' => 5]);
        $this->assertDatabaseHas('point_events', ['type' => 'medal_silver', 'points' => 10]);
        $this->assertDatabaseHas('point_events', ['type' => 'medal_gold', 'points' => 20]);
        $this->assertDatabaseHas('point_events', ['type' => 'medal_author', 'points' => 35]);
    }

    public function test_medal_rewards_not_awarded_if_does_not_qualify(): void
    {
        $this->service->evaluateNewRecord($this->season, $this->map, $this->player, 55000, 1, true);

        $this->assertDatabaseMissing('point_events', ['type' => 'medal_bronze']);
        $this->assertDatabaseMissing('point_events', ['type' => 'medal_silver']);
        $this->assertDatabaseMissing('point_events', ['type' => 'medal_gold']);
        $this->assertDatabaseMissing('point_events', ['type' => 'medal_author']);
    }

    public function test_medal_rewards_awarded_once_only(): void
    {
        $this->service->evaluateNewRecord($this->season, $this->map, $this->player, 40000, 1, true);
        // improvement does not re-award same medals
        $this->service->evaluateNewRecord($this->season, $this->map, $this->player, 39000, 1, false);

        $this->assertSame(1, PointEvent::query()->where('type', 'medal_gold')->count());
    }

    public function test_improvement_to_higher_medal_awards_new_medals_only(): void
    {
        // first: bronze only
        $this->service->evaluateNewRecord($this->season, $this->map, $this->player, 50000, 1, true);
        // improved to gold
        $this->service->evaluateNewRecord($this->season, $this->map, $this->player, 40000, 1, false);

        $this->assertSame(1, PointEvent::query()->where('type', 'medal_bronze')->count());
        $this->assertSame(1, PointEvent::query()->where('type', 'medal_silver')->count());
        $this->assertSame(1, PointEvent::query()->where('type', 'medal_gold')->count());
    }

    public function test_strong_first_attempt_rewarded_correctly(): void
    {
        // 35s = author time = first_finish + all medals (position rewards awarded separately on finalize)
        $this->service->evaluateNewRecord($this->season, $this->map, $this->player, 35000, 1, true);

        $totalPoints = PointEvent::query()->where('season_id', $this->season->id)->sum('points');
        $expected = 10 + 5 + 10 + 20 + 35;

        $this->assertSame($expected, $totalPoints);
    }

    public function test_weak_first_attempt_no_unfair_advantage(): void
    {
        // 55s = no medals, poor position
        $this->service->evaluateNewRecord($this->season, $this->map, $this->player, 55000, 30, true);

        $totalPoints = PointEvent::query()->where('season_id', $this->season->id)->sum('points');

        $this->assertSame(10, $totalPoints);
    }

    public function test_baseline_time_ms_no_longer_affects_scoring(): void
    {
        SeasonMapPlayerRecord::query()->create([
            'season_id' => $this->season->id,
            'map_id' => $this->map->id,
            'trackmania_player_id' => $this->player->id,
            'time_ms' => 45000,
            'baseline_time_ms' => 55000,
            'first_seen_at' => now(),
            'last_seen_at' => now(),
        ]);

        $this->service->evaluateNewRecord($this->season, $this->map, $this->player, 45000, 1, true);

        // scoring should be based on time_ms=45000, not baseline delta
        $this->assertDatabaseHas('point_events', ['type' => 'medal_bronze']);
        $this->assertDatabaseMissing('point_events', ['type' => 'improvement_100ms']);
        $this->assertDatabaseMissing('point_events', ['type' => 'improvement_250ms']);
    }

    public function test_no_improvement_events_created(): void
    {
        $this->service->evaluateNewRecord($this->season, $this->map, $this->player, 45000, 1, true);

        $this->assertSame(
            0,
            PointEvent::query()->where('type', 'like', 'improvement_%')->count(),
        );
    }

    public function test_recalculation_deterministic_with_medals(): void
    {
        $player2 = TrackmaniaPlayer::query()->create(['account_id' => 'player-2', 'display_name' => 'Player 2']);

        SeasonMapPlayerRecord::query()->create([
            'season_id' => $this->season->id,
            'map_id' => $this->map->id,
            'trackmania_player_id' => $this->player->id,
            'global_position' => 3,
            'current_position' => null,
            'time_ms' => 36000,
            'baseline_time_ms' => 36000,
            'first_seen_at' => now()->subDay(),
            'last_seen_at' => now(),
        ]);

        SeasonMapPlayerRecord::query()->create([
            'season_id' => $this->season->id,
            'map_id' => $this->map->id,
            'trackmania_player_id' => $player2->id,
            'global_position' => 4,
            'current_position' => null,
            'time_ms' => 42000,
            'baseline_time_ms' => 42000,
            'first_seen_at' => now()->subDay(),
            'last_seen_at' => now(),
        ]);

        $this->service->recalculate($this->season);

        $firstCount = PointEvent::query()->where('season_id', $this->season->id)->count();
        $firstTotal = PointEvent::query()->where('season_id', $this->season->id)->sum('points');

        PointEvent::query()->where('season_id', $this->season->id)->delete();
        PlayerMapMilestone::query()->where('season_id', $this->season->id)->delete();

        $this->service->recalculate($this->season);

        $secondCount = PointEvent::query()->where('season_id', $this->season->id)->count();
        $secondTotal = PointEvent::query()->where('season_id', $this->season->id)->sum('points');

        $this->assertSame($firstCount, $secondCount);
        $this->assertSame($firstTotal, $secondTotal);
    }

    public function test_time_equal_to_threshold_awards_medal(): void
    {
        $this->service->evaluateNewRecord($this->season, $this->map, $this->player, 50000, 1, true);

        $this->assertDatabaseHas('point_events', ['type' => 'medal_bronze', 'points' => 5]);
        $this->assertDatabaseMissing('point_events', ['type' => 'medal_silver']);
    }

    public function test_time_one_ms_under_threshold_still_awards_medal(): void
    {
        $this->service->evaluateNewRecord($this->season, $this->map, $this->player, 49999, 1, true);

        $this->assertDatabaseHas('point_events', ['type' => 'medal_bronze', 'points' => 5]);
    }

    public function test_standings_aggregate_correctly(): void
    {
        $player2 = TrackmaniaPlayer::query()->create(['account_id' => 'player-2', 'display_name' => 'Player 2']);

        $this->service->evaluateNewRecord($this->season, $this->map, $this->player, 40000, 1, true);

        $map2 = Map::query()->create([
            'uid' => 'map-2',
            'name' => 'Map 2',
            'bronze_time' => 50000,
            'silver_time' => 45000,
            'gold_time' => 40000,
            'author_time' => 35000,
        ]);

        $this->service->evaluateNewRecord($this->season, $map2, $player2, 45000, 2, true);

        $standingsService = new SeasonStandingsService;
        $standings = $standingsService->getStandings($this->season);

        $this->assertCount(2, $standings);

        $top = $standings->first();
        $this->assertSame($this->player->id, $top['player_id']);
        $this->assertSame(1, $top['position']);
    }
}
