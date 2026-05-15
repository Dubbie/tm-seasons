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
        config()->set('cache.default', 'array');
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

    public function test_non_json_token_response_throws_exception(): void
    {
        Http::fake([
            'prod.trackmania.core.nadeo.online/*' => Http::response('plain-text', 200),
        ]);

        $this->expectException(TrackmaniaTokenException::class);
        $this->expectExceptionMessage('Trackmania token response was not valid JSON.');

        app(TrackmaniaTokenService::class)->getToken();
    }

    public function test_token_response_without_access_token_throws_exception(): void
    {
        Http::fake([
            'prod.trackmania.core.nadeo.online/*' => Http::response(['expiresIn' => 3600], 200),
        ]);

        $this->expectException(TrackmaniaTokenException::class);
        $this->expectExceptionMessage('did not include accessToken');

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

    public function test_get_club_and_members_are_normalized(): void
    {
        Http::fake([
            'prod.trackmania.core.nadeo.online/*' => Http::response(['accessToken' => 'token-123'], 200),
            'live-services.trackmania.nadeo.live/api/token/club/12345' => Http::response([
                'id' => 12345,
                'name' => 'Test Club',
                'tag' => 'TC',
                'description' => 'Desc',
                'memberCount' => 2,
                'iconUrl' => 'https://example.com/icon.png',
            ], 200),
            'live-services.trackmania.nadeo.live/api/token/club/12345/member*' => Http::response([
                'clubMemberList' => [
                    ['accountId' => 'player-1', 'displayName' => 'Player One', 'zone' => ['zoneId' => 'world', 'name' => 'World']],
                ],
            ], 200),
        ]);

        $client = app(TrackmaniaClient::class);
        $club = $client->getClub('12345');
        $members = $client->getClubMembers('12345');

        $this->assertSame('12345', $club['club_id']);
        $this->assertSame('Test Club', $club['name']);
        $this->assertCount(1, $members);
        $this->assertSame('player-1', $members[0]['account_id']);
    }

    public function test_token_refreshes_after_payload_driven_ttl(): void
    {
        Http::fake([
            'prod.trackmania.core.nadeo.online/*' => Http::sequence()
                ->push(['accessToken' => 'token-1', 'expiresIn' => 61], 200)
                ->push(['accessToken' => 'token-2', 'expiresIn' => 61], 200),
        ]);

        $service = app(TrackmaniaTokenService::class);

        $first = $service->getToken();
        $this->assertSame('token-1', $first);

        $this->travel(2)->seconds();

        $second = $service->getToken();
        $this->assertSame('token-2', $second);
    }

    public function test_get_club_members_does_not_fallback_to_other_variants_on_rate_limit(): void
    {
        Http::fake([
            'prod.trackmania.core.nadeo.online/*' => Http::response(['accessToken' => 'token-123'], 200),
            'live-services.trackmania.nadeo.live/api/token/club/12345/member*' => Http::response(['error' => 'rate_limited'], 429),
            'live-services.trackmania.nadeo.live/api/token/club/12345/members*' => Http::response(['clubMemberList' => []], 200),
        ]);

        try {
            app(TrackmaniaClient::class)->getClubMembers('12345');
            $this->fail('Expected TrackmaniaClientException to be thrown.');
        } catch (TrackmaniaClientException $exception) {
            $this->assertStringContainsString('status [429]', $exception->getMessage());
        }

        Http::assertSentCount(4);
    }

    public function test_get_club_members_falls_back_on_404_and_uses_members_endpoint(): void
    {
        Http::fake([
            'prod.trackmania.core.nadeo.online/*' => Http::response(['accessToken' => 'token-123'], 200),
            'live-services.trackmania.nadeo.live/api/token/club/12345/member?length=100&offset=0' => Http::response([], 404),
            'live-services.trackmania.nadeo.live/api/token/club/12345/member' => Http::response([], 404),
            'live-services.trackmania.nadeo.live/api/token/club/12345/members?length=100&offset=0' => Http::response([
                'clubMemberList' => [
                    ['accountId' => 'player-1', 'displayName' => 'Player One'],
                ],
            ], 200),
        ]);

        $members = app(TrackmaniaClient::class)->getClubMembers('12345');

        $this->assertCount(1, $members);
        $this->assertSame('player-1', $members[0]['account_id']);
    }

    public function test_token_ttl_falls_back_to_jwt_exp_when_expires_in_missing(): void
    {
        $payload = rtrim(strtr(base64_encode(json_encode(['exp' => now()->timestamp + 120])), '+/', '-_'), '=');
        $token = sprintf('a.%s.c', $payload);

        Http::fake([
            'prod.trackmania.core.nadeo.online/*' => Http::sequence()
                ->push(['accessToken' => $token], 200)
                ->push(['accessToken' => 'token-2', 'expiresIn' => 3600], 200),
        ]);

        $service = app(TrackmaniaTokenService::class);

        $first = $service->getToken();
        $this->assertSame($token, $first);

        $this->travel(59)->seconds();
        $second = $service->getToken();
        $this->assertSame($token, $second);

        $this->travel(2)->seconds();
        $third = $service->getToken();
        $this->assertSame('token-2', $third);
    }

    public function test_token_ttl_falls_back_to_config_when_token_is_not_jwt_and_expires_in_missing(): void
    {
        config()->set('trackmania.token_cache_ttl_fallback', 120);
        config()->set('trackmania.token_expiry_skew_seconds', 60);

        Http::fake([
            'prod.trackmania.core.nadeo.online/*' => Http::sequence()
                ->push(['accessToken' => 'plain-token'], 200)
                ->push(['accessToken' => 'next-token', 'expiresIn' => 3600], 200),
        ]);

        $service = app(TrackmaniaTokenService::class);

        $first = $service->getToken();
        $this->assertSame('plain-token', $first);

        $this->travel(59)->seconds();
        $second = $service->getToken();
        $this->assertSame('plain-token', $second);

        $this->travel(2)->seconds();
        $third = $service->getToken();
        $this->assertSame('next-token', $third);
    }
}
