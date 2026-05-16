<?php

namespace App\Domains\Trackmania\Services;

use App\Domains\Trackmania\Data\TrackmaniaLeaderboard;
use App\Domains\Trackmania\Data\TrackmaniaMap;
use App\Domains\Trackmania\Exceptions\TrackmaniaClientException;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
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
    ) {}

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

    public function getClub(int|string $clubId): array
    {
        $response = $this->request()
            ->get(sprintf('/api/token/club/%s', urlencode((string) $clubId)));

        $this->ensureSuccessful($response, 'club', (string) $clubId);
        $payload = $this->arrayPayload($response);

        return [
            'club_id' => (string) ($payload['id'] ?? $payload['clubId'] ?? $clubId),
            'name' => (string) ($payload['name'] ?? ''),
            'tag' => $this->nullableString($payload['tag'] ?? null),
            'description' => $this->nullableString($payload['description'] ?? null),
            'member_count' => isset($payload['memberCount']) ? (int) $payload['memberCount'] : null,
            'icon_url' => $this->nullableString($payload['iconUrl'] ?? null),
        ];
    }

    public function getClubMembers(int|string $clubId): array
    {
        $clubId = (string) $clubId;
        $length = 100;
        $offset = 0;
        $allMembers = [];

        while (true) {
            $attempts = [
                ['/api/token/club/%s/member', ['length' => $length, 'offset' => $offset]],
                ['/api/token/club/%s/member', []],
                ['/api/token/club/%s/members', ['length' => $length, 'offset' => $offset]],
                ['/api/token/club/%s/members', []],
            ];

            $response = null;

            foreach ($attempts as $index => [$pathTemplate, $query]) {
                $candidate = $this->request($index === 0 ? null : 1)
                    ->get(sprintf($pathTemplate, urlencode($clubId)), $query);

                if ($candidate->successful()) {
                    $response = $candidate;

                    break;
                }

                $response = $candidate;

                if (! $this->shouldTryNextClubMembersVariant($candidate)) {
                    break;
                }
            }

            if (! $response instanceof Response) {
                throw new TrackmaniaClientException('Trackmania club members request failed before any response was received.');
            }

            $this->ensureSuccessful($response, 'club members', $clubId);

            $payload = $this->arrayPayload($response);
            $members = $payload['clubMemberList'] ?? $payload['members'] ?? [];

            if (! is_array($members) || $members === []) {
                break;
            }

            $allMembers = [...$allMembers, ...$members];

            if (count($members) < $length) {
                break;
            }

            $offset += $length;
        }

        return array_values(array_filter(array_map(function ($member): ?array {
            if (! is_array($member)) {
                return null;
            }

            $accountId = $this->nullableString($member['accountId'] ?? null);
            $displayName = $this->nullableString($member['displayName'] ?? $member['name'] ?? null) ?? $accountId;
            if (! $accountId) {
                return null;
            }

            return [
                'account_id' => $accountId,
                'display_name' => $displayName,
                'zone_id' => $this->nullableString(data_get($member, 'zone.zoneId')),
                'zone_name' => $this->nullableString(data_get($member, 'zone.name')),
                'joined_at' => $this->toIsoDateTime($member['joinDate'] ?? null),
            ];
        }, $allMembers)));
    }

    private function request(?int $retryTimes = null): PendingRequest
    {
        $baseUrl = rtrim((string) config('trackmania.base_url', self::BASE_URL_DEFAULT), '/');
        $token = $this->tokenService->getToken((string) config('trackmania.audience', self::AUDIENCE_DEFAULT));
        $resolvedRetryTimes = $retryTimes ?? (int) config('trackmania.retry_times', self::RETRY_TIMES_DEFAULT);

        return Http::baseUrl($baseUrl)
            ->acceptJson()
            ->withUserAgent((string) config('trackmania.user_agent', self::USER_AGENT_DEFAULT))
            ->retry(
                max(1, $resolvedRetryTimes),
                (int) config('trackmania.retry_sleep_ms', self::RETRY_SLEEP_MS_DEFAULT),
                throw: false,
            )
            ->timeout((int) config('trackmania.timeout_seconds', self::TIMEOUT_SECONDS_DEFAULT))
            ->withHeaders([
                'Authorization' => sprintf('nadeo_v1 t=%s', $token),
            ]);
    }

    private function shouldTryNextClubMembersVariant(Response $response): bool
    {
        return in_array($response->status(), [404, 405], true);
    }

    private function ensureSuccessful(Response $response, string $resource, string $identifier): void
    {
        if ($resource === 'map' && $response->status() === 404) {
            throw new TrackmaniaClientException(sprintf('Trackmania map not found for uid [%s].', $identifier));
        }

        if (! $response->successful()) {
            $body = trim((string) $response->body());
            $detail = $body !== '' ? sprintf(' Response: %s', mb_substr($body, 0, 300)) : '';

            throw new TrackmaniaClientException(sprintf(
                'Trackmania %s request failed with status [%d].%s',
                $resource,
                $response->status(),
                $detail,
            ));
        }
    }

    private function nullableString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }

    private function toIsoDateTime(mixed $value): ?string
    {
        if (is_numeric($value)) {
            return CarbonImmutable::createFromTimestamp((int) $value)->toIso8601String();
        }

        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return CarbonImmutable::parse($value)->toIso8601String();
        } catch (\Throwable) {
            return null;
        }
    }

    private function arrayPayload(Response $response): array
    {
        $payload = $response->json();

        return is_array($payload) ? $payload : [];
    }
}
