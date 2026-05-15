<?php

namespace App\Services\Trackmania;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class TrackmaniaIoClient
{
    private const BASE_URL_DEFAULT = 'https://trackmania.io';

    private const TIMEOUT_SECONDS_DEFAULT = 10;

    private const RETRY_TIMES_DEFAULT = 2;

    private const RETRY_SLEEP_MS_DEFAULT = 200;

    private const USER_AGENT_DEFAULT = 'tm-bot/1.0 (+https://example.com)';

    public function getPlayer(string $accountId): ?array
    {
        $response = $this->request()->get(
            sprintf('/api/player/%s', urlencode($accountId)),
        );

        if (! $response->successful()) {
            return null;
        }

        $payload = $response->json();
        if (! is_array($payload)) {
            return null;
        }

        $displayName = $payload['displayname'] ?? ($payload['name'] ?? null);

        return [
            'account_id' => (string) ($payload['accountid'] ?? $accountId),
            'display_name' => is_string($displayName) && trim($displayName) !== ''
                    ? trim($displayName)
                    : null,
            'zone_id' => is_string(data_get($payload, 'trophies.zone.id'))
                ? trim((string) data_get($payload, 'trophies.zone.id'))
                : null,
            'zone_name' => is_string(data_get($payload, 'trophies.zone.name'))
                ? trim((string) data_get($payload, 'trophies.zone.name'))
                : null,
        ];
    }

    private function request(): PendingRequest
    {
        return Http::baseUrl(
            rtrim(
                (string) config(
                    'services.trackmania_io.base_url',
                    self::BASE_URL_DEFAULT,
                ),
                '/',
            ),
        )
            ->acceptJson()
            ->withUserAgent(
                (string) config(
                    'services.trackmania_io.user_agent',
                    self::USER_AGENT_DEFAULT,
                ),
            )
            ->retry(
                (int) config(
                    'services.trackmania_io.retry_times',
                    self::RETRY_TIMES_DEFAULT,
                ),
                (int) config(
                    'services.trackmania_io.retry_sleep_ms',
                    self::RETRY_SLEEP_MS_DEFAULT,
                ),
                throw: false,
            )
            ->timeout(
                (int) config(
                    'services.trackmania_io.timeout_seconds',
                    self::TIMEOUT_SECONDS_DEFAULT,
                ),
            );
    }
}
