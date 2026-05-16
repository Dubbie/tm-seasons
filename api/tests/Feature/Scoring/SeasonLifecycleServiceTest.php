<?php

namespace Tests\Feature\Scoring;

use App\Domains\Identity\Models\User;
use App\Domains\Seasons\Models\Season;
use App\Domains\Seasons\Models\SeasonMapPlayerRecord;
use App\Domains\Seasons\Models\SeasonStatus;
use App\Domains\Seasons\Services\SeasonLifecycleService;
use App\Domains\Trackmania\Models\Map;
use App\Domains\Trackmania\Models\TrackmaniaPlayer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeasonLifecycleServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_scheduled_season_becomes_active(): void
    {
        $season = Season::query()->create([
            'name' => 'Scheduled',
            'status' => SeasonStatus::Scheduled,
            'starts_at' => now()->subMinute(),
            'ends_at' => now()->addDay(),
        ]);

        $result = app(SeasonLifecycleService::class)->updateAutomaticStatuses();

        $season->refresh();
        $this->assertSame('active', $season->status->value);
        $this->assertContains($season->id, $result['activated']);
    }

    public function test_active_season_becomes_ended(): void
    {
        $season = Season::query()->create([
            'name' => 'Active',
            'status' => SeasonStatus::Active,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->subMinute(),
        ]);

        app(SeasonLifecycleService::class)->updateAutomaticStatuses();

        $this->assertSame('ended', $season->refresh()->status->value);
    }

    public function test_finalization_awards_highest_matching_final_reward_once(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $season = Season::query()->create([
            'name' => 'Ended',
            'status' => SeasonStatus::Ended,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->subHour(),
            'created_by_user_id' => $admin->id,
        ]);

        $map = Map::query()->create(['uid' => 'm1', 'name' => 'Map']);
        $p1 = TrackmaniaPlayer::query()->create(['account_id' => 'p1', 'display_name' => 'P1']);
        $p2 = TrackmaniaPlayer::query()->create(['account_id' => 'p2', 'display_name' => 'P2']);

        SeasonMapPlayerRecord::query()->create([
            'season_id' => $season->id,
            'map_id' => $map->id,
            'trackmania_player_id' => $p1->id,
            'time_ms' => 1000,
            'current_position' => 1,
            'first_seen_at' => now(),
            'last_seen_at' => now(),
        ]);

        SeasonMapPlayerRecord::query()->create([
            'season_id' => $season->id,
            'map_id' => $map->id,
            'trackmania_player_id' => $p2->id,
            'time_ms' => 2000,
            'current_position' => 8,
            'first_seen_at' => now(),
            'last_seen_at' => now(),
        ]);

        app(SeasonLifecycleService::class)->finalizeSeason($season, $admin->id);

        $this->assertDatabaseHas('point_events', ['trackmania_player_id' => $p1->id, 'type' => 'final_top_1']);
        $this->assertDatabaseMissing('point_events', ['trackmania_player_id' => $p1->id, 'type' => 'final_top_5']);

        $this->assertDatabaseHas('point_events', ['trackmania_player_id' => $p2->id, 'type' => 'final_top_10']);
        $this->assertDatabaseMissing('point_events', ['trackmania_player_id' => $p2->id, 'type' => 'final_top_20']);

        $this->assertSame('finalized', $season->refresh()->status->value);
    }

    public function test_finalization_only_runs_once(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $season = Season::query()->create([
            'name' => 'Ended',
            'status' => SeasonStatus::Ended,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->subHour(),
            'created_by_user_id' => $admin->id,
        ]);

        app(SeasonLifecycleService::class)->finalizeSeason($season, $admin->id);

        $this->expectException(\RuntimeException::class);
        app(SeasonLifecycleService::class)->finalizeSeason($season->refresh(), $admin->id);
    }
}
