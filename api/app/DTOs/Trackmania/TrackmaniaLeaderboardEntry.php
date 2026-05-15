<?php

namespace App\DTOs\Trackmania;

final readonly class TrackmaniaLeaderboardEntry
{
    public function __construct(
        public ?string $accountId,
        public ?int $position,
        public int $score,
        public ?int $timestamp,
        public ?string $zoneId,
        public ?string $zoneName,
    ) {
    }

    public static function fromApiResponse(array $payload): self
    {
        $zone = is_array($payload['zone'] ?? null) ? $payload['zone'] : [];

        return new self(
            accountId: self::stringOrNull($payload['accountId'] ?? null),
            position: self::intOrNull($payload['position'] ?? $payload['rank'] ?? null),
            score: self::normalizeScore($payload['score'] ?? null),
            timestamp: self::intOrNull($payload['timestamp'] ?? $payload['updateTimestamp'] ?? null),
            zoneId: self::stringOrNull($zone['zoneId'] ?? $payload['zoneId'] ?? null),
            zoneName: self::stringOrNull($zone['name'] ?? $payload['zoneName'] ?? null),
        );
    }

    private static function normalizeScore(mixed $value): int
    {
        if ($value === null || $value === '' || ! is_numeric($value)) {
            return -1;
        }

        return (int) $value;
    }

    private static function stringOrNull(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (string) $value;
    }

    private static function intOrNull(mixed $value): ?int
    {
        if ($value === null || $value === '' || ! is_numeric($value)) {
            return null;
        }

        return (int) $value;
    }
}
