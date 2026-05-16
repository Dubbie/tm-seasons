<?php

namespace App\Domains\Seasons\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Domains\Activity\Http\Resources\PointEventResource;
use App\Domains\Activity\Services\SeasonActivityFeedService;
use App\Domains\Seasons\Models\Season;
use App\Domains\Seasons\Services\SeasonScoringService;
use App\Domains\Seasons\Services\SeasonStandingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AdminSeasonScoringController extends Controller
{
    public function standings(
        Season $season,
        SeasonStandingsService $standingsService,
        SeasonActivityFeedService $activityFeedService,
    ): JsonResponse
    {
        $standings = $standingsService->getStandings($season);

        return response()->json([
            'data' => [
                'season_id' => $season->id,
                'standings' => $standings,
                'total_events' => $activityFeedService->countForSeason($season),
            ],
        ]);
    }

    public function events(
        Request $request,
        Season $season,
        SeasonActivityFeedService $activityFeedService,
    ): AnonymousResourceCollection
    {
        $query = $activityFeedService->querySeasonEvents(
            season: $season,
            playerId: $request->filled('player_id') ? $request->integer('player_id') : null,
            mapId: $request->filled('map_id') ? $request->integer('map_id') : null,
            type: $request->filled('type') ? $request->string('type')->toString() : null,
        );

        return PointEventResource::collection($query->paginate(min($request->integer('per_page', 50), 100)));
    }

    public function recalculate(
        Season $season,
        SeasonScoringService $scoringService,
        SeasonActivityFeedService $activityFeedService,
    ): JsonResponse
    {
        $scoringService->recalculate($season);

        return response()->json([
            'message' => 'Recalculation complete.',
            'events_count' => $activityFeedService->countForSeason($season),
        ]);
    }
}
