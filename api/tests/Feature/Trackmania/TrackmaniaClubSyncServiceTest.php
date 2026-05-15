<?php

namespace Tests\Feature\Trackmania;

use App\Models\ClubMember;
use App\Models\TrackmaniaClub;
use App\Models\TrackmaniaPlayer;
use App\Services\Trackmania\TrackmaniaClient;
use App\Services\Trackmania\TrackmaniaClubSyncService;
use App\Services\Trackmania\TrackmaniaIoClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class TrackmaniaClubSyncServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_repeat_sync_updates_and_deactivates_missing_members(): void
    {
        $client = Mockery::mock(TrackmaniaClient::class);
        $client->shouldReceive('getClub')->twice()->with('12345')->andReturn([
            'club_id' => '12345',
            'name' => 'Test Club',
            'tag' => null,
            'description' => null,
            'member_count' => 2,
            'icon_url' => null,
        ]);
        $client->shouldReceive('getClubMembers')->once()->with('12345')->andReturn([
            ['account_id' => 'player-1', 'display_name' => 'Player One', 'zone_id' => 'world', 'zone_name' => 'World', 'joined_at' => null],
            ['account_id' => 'player-2', 'display_name' => 'Player Two', 'zone_id' => 'eu', 'zone_name' => 'Europe', 'joined_at' => null],
        ]);
        $client->shouldReceive('getClubMembers')->once()->with('12345')->andReturn([
            ['account_id' => 'player-1', 'display_name' => 'Player One Updated', 'zone_id' => 'world', 'zone_name' => 'World', 'joined_at' => null],
        ]);

        $tmio = Mockery::mock(TrackmaniaIoClient::class);
        $tmio->shouldReceive('getPlayer')->never();

        $service = new TrackmaniaClubSyncService($client, $tmio);

        $service->syncClub('12345');
        $service->syncClub('12345');

        $club = TrackmaniaClub::query()->where('club_id', '12345')->firstOrFail();
        $activeMember = TrackmaniaPlayer::query()->where('account_id', 'player-1')->firstOrFail();
        $inactiveMember = TrackmaniaPlayer::query()->where('account_id', 'player-2')->firstOrFail();

        $this->assertSame('Player One Updated', $activeMember->display_name);
        $this->assertTrue($activeMember->is_active);
        $this->assertFalse($inactiveMember->is_active);

        $this->assertDatabaseHas('club_members', [
            'trackmania_club_id' => $club->id,
            'trackmania_player_id' => $inactiveMember->id,
            'is_active' => false,
        ]);
        $this->assertSame(2, ClubMember::query()->count());
    }

    public function test_sync_does_not_downgrade_existing_resolved_display_name(): void
    {
        $client = Mockery::mock(TrackmaniaClient::class);
        $client->shouldReceive('getClub')->once()->with('12345')->andReturn([
            'club_id' => '12345',
            'name' => 'Test Club',
            'tag' => null,
            'description' => null,
            'member_count' => 1,
            'icon_url' => null,
        ]);
        $client->shouldReceive('getClubMembers')->once()->with('12345')->andReturn([
            ['account_id' => 'player-1', 'display_name' => 'player-1', 'zone_id' => 'world', 'zone_name' => 'World', 'joined_at' => null],
        ]);

        $tmio = Mockery::mock(TrackmaniaIoClient::class);
        $tmio->shouldReceive('getPlayer')->once()->andReturn(null);

        $service = new TrackmaniaClubSyncService($client, $tmio);
        $service->syncClub('12345');

        $player = TrackmaniaPlayer::query()->where('account_id', 'player-1')->firstOrFail();
        $player->display_name = 'Resolved Name';
        $player->save();

        $client2 = Mockery::mock(TrackmaniaClient::class);
        $client2->shouldReceive('getClub')->once()->with('12345')->andReturn([
            'club_id' => '12345',
            'name' => 'Test Club',
            'tag' => null,
            'description' => null,
            'member_count' => 1,
            'icon_url' => null,
        ]);
        $client2->shouldReceive('getClubMembers')->once()->with('12345')->andReturn([
            ['account_id' => 'player-1', 'display_name' => 'player-1', 'zone_id' => 'world', 'zone_name' => 'World', 'joined_at' => null],
        ]);

        $tmio2 = Mockery::mock(TrackmaniaIoClient::class);
        $tmio2->shouldReceive('getPlayer')->never();

        $service2 = new TrackmaniaClubSyncService($client2, $tmio2);
        $service2->syncClub('12345');

        $player->refresh();
        $this->assertSame('Resolved Name', $player->display_name);
    }

    public function test_sync_does_not_clear_existing_zone_when_incoming_zone_is_missing(): void
    {
        $client = Mockery::mock(TrackmaniaClient::class);
        $client->shouldReceive('getClub')->once()->with('12345')->andReturn([
            'club_id' => '12345',
            'name' => 'Test Club',
            'tag' => null,
            'description' => null,
            'member_count' => 1,
            'icon_url' => null,
        ]);
        $client->shouldReceive('getClubMembers')->once()->with('12345')->andReturn([
            ['account_id' => 'player-1', 'display_name' => 'player-1', 'zone_id' => null, 'zone_name' => null, 'joined_at' => null],
        ]);

        $tmio = Mockery::mock(TrackmaniaIoClient::class);
        $tmio->shouldReceive('getPlayer')->once()->andReturn(null);

        TrackmaniaClub::query()->create([
            'club_id' => '12345',
            'name' => 'Test Club',
            'is_primary' => true,
        ]);

        TrackmaniaPlayer::query()->create([
            'account_id' => 'player-1',
            'display_name' => 'player-1',
            'zone_id' => 'world',
            'zone_name' => 'World',
            'is_active' => true,
        ]);

        $service = new TrackmaniaClubSyncService($client, $tmio);
        $service->syncClub('12345');

        $player = TrackmaniaPlayer::query()->where('account_id', 'player-1')->firstOrFail();
        $this->assertSame('world', $player->zone_id);
        $this->assertSame('World', $player->zone_name);
    }
}
