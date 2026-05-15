<?php

namespace Tests\Unit\Trackmania;

use App\DTOs\Trackmania\TrackmaniaLeaderboard;
use App\DTOs\Trackmania\TrackmaniaMap;
use PHPUnit\Framework\TestCase;

class TrackmaniaDtoMappingTest extends TestCase
{
    public function test_map_dto_maps_payload_fields(): void
    {
        $payload = [
            'mapUid' => 'abc123',
            'mapId' => 'map-id-1',
            'name' => 'Ice Thing 03',
            'author' => 'author-1',
            'authorScore' => 31420,
            'goldScore' => 32000,
            'silverScore' => 34000,
            'bronzeScore' => 37000,
            'thumbnailUrl' => 'https://example.com/thumb.jpg',
            'downloadUrl' => 'https://example.com/map.Gbx',
            'mapStyle' => 'Tech',
            'mapType' => 'Race',
            'collectionName' => 'Stadium',
            'uploadTimestamp' => 1700000000,
            'updateTimestamp' => 1700001111,
        ];

        $dto = TrackmaniaMap::fromApiResponse('fallback-uid', $payload);

        $this->assertSame('abc123', $dto->uid);
        $this->assertSame('map-id-1', $dto->mapId);
        $this->assertSame('Ice Thing 03', $dto->name);
        $this->assertSame('author-1', $dto->authorAccountId);
        $this->assertSame(31420, $dto->authorTime);
    }

    public function test_leaderboard_dto_maps_top_entries_and_keeps_negative_score(): void
    {
        $payload = [
            'mapUid' => 'abc123',
            'tops' => [[
                'groupUid' => 'Personal_Best',
                'top' => [
                    [
                        'accountId' => 'player-1',
                        'position' => 1,
                        'score' => 30901,
                        'timestamp' => 1700000000,
                        'zone' => ['zoneId' => 'world', 'name' => 'World'],
                    ],
                    [
                        'accountId' => 'player-2',
                        'position' => 2,
                        'score' => -1,
                        'timestamp' => 1700000500,
                        'zone' => ['zoneId' => 'world', 'name' => 'World'],
                    ],
                ],
            ]],
        ];

        $dto = TrackmaniaLeaderboard::fromApiResponse('fallback-map', 'Personal_Best', $payload);

        $this->assertSame('Personal_Best', $dto->groupUid);
        $this->assertSame('abc123', $dto->mapUid);
        $this->assertCount(2, $dto->entries);
        $this->assertSame(-1, $dto->entries[1]->score);
    }
}
