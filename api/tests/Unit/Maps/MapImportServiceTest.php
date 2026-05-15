<?php

namespace Tests\Unit\Maps;

use App\DTOs\Trackmania\TrackmaniaMap;
use App\Services\Maps\MapImportService;
use App\Services\Trackmania\TrackmaniaClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class MapImportServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_import_by_uid_creates_or_updates_map(): void
    {
        $client = Mockery::mock(TrackmaniaClient::class);
        $client->shouldReceive('getMapInfo')->twice()->with('uid-123')->andReturn(
            new TrackmaniaMap(
                uid: 'uid-123',
                mapId: 'nadeo-1',
                name: 'Test Map',
                authorAccountId: 'acc-1',
                authorTime: 45000,
                goldTime: 47000,
                silverTime: 52000,
                bronzeTime: 60000,
                thumbnailUrl: 'https://example.com/thumb.jpg',
                downloadUrl: null,
                mapStyle: 'Tech',
                mapType: 'Race',
                collectionName: 'Stadium',
                uploadTimestamp: 1_700_000_000,
                updateTimestamp: 1_700_000_100,
            ),
        );

        $service = new MapImportService($client);

        $first = $service->importByUid('uid-123');
        $second = $service->importByUid('uid-123');

        $this->assertSame($first->id, $second->id);
        $this->assertDatabaseHas('maps', [
            'uid' => 'uid-123',
            'nadeo_map_id' => 'nadeo-1',
            'name' => 'Test Map',
            'map_style' => 'Tech',
        ]);
    }
}
