<?php

namespace App\Services\Trackmania;

use App\DTOs\Trackmania\TrackmaniaLeaderboard;
use App\DTOs\Trackmania\TrackmaniaMap;
use App\Exceptions\Trackmania\TrackmaniaClientException;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class TrackmaniaClient
{
    private const BASE_URL_DEFAULT = 'https://live-services.trackmania.nadeo.live';
    private const AUDIENCE_DEFAULT = 'NadeoLiveServices';
    private const RETRY_TIMES_DEFAULT = 3;
    private const RETRY_SLEEP_MS_DEFAULT = 200;
    private const TIMEOUT_SECONDS_DEFAULT = 10;
    private const USER_AGENT_DEFAULT = 'tm-bot/1.0 (+https://example.com)';

    public function __construct(
        private readonly TrackmaniaTokenService $tokenService,
    ) {
    }

    public function getMapInfo(string $mapUid): TrackmaniaMap
    {
        $response = $this->request()
            ->get(sprintf('/api/token/map/%s', urlencode($mapUid)));

        $this->ensureSuccessful($response, 'map', $mapUid);
        $payload = $this->arrayPayload($response);

        return TrackmaniaMap::fromApiResponse($mapUid, $payload);
    }

    public function getMapLeaderboard(
        string $mapUid,
        int $length = 100,
        int $offset = 0,
        string $groupUid = 'Personal_Best',
    ): TrackmaniaLeaderboard {
        $response = $this->request()
            ->get(sprintf('/api/token/leaderboard/group/%s/map/%s/top', urlencode($groupUid), urlencode($mapUid)), [
                'length' => max(1, $length),
                'offset' => max(0, $offset),
                'onlyWorld' => 'true',
            ]);

        $this->ensureSuccessful($response, 'leaderboard', $mapUid);
        $payload = $this->arrayPayload($response);

        return TrackmaniaLeaderboard::fromApiResponse($mapUid, $groupUid, $payload);
    }

    private function request(): PendingRequest
    {
        $baseUrl = rtrim((string) config('trackmania.base_url', self::BASE_URL_DEFAULT), '/');
        $token = $this->tokenService->getToken((string) config('trackmania.audience', self::AUDIENCE_DEFAULT));

        return Http::baseUrl($baseUrl)
            ->acceptJson()
            ->withUserAgent((string) config('trackmania.user_agent', self::USER_AGENT_DEFAULT))
            ->retry(
                (int) config('trackmania.retry_times', self::RETRY_TIMES_DEFAULT),
                (int) config('trackmania.retry_sleep_ms', self::RETRY_SLEEP_MS_DEFAULT),
                throw: false,
            )
            ->timeout((int) config('trackmania.timeout_seconds', self::TIMEOUT_SECONDS_DEFAULT))
            ->withHeaders([
                'Authorization' => sprintf('nadeo_v1 t=%s', $token),
            ]);
    }

    private function ensureSuccessful(Response $response, string $resource, string $mapUid): void
    {
        if ($resource === 'map' && $response->status() === 404) {
            throw new TrackmaniaClientException(sprintf('Trackmania map not found for uid [%s].', $mapUid));
        }

        if (! $response->successful()) {
            throw new TrackmaniaClientException(sprintf(
                'Trackmania %s request failed with status [%d].',
                $resource,
                $response->status(),
            ));
        }
    }

    private function arrayPayload(Response $response): array
    {
        $payload = $response->json();

        return is_array($payload) ? $payload : [];
    }
}
