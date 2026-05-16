<?php

namespace App\Domains\Seasons\Http\Controllers\Public;

use App\Domains\Seasons\Http\Resources\SeasonMapPlayerRecordResource;
use App\Domains\Seasons\Http\Resources\SeasonResource;
use App\Domains\Seasons\Models\Season;
use App\Domains\Seasons\Models\SeasonMapPlayerRecord;
use App\Domains\Trackmania\Http\Resources\MapResource;
use App\Domains\Trackmania\Models\Map;
use App\Http\Controllers\Controller;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;

#[Group('Seasons - Public', description: 'Public season discovery, leaderboards, standings, and activity endpoints.', weight: 40)]
class PublicSeasonLeaderboardController extends Controller
{
    /**
     * Get public season leaderboard.
     *
     * Returns each active season map with ordered player records.
     *
     * @unauthenticated
     */
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

    /**
     * Get public map leaderboard.
     *
     * Returns ordered player records for one map within a season.
     *
     * @unauthenticated
     */
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
