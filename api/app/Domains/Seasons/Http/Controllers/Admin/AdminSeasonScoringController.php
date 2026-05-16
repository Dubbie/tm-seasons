<?php

namespace App\Domains\Seasons\Http\Controllers\Admin;

use App\Domains\Activity\Http\Resources\PointEventResource;
use App\Domains\Activity\Services\SeasonActivityFeedService;
use App\Domains\Seasons\Models\Season;
use App\Domains\Seasons\Services\SeasonScoringService;
use App\Domains\Seasons\Services\SeasonStandingsService;
use App\Http\Controllers\Controller;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

#[Group('Seasons - Admin', description: 'Admin-only season lifecycle, map assignment, polling, scoring, and finalization endpoints.', weight: 50)]
class AdminSeasonScoringController extends Controller
{
    /**
     * Get admin season points.
     *
     * Returns calculated standings and total point event count for a season.
     */
    public function standings(
        Season $season,
        SeasonStandingsService $standingsService,
        SeasonActivityFeedService $activityFeedService,
    ): JsonResponse {
        $standings = $standingsService->getStandings($season);

        return response()->json([
            'data' => [
                'season_id' => $season->id,
                'standings' => $standings,
                'total_events' => $activityFeedService->countForSeason($season),
            ],
        ]);
    }

    /**
     * List admin season events.
     *
     * Returns paginated point events with optional player, map, and type filters.
     *
     * @response AnonymousResourceCollection<PointEventResource>
     */
    public function events(
        Request $request,
        Season $season,
        SeasonActivityFeedService $activityFeedService,
    ): AnonymousResourceCollection {
        $query = $activityFeedService->querySeasonEvents(
            season: $season,
            playerId: $request->filled('player_id') ? $request->integer('player_id') : null,
            mapId: $request->filled('map_id') ? $request->integer('map_id') : null,
            type: $request->filled('type') ? $request->string('type')->toString() : null,
        );

        return PointEventResource::collection($query->paginate(min($request->integer('per_page', 50), 100)));
    }

    /**
     * Recalculate season points.
     *
     * Rebuilds point events for a season and returns the updated event count.
     */
    public function recalculate(
        Season $season,
        SeasonScoringService $scoringService,
        SeasonActivityFeedService $activityFeedService,
    ): JsonResponse {
        $scoringService->recalculate($season);

        return response()->json([
            'message' => 'Recalculation complete.',
            'events_count' => $activityFeedService->countForSeason($season),
        ]);
    }
}
