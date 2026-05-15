<?php

namespace App\DTOs\Trackmania;

final readonly class TrackmaniaMap
{
    public function __construct(
        public string $uid,
        public ?string $mapId,
        public ?string $name,
        public ?string $authorAccountId,
        public ?int $authorTime,
        public ?int $goldTime,
        public ?int $silverTime,
        public ?int $bronzeTime,
        public ?string $thumbnailUrl,
        public ?string $downloadUrl,
        public ?string $mapStyle,
        public ?string $mapType,
        public ?string $collectionName,
        public ?int $uploadTimestamp,
        public ?int $updateTimestamp,
    ) {}

    public static function fromApiResponse(string $requestedMapUid, array $payload): self
    {
        $map = is_array($payload['map'] ?? null) ? $payload['map'] : $payload;

        return new self(
            uid: (string) ($map['mapUid'] ?? $map['uid'] ?? $requestedMapUid),
            mapId: self::stringOrNull($map['mapId'] ?? $map['id'] ?? null),
            name: self::stringOrNull($map['name'] ?? null),
            authorAccountId: self::stringOrNull($map['author'] ?? $map['authorAccountId'] ?? null),
            authorTime: self::intOrNull($map['authorScore'] ?? $map['authorTime'] ?? null),
            goldTime: self::intOrNull($map['goldScore'] ?? $map['goldTime'] ?? null),
            silverTime: self::intOrNull($map['silverScore'] ?? $map['silverTime'] ?? null),
            bronzeTime: self::intOrNull($map['bronzeScore'] ?? $map['bronzeTime'] ?? null),
            thumbnailUrl: self::stringOrNull($map['thumbnailUrl'] ?? $map['thumbnail'] ?? null),
            downloadUrl: self::stringOrNull($map['downloadUrl'] ?? null),
            mapStyle: self::stringOrNull($map['mapStyle'] ?? $map['styleName'] ?? null),
            mapType: self::stringOrNull($map['mapType'] ?? $map['mapTypeName'] ?? null),
            collectionName: self::stringOrNull($map['collectionName'] ?? null),
            uploadTimestamp: self::intOrNull($map['uploadTimestamp'] ?? null),
            updateTimestamp: self::intOrNull($map['updateTimestamp'] ?? null),
        );
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
        if ($value === null || $value === '') {
            return null;
        }

        if (! is_numeric($value)) {
            return null;
        }

        return (int) $value;
    }
}
