<?php

namespace Tests\Feature\Trackmania;

use App\Services\Trackmania\TrackmaniaIoClient;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TrackmaniaIoClientTest extends TestCase
{
    public function test_get_player_maps_display_name_and_zone(): void
    {
        Http::fake([
            'trackmania.io/api/player/*' => Http::response([
                'accountid' => 'acc-1',
                'displayname' => 'Player One',
                'trophies' => [
                    'zone' => ['name' => 'World'],
                ],
            ], 200),
        ]);

        $player = app(TrackmaniaIoClient::class)->getPlayer('acc-1');

        $this->assertSame('acc-1', $player['account_id']);
        $this->assertSame('Player One', $player['display_name']);
        $this->assertNull($player['zone_id']);
        $this->assertSame('World', $player['zone_name']);
    }

    public function test_get_player_returns_null_for_non_successful_response(): void
    {
        Http::fake([
            'trackmania.io/api/player/*' => Http::response(['error' => 'not found'], 404),
        ]);

        $player = app(TrackmaniaIoClient::class)->getPlayer('missing');

        $this->assertNull($player);
    }
}
