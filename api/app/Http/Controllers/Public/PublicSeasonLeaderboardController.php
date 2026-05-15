<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Resources\MapResource;
use App\Http\Resources\SeasonMapPlayerRecordResource;
use App\Http\Resources\SeasonResource;
use App\Models\Map;
use App\Models\Season;
use App\Models\SeasonMapPlayerRecord;
use Illuminate\Http\JsonResponse;

class PublicSeasonLeaderboardController extends Controller
{
    public function seasonLeaderboard(string $slug): JsonResponse
    {
        $season = Season::query()
            ->where('slug', $slug)
            ->with(['maps' => fn ($query) => $query
                ->wherePivot('is_active', true)
                ->orderBy('season_maps.order_index')
                ->orderBy('season_maps.id'),
            ])
            ->firstOrFail();

        $records = SeasonMapPlayerRecord::query()
            ->where('season_id', $season->id)
            ->with(['player', 'map'])
            ->orderBy('map_id')
            ->orderBy('time_ms')
            ->get()
            ->groupBy('map_id');

        $leaderboard = $records->map(function ($mapRecords, $mapId) use ($season): array {
            $map = $season->maps->firstWhere('id', (int) $mapId);

            return [
                'map' => $map ? new MapResource($map) : ['id' => (int) $mapId],
                'entries' => SeasonMapPlayerRecordResource::collection($mapRecords),
            ];
        })->values();

        return response()->json([
            'data' => [
                'season' => new SeasonResource($season),
                'leaderboard' => $leaderboard,
            ],
        ]);
    }

    public function mapLeaderboard(string $slug, Map $map): JsonResponse
    {
        $season = Season::query()->where('slug', $slug)->firstOrFail();

        $records = SeasonMapPlayerRecord::query()
            ->where('season_id', $season->id)
            ->where('map_id', $map->id)
            ->with('player')
            ->orderBy('time_ms')
            ->get();

        return response()->json([
            'data' => [
                'map' => new MapResource($map),
                'entries' => SeasonMapPlayerRecordResource::collection($records),
            ],
        ]);
    }
}
