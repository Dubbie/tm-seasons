<?php

namespace Tests\Feature\Admin;

use App\Models\LeaderboardPoll;
use App\Models\Season;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminLeaderboardPollControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['is_admin' => true]);
    }

    public function test_index_lists_polls(): void
    {
        $season = Season::query()->create(['name' => 'Test Season']);
        LeaderboardPoll::query()->create([
            'season_id' => $season->id, 'status' => 'completed',
            'started_at' => now(), 'finished_at' => now(),
            'maps_polled_count' => 5, 'players_processed_count' => 20,
        ]);

        $this->actingAs($this->admin)
            ->getJson('/api/admin/polls')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_show_returns_poll_details(): void
    {
        $season = Season::query()->create(['name' => 'Test Season']);
        $poll = LeaderboardPoll::query()->create([
            'season_id' => $season->id, 'status' => 'completed',
            'started_at' => now(), 'finished_at' => now(),
            'maps_polled_count' => 5, 'players_processed_count' => 20,
        ]);

        $this->actingAs($this->admin)
            ->getJson("/api/admin/polls/{$poll->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $poll->id)
            ->assertJsonPath('data.status', 'completed');
    }

    public function test_polls_endpoint_requires_admin(): void
    {
        $nonAdmin = User::factory()->create(['is_admin' => false]);

        $this->actingAs($nonAdmin)
            ->getJson('/api/admin/polls')
            ->assertForbidden();
    }
}
