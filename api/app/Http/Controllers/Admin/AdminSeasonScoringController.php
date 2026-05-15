<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\PointEventResource;
use App\Models\PointEvent;
use App\Models\Season;
use App\Services\Scoring\SeasonScoringService;
use App\Services\Scoring\SeasonStandingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AdminSeasonScoringController extends Controller
{
    public function standings(Season $season, SeasonStandingsService $standingsService): JsonResponse
    {
        $standings = $standingsService->getStandings($season);

        return response()->json([
            'data' => [
                'season_id' => $season->id,
                'standings' => $standings,
                'total_events' => PointEvent::query()->where('season_id', $season->id)->count(),
            ],
        ]);
    }

    public function events(Request $request, Season $season): AnonymousResourceCollection
    {
        $query = PointEvent::query()
            ->where('season_id', $season->id)
            ->with(['player', 'map']);

        if ($request->filled('player_id')) {
            $query->where('trackmania_player_id', $request->integer('player_id'));
        }

        if ($request->filled('map_id')) {
            $query->where('map_id', $request->integer('map_id'));
        }

        if ($request->filled('type')) {
            $query->where('type', $request->string('type'));
        }

        $query->orderBy('created_at', 'desc');

        return PointEventResource::collection($query->paginate(min($request->integer('per_page', 50), 100)));
    }

    public function recalculate(Season $season, SeasonScoringService $scoringService): JsonResponse
    {
        $scoringService->recalculate($season);

        return response()->json([
            'message' => 'Recalculation complete.',
            'events_count' => PointEvent::query()->where('season_id', $season->id)->count(),
        ]);
    }
}
