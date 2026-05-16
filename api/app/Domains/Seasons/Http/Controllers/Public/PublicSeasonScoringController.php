<?php

namespace App\Domains\Seasons\Http\Controllers\Public;

use App\Domains\Activity\Http\Resources\PointEventResource;
use App\Domains\Activity\Services\SeasonActivityFeedService;
use App\Domains\Seasons\Models\Season;
use App\Domains\Seasons\Services\SeasonStandingsService;
use App\Http\Controllers\Controller;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

#[Group('Seasons - Public', description: 'Public season discovery, leaderboards, standings, and activity endpoints.', weight: 40)]
class PublicSeasonScoringController extends Controller
{
    /**
     * Get public season standings.
     *
     * Returns calculated point standings for a season by slug.
     *
     * @unauthenticated
     */
    public function standings(string $slug, SeasonStandingsService $standingsService): JsonResponse
    {
        $season = Season::query()->where('slug', $slug)->firstOrFail();

        $standings = $standingsService->getStandings($season);

        return response()->json([
            'data' => [
                'season_id' => $season->id,
                'season_name' => $season->name,
                'standings' => $standings,
            ],
        ]);
    }

    /**
     * List public season events.
     *
     * Returns paginated point events for a season, optionally filtered by player.
     *
     * @unauthenticated
     *
     * @response AnonymousResourceCollection<PointEventResource>
     */
    public function events(
        Request $request,
        string $slug,
        SeasonActivityFeedService $activityFeedService,
    ): AnonymousResourceCollection {
        $season = Season::query()->where('slug', $slug)->firstOrFail();

        $query = $activityFeedService->querySeasonEvents(
            season: $season,
            playerId: $request->filled('player_id') ? $request->integer('player_id') : null,
        );

        return PointEventResource::collection($query->paginate(min($request->integer('per_page', 50), 100)));
    }

    /**
     * Get public player standings.
     *
     * Returns one player's season standing and map milestones.
     *
     * @unauthenticated
     */
    public function player(string $slug, int $player, SeasonStandingsService $standingsService): JsonResponse
    {
        $season = Season::query()->where('slug', $slug)->firstOrFail();

        $standing = $standingsService->getPlayerStanding($season, $player);

        if ($standing === null) {
            return response()->json(['message' => 'Player not found in season.'], 404);
        }

        $milestones = $standingsService->getPlayerMilestones($season, $player);

        return response()->json([
            'data' => array_merge($standing, [
                'milestones' => $milestones,
            ]),
        ]);
    }
}
