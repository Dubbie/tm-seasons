<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Resources\PointEventResource;
use App\Models\PointEvent;
use App\Models\Season;
use App\Services\Scoring\SeasonStandingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PublicSeasonScoringController extends Controller
{
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

    public function events(Request $request, string $slug): AnonymousResourceCollection
    {
        $season = Season::query()->where('slug', $slug)->firstOrFail();

        $query = PointEvent::query()
            ->where('season_id', $season->id)
            ->with(['player', 'map']);

        if ($request->filled('player_id')) {
            $query->where('trackmania_player_id', $request->integer('player_id'));
        }

        $query->orderBy('created_at', 'desc');

        return PointEventResource::collection($query->paginate(min($request->integer('per_page', 50), 100)));
    }

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
