<?php

namespace App\Domains\Seasons\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Domains\Seasons\Http\Resources\SeasonMapPlayerRecordResource;
use App\Domains\Seasons\Models\Season;
use App\Domains\Seasons\Models\SeasonMapPlayerRecord;
use App\Domains\Seasons\Services\SeasonLeaderboardPollingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AdminSeasonPollController extends Controller
{
    public function poll(Season $season, SeasonLeaderboardPollingService $pollingService): JsonResponse
    {
        if (! $season->canPoll()) {
            return response()->json([
                'message' => 'Only active seasons can be polled.',
            ], 422);
        }

        try {
            $result = $pollingService->pollSeason($season);
        } catch (\Throwable $exception) {
            return response()->json([
                'message' => 'Poll failed.',
                'error' => $exception->getMessage(),
            ], 500);
        }

        return response()->json([
            'message' => 'Poll completed successfully.',
            'total_maps' => $result['total_maps'],
            'maps_processed' => $result['maps_processed'],
            'snapshots_created' => $result['snapshots_created'],
            'records_updated' => $result['records_updated'],
        ]);
    }

    public function records(Request $request, Season $season): AnonymousResourceCollection
    {
        $query = SeasonMapPlayerRecord::query()
            ->where('season_id', $season->id)
            ->with(['map', 'player']);

        if ($request->filled('map_id')) {
            $query->where('map_id', $request->integer('map_id'));
        }

        if ($request->filled('player_id')) {
            $query->where('trackmania_player_id', $request->integer('player_id'));
        }

        $sort = $request->string('sort', 'time_ms')->toString();
        $direction = $request->string('direction', 'asc')->toString();

        if (in_array($sort, ['global_position', 'current_position', 'time_ms', 'last_improved_at'], true)) {
            $query->orderBy($sort, $direction === 'desc' ? 'desc' : 'asc');
        }

        return SeasonMapPlayerRecordResource::collection($query->paginate(25));
    }
}
