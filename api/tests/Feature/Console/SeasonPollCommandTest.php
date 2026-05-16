<?php

namespace Tests\Feature\Console;

use App\Domains\Seasons\Models\Season;
use App\Domains\Seasons\Models\SeasonStatus;
use App\Domains\Seasons\Services\SeasonLeaderboardPollingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class SeasonPollCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_poll_command_fails_for_missing_season(): void
    {
        $this->artisan('season:poll', ['seasonId' => '404'])
            ->expectsOutput('Season [404] not found.')
            ->assertFailed();
    }

    public function test_poll_command_outputs_poll_summary(): void
    {
        $season = Season::query()->create([
            'name' => 'Spring Cup',
            'status' => SeasonStatus::Active,
        ]);

        $pollingService = Mockery::mock(SeasonLeaderboardPollingService::class);
        $pollingService
            ->shouldReceive('pollSeason')
            ->once()
            ->with(Mockery::on(fn (Season $polledSeason): bool => $polledSeason->is($season)))
            ->andReturn([
                'maps_processed' => 2,
                'snapshots_created' => 5,
                'records_updated' => 3,
                'map_errors' => [],
                'total_maps' => 4,
            ]);

        $this->instance(SeasonLeaderboardPollingService::class, $pollingService);

        $this->artisan('season:poll', ['seasonId' => (string) $season->id])
            ->expectsOutput('Season poll completed.')
            ->expectsOutput('Maps processed: 2 / 4')
            ->expectsOutput('Snapshots created: 5')
            ->expectsOutput('Records updated: 3')
            ->assertSuccessful();
    }

    public function test_poll_active_command_exits_successfully_when_no_active_season_exists(): void
    {
        $this->artisan('season:poll-active')
            ->expectsOutput('No active season found.')
            ->assertSuccessful();
    }

    public function test_poll_active_command_polls_the_active_season(): void
    {
        $season = Season::query()->create([
            'name' => 'Spring Cup',
            'slug' => 'spring-cup',
            'status' => SeasonStatus::Active,
        ]);

        $pollingService = Mockery::mock(SeasonLeaderboardPollingService::class);
        $pollingService
            ->shouldReceive('pollSeason')
            ->once()
            ->with(Mockery::on(fn (Season $polledSeason): bool => $polledSeason->is($season)))
            ->andReturn([
                'maps_processed' => 1,
                'snapshots_created' => 2,
                'records_updated' => 1,
                'map_errors' => [],
                'total_maps' => 1,
            ]);

        $this->instance(SeasonLeaderboardPollingService::class, $pollingService);

        $this->artisan('season:poll-active')
            ->expectsOutput('Polling active season [Spring Cup] (spring-cup)...')
            ->expectsOutput('Season poll completed.')
            ->assertSuccessful();
    }
}
