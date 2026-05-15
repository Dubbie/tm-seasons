<?php

namespace Tests\Feature\Trackmania;

use App\Exceptions\Trackmania\TrackmaniaClientException;
use App\Exceptions\Trackmania\TrackmaniaTokenException;
use App\Services\Trackmania\TrackmaniaClient;
use App\Services\Trackmania\TrackmaniaTokenService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TrackmaniaClientTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('trackmania.auth_base_url', 'https://prod.trackmania.core.nadeo.online');
        config()->set('trackmania.base_url', 'https://live-services.trackmania.nadeo.live');
        config()->set('trackmania.dedicated_login', 'test-login');
        config()->set('trackmania.dedicated_password', 'test-password');
        Cache::flush();
    }

    public function test_get_map_info_and_leaderboard(): void
    {
        Http::fake([
            'prod.trackmania.core.nadeo.online/*' => Http::response([
                'accessToken' => 'token-123',
                'expiresIn' => 3600,
            ], 200),
            'live-services.trackmania.nadeo.live/api/token/map/*' => Http::response([
                'mapUid' => 'map-1',
                'name' => 'Sample Map',
                'authorScore' => 35000,
                'goldScore' => 36000,
                'silverScore' => 39000,
                'bronzeScore' => 42000,
            ], 200),
            'live-services.trackmania.nadeo.live/api/token/leaderboard/*' => Http::response([
                'mapUid' => 'map-1',
                'tops' => [[
                    'groupUid' => 'Personal_Best',
                    'top' => [
                        ['accountId' => 'wr-player', 'position' => 1, 'score' => 30901, 'timestamp' => 1700000000],
                    ],
                ]],
            ], 200),
        ]);

        $client = app(TrackmaniaClient::class);

        $map = $client->getMapInfo('map-1');
        $leaderboard = $client->getMapLeaderboard('map-1');

        $this->assertSame('Sample Map', $map->name);
        $this->assertCount(1, $leaderboard->entries);
        $this->assertSame('wr-player', $leaderboard->entries[0]->accountId);

        Http::assertSent(function ($request): bool {
            if (! str_contains($request->url(), '/api/token/map/')) {
                return true;
            }

            return $request->hasHeader('Authorization', 'nadeo_v1 t=token-123');
        });

        Http::assertSent(function ($request): bool {
            if (! str_contains($request->url(), '/api/token/leaderboard/')) {
                return true;
            }

            return str_contains($request->url(), 'onlyWorld=true')
                && str_contains($request->url(), 'length=100')
                && str_contains($request->url(), 'offset=0');
        });
    }

    public function test_empty_leaderboard_response_returns_empty_entries(): void
    {
        Http::fake([
            'prod.trackmania.core.nadeo.online/*' => Http::response(['accessToken' => 'token-123'], 200),
            'live-services.trackmania.nadeo.live/api/token/leaderboard/*' => Http::response([], 200),
        ]);

        $client = app(TrackmaniaClient::class);
        $leaderboard = $client->getMapLeaderboard('map-2');

        $this->assertCount(0, $leaderboard->entries);
        $this->assertSame('map-2', $leaderboard->mapUid);
    }

    public function test_map_not_found_throws_exception(): void
    {
        Http::fake([
            'prod.trackmania.core.nadeo.online/*' => Http::response(['accessToken' => 'token-123'], 200),
            'live-services.trackmania.nadeo.live/api/token/map/*' => Http::response([], 404),
        ]);

        $this->expectException(TrackmaniaClientException::class);
        $this->expectExceptionMessage('Trackmania map not found');

        app(TrackmaniaClient::class)->getMapInfo('missing-map');
    }

    public function test_failed_token_response_throws_exception(): void
    {
        Http::fake([
            'prod.trackmania.core.nadeo.online/*' => Http::response(['error' => 'invalid'], 401),
        ]);

        $this->expectException(TrackmaniaTokenException::class);

        app(TrackmaniaTokenService::class)->getToken();
    }

    public function test_leaderboard_score_of_minus_one_is_preserved(): void
    {
        Http::fake([
            'prod.trackmania.core.nadeo.online/*' => Http::response(['accessToken' => 'token-123'], 200),
            'live-services.trackmania.nadeo.live/api/token/leaderboard/*' => Http::response([
                'tops' => [[
                    'groupUid' => 'Personal_Best',
                    'top' => [
                        ['accountId' => 'player-1', 'position' => 1, 'score' => -1],
                    ],
                ]],
            ], 200),
        ]);

        $leaderboard = app(TrackmaniaClient::class)->getMapLeaderboard('map-3');

        $this->assertSame(-1, $leaderboard->entries[0]->score);
    }
}
