<?php

namespace Tests\Feature\Admin;

use App\Models\ClubMember;
use App\Models\TrackmaniaPlayer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AdminClubControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_routes_require_admin_auth(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)->getJson('/api/admin/clubs')->assertForbidden();
        $this->actingAs($user)->getJson('/api/admin/club')->assertForbidden();
        $this->actingAs($user)->postJson('/api/admin/clubs/sync', ['club_id' => '12345'])->assertForbidden();
        $this->actingAs($user)->postJson('/api/admin/club/sync', ['club_id' => '12345'])->assertForbidden();
    }

    public function test_sync_club_creates_club_and_members(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $this->fakeTrackmaniaClubResponses();

        $this->actingAs($admin)
            ->postJson('/api/admin/clubs/sync', ['club_id' => '12345'])
            ->assertOk()
            ->assertJsonPath('imported', 2)
            ->assertJsonPath('deactivated', 0)
            ->assertJsonPath('total_members', 2);

        $this->assertDatabaseHas('trackmania_clubs', ['club_id' => '12345', 'name' => 'Test Club']);
        $this->assertDatabaseHas('trackmania_clubs', ['club_id' => '12345', 'is_primary' => true]);
        $this->assertDatabaseHas('trackmania_players', ['account_id' => 'player-1', 'display_name' => 'Player One']);
        $this->assertDatabaseHas('trackmania_players', ['account_id' => 'player-2', 'display_name' => 'Player Two']);
        $this->assertSame(2, ClubMember::query()->count());
    }

    public function test_duplicate_players_are_not_created(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $this->fakeTrackmaniaClubResponses();

        $this->actingAs($admin)->postJson('/api/admin/clubs/sync', ['club_id' => '12345'])->assertOk();
        $this->actingAs($admin)->postJson('/api/admin/clubs/sync', ['club_id' => '12345'])->assertOk();

        $this->assertSame(2, TrackmaniaPlayer::query()->count());
    }

    public function test_admin_primary_club_endpoints_work(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $this->fakeTrackmaniaClubResponses();

        $this->actingAs($admin)
            ->postJson('/api/admin/club/sync', ['club_id' => '12345'])
            ->assertOk();

        $this->actingAs($admin)
            ->getJson('/api/admin/club')
            ->assertOk()
            ->assertJsonPath('data.club_id', '12345')
            ->assertJsonPath('data.is_primary', true);

        $this->actingAs($admin)
            ->getJson('/api/admin/club/members')
            ->assertOk()
            ->assertJsonPath('meta.total', 2);
    }

    public function test_primary_sync_can_run_without_club_id_when_primary_exists(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $this->fakeTrackmaniaClubResponses();

        $this->actingAs($admin)
            ->postJson('/api/admin/club/sync', ['club_id' => '12345'])
            ->assertOk();

        $this->actingAs($admin)
            ->postJson('/api/admin/club/sync', [])
            ->assertOk()
            ->assertJsonPath('club.club_id', '12345');
    }

    public function test_api_failure_is_handled_cleanly(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        Http::fake([
            'prod.trackmania.core.nadeo.online/*' => Http::response(['accessToken' => 'token-123'], 200),
            'live-services.trackmania.nadeo.live/api/token/club/12345' => Http::response([], 500),
        ]);

        $this->actingAs($admin)
            ->postJson('/api/admin/clubs/sync', ['club_id' => '12345'])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Trackmania API failure while syncing club.');
    }

    public function test_malformed_members_payload_is_handled(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        Http::fake([
            'prod.trackmania.core.nadeo.online/*' => Http::response(['accessToken' => 'token-123'], 200),
            'live-services.trackmania.nadeo.live/api/token/club/12345/member*' => Http::response([
                'clubMemberList' => [
                    ['accountId' => '', 'displayName' => ''],
                    'bad-row',
                ],
            ], 200),
            'live-services.trackmania.nadeo.live/api/token/club/12345' => Http::response([
                'id' => 12345,
                'name' => 'Test Club',
            ], 200),
        ]);

        $this->actingAs($admin)
            ->postJson('/api/admin/clubs/sync', ['club_id' => '12345'])
            ->assertOk()
            ->assertJsonPath('total_members', 0)
            ->assertJsonPath('imported', 0);
    }

    private function fakeTrackmaniaClubResponses(): void
    {
        Http::fake([
            'prod.trackmania.core.nadeo.online/*' => Http::response(['accessToken' => 'token-123'], 200),
            'live-services.trackmania.nadeo.live/api/token/club/12345/member*' => Http::response([
                'clubMemberList' => [
                    ['accountId' => 'player-1', 'displayName' => 'Player One', 'joinDate' => 1700000000, 'zone' => ['zoneId' => 'world', 'name' => 'World']],
                    ['accountId' => 'player-2', 'displayName' => 'Player Two', 'joinDate' => 1700001111, 'zone' => ['zoneId' => 'eu', 'name' => 'Europe']],
                ],
            ], 200),
            'live-services.trackmania.nadeo.live/api/token/club/12345' => Http::response([
                'id' => 12345,
                'name' => 'Test Club',
                'tag' => 'TC',
                'description' => 'desc',
                'memberCount' => 2,
                'iconUrl' => 'https://example.com/icon.png',
            ], 200),
        ]);
    }
}
