<?php

namespace Tests\Feature\Scoring;

use App\Domains\Activity\Models\PointEvent;
use App\Domains\Seasons\Models\PlayerMapMilestone;
use App\Domains\Seasons\Models\Season;
use App\Domains\Seasons\Services\SeasonStandingsService;
use App\Domains\Trackmania\Models\Map;
use App\Domains\Trackmania\Models\TrackmaniaPlayer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeasonStandingsServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_standings_aggregates_and_orders_players(): void
    {
        $season = Season::query()->create(['name' => 'S1']);
        $mapA = Map::query()->create(['uid' => 'map-a', 'name' => 'Map A']);
        $mapB = Map::query()->create(['uid' => 'map-b', 'name' => 'Map B']);
        $playerA = TrackmaniaPlayer::query()->create(['account_id' => 'p-a', 'display_name' => 'Player A']);
        $playerB = TrackmaniaPlayer::query()->create(['account_id' => 'p-b', 'display_name' => 'Player B']);

        PointEvent::query()->create([
            'season_id' => $season->id,
            'map_id' => $mapA->id,
            'trackmania_player_id' => $playerA->id,
            'type' => 'first_finish',
            'points' => 10,
        ]);
        PointEvent::query()->create([
            'season_id' => $season->id,
            'map_id' => $mapB->id,
            'trackmania_player_id' => $playerA->id,
            'type' => 'medal_gold',
            'points' => 20,
        ]);
        PointEvent::query()->create([
            'season_id' => $season->id,
            'map_id' => $mapA->id,
            'trackmania_player_id' => $playerB->id,
            'type' => 'first_finish',
            'points' => 15,
        ]);

        $standings = app(SeasonStandingsService::class)->getStandings($season);

        $this->assertCount(2, $standings);
        $this->assertSame($playerA->id, $standings[0]['player_id']);
        $this->assertSame(30, $standings[0]['total_points']);
        $this->assertSame(2, $standings[0]['maps_completed']);
        $this->assertSame(2, $standings[0]['event_count']);
        $this->assertSame(1, $standings[0]['position']);
        $this->assertSame(['first_finish' => 1, 'medal_gold' => 1], $standings[0]['events_by_type']);
        $this->assertSame($playerB->id, $standings[1]['player_id']);
        $this->assertSame(2, $standings[1]['position']);
    }

    public function test_get_player_standing_returns_null_when_player_has_no_events(): void
    {
        $season = Season::query()->create(['name' => 'S1']);
        $player = TrackmaniaPlayer::query()->create(['account_id' => 'p-a', 'display_name' => 'Player A']);

        $standing = app(SeasonStandingsService::class)->getPlayerStanding($season, $player->id);

        $this->assertNull($standing);
    }

    public function test_get_player_standing_and_milestones_are_computed_correctly(): void
    {
        $season = Season::query()->create(['name' => 'S1']);
        $mapA = Map::query()->create(['uid' => 'map-a', 'name' => 'Map A']);
        $mapB = Map::query()->create(['uid' => 'map-b', 'name' => 'Map B']);
        $playerA = TrackmaniaPlayer::query()->create(['account_id' => 'p-a', 'display_name' => 'Player A']);
        $playerB = TrackmaniaPlayer::query()->create(['account_id' => 'p-b', 'display_name' => 'Player B']);

        PointEvent::query()->create([
            'season_id' => $season->id,
            'map_id' => $mapA->id,
            'trackmania_player_id' => $playerA->id,
            'type' => 'first_finish',
            'points' => 15,
        ]);
        PointEvent::query()->create([
            'season_id' => $season->id,
            'map_id' => $mapA->id,
            'trackmania_player_id' => $playerA->id,
            'type' => 'medal_silver',
            'points' => 5,
        ]);
        PointEvent::query()->create([
            'season_id' => $season->id,
            'map_id' => $mapB->id,
            'trackmania_player_id' => $playerB->id,
            'type' => 'first_finish',
            'points' => 30,
        ]);

        $oldMilestone = PlayerMapMilestone::query()->create([
            'season_id' => $season->id,
            'map_id' => $mapA->id,
            'trackmania_player_id' => $playerA->id,
            'milestone_key' => 'bronze',
            'achieved_at' => now()->subMinute(),
        ]);
        $newMilestone = PlayerMapMilestone::query()->create([
            'season_id' => $season->id,
            'map_id' => $mapB->id,
            'trackmania_player_id' => $playerA->id,
            'milestone_key' => 'gold',
            'achieved_at' => now(),
        ]);

        $standing = app(SeasonStandingsService::class)->getPlayerStanding($season, $playerA->id);
        $milestones = app(SeasonStandingsService::class)->getPlayerMilestones($season, $playerA->id);

        $this->assertNotNull($standing);
        $this->assertSame($playerA->id, $standing['player_id']);
        $this->assertSame(20, $standing['total_points']);
        $this->assertSame(2, $standing['event_count']);
        $this->assertSame(1, $standing['maps_completed']);
        $this->assertSame(2, $standing['position']);
        $this->assertSame(['first_finish' => 1, 'medal_silver' => 1], $standing['events_by_type']);
        $this->assertCount(2, $standing['events']);
        $this->assertCount(2, $milestones);
        $this->assertSame($newMilestone->id, $milestones[0]->id);
        $this->assertSame($oldMilestone->id, $milestones[1]->id);
        $this->assertTrue($milestones[0]->relationLoaded('map'));
    }
}
