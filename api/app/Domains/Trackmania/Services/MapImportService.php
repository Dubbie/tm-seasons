<?php

namespace App\Domains\Trackmania\Services;

use App\Domains\Trackmania\Exceptions\TrackmaniaClientException;
use App\Domains\Trackmania\Models\Map;
use Carbon\CarbonImmutable;

class MapImportService
{
    public function __construct(
        private readonly TrackmaniaClient $trackmaniaClient,
    ) {}

    /**
     * @throws TrackmaniaClientException
     */
    public function importByUid(string $uid): Map
    {
        $mapInfo = $this->trackmaniaClient->getMapInfo($uid);

        return Map::query()->updateOrCreate(
            ['uid' => $mapInfo->uid],
            [
                'nadeo_map_id' => $mapInfo->mapId,
                'name' => $mapInfo->name,
                'author_account_id' => $mapInfo->authorAccountId,
                'author_time' => $mapInfo->authorTime,
                'gold_time' => $mapInfo->goldTime,
                'silver_time' => $mapInfo->silverTime,
                'bronze_time' => $mapInfo->bronzeTime,
                'map_type' => $mapInfo->mapType,
                'map_style' => $mapInfo->mapStyle,
                'thumbnail_url' => $mapInfo->thumbnailUrl,
                'collection_name' => $mapInfo->collectionName,
                'uploaded_at' => $this->timestampToCarbon($mapInfo->uploadTimestamp),
                'updated_at_source' => $this->timestampToCarbon($mapInfo->updateTimestamp),
            ],
        );
    }

    private function timestampToCarbon(?int $timestamp): ?CarbonImmutable
    {
        if (! $timestamp) {
            return null;
        }

        return CarbonImmutable::createFromTimestampUTC($timestamp);
    }
}
