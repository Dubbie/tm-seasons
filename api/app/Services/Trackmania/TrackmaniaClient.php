<?php

namespace App\Services\Trackmania;

use App\DTOs\Trackmania\TrackmaniaLeaderboard;
use App\DTOs\Trackmania\TrackmaniaMap;
use App\Exceptions\Trackmania\TrackmaniaClientException;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\PendingRequest;

class TrackmaniaClient
{
    public function __construct(
        private readonly TrackmaniaTokenService $tokenService,
    ) {
    }

    public function getMapInfo(string $mapUid): TrackmaniaMap
    {
        $response = $this->request()
            ->get(sprintf('/api/token/map/%s', urlencode($mapUid)));

        if ($response->status() === 404) {
            throw new TrackmaniaClientException(sprintf('Trackmania map not found for uid [%s].', $mapUid));
        }

        if (! $response->successful()) {
            throw new TrackmaniaClientException(sprintf('Trackmania map request failed with status [%d].', $response->status()));
        }

        $payload = $response->json();
        if (! is_array($payload)) {
            $payload = [];
        }

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

        if (! $response->successful()) {
            throw new TrackmaniaClientException(sprintf('Trackmania leaderboard request failed with status [%d].', $response->status()));
        }

        $payload = $response->json();
        if (! is_array($payload)) {
            $payload = [];
        }

        return TrackmaniaLeaderboard::fromApiResponse($mapUid, $groupUid, $payload);
    }

    private function request(): PendingRequest
    {
        $baseUrl = rtrim((string) config('trackmania.base_url', 'https://live-services.trackmania.nadeo.live'), '/');
        $token = $this->tokenService->getToken((string) config('trackmania.audience', 'NadeoLiveServices'));

        return Http::baseUrl($baseUrl)
            ->acceptJson()
            ->retry(
                (int) config('trackmania.retry_times', 3),
                (int) config('trackmania.retry_sleep_ms', 200),
                throw: false,
            )
            ->timeout((int) config('trackmania.timeout_seconds', 10))
            ->withHeaders([
                'Authorization' => sprintf('nadeo_v1 t=%s', $token),
            ]);
    }
}
