<?php

namespace App\Domains\Trackmania\Data;

final readonly class TrackmaniaLeaderboard
{
    /**
     * @param  array<int, TrackmaniaLeaderboardEntry>  $entries
     */
    public function __construct(
        public string $groupUid,
        public string $mapUid,
        public array $entries,
    ) {}

    public static function fromApiResponse(string $mapUid, string $groupUid, array $payload): self
    {
        $tops = $payload['tops'] ?? [];
        $firstTop = is_array($tops) && isset($tops[0]) && is_array($tops[0]) ? $tops[0] : [];
        $rawEntries = $firstTop['top'] ?? $payload['top'] ?? $payload['entries'] ?? [];

        if (! is_array($rawEntries)) {
            $rawEntries = [];
        }

        $entries = [];

        foreach ($rawEntries as $rawEntry) {
            if (! is_array($rawEntry)) {
                continue;
            }

            $entries[] = TrackmaniaLeaderboardEntry::fromApiResponse($rawEntry);
        }

        return new self(
            groupUid: self::stringOrFallback($firstTop['groupUid'] ?? $payload['groupUid'] ?? null, $groupUid),
            mapUid: self::stringOrFallback($payload['mapUid'] ?? null, $mapUid),
            entries: $entries,
        );
    }

    private static function stringOrFallback(mixed $value, string $fallback): string
    {
        if ($value === null || $value === '') {
            return $fallback;
        }

        return (string) $value;
    }
}
