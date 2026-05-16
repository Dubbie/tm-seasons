<?php

namespace App\Domains\Activity\Services;

use App\Domains\Activity\Models\PointEvent;
use App\Domains\Seasons\Models\Season;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SeasonActivityStatsService
{
    public function seasonEventsWithPlayers(Season $season): Collection
    {
        return PointEvent::query()
            ->where('season_id', $season->id)
            ->with('player')
            ->get();
    }

    public function playerEventsWithPlayer(Season $season, int $playerId): Collection
    {
        return PointEvent::query()
            ->where('season_id', $season->id)
            ->where('trackmania_player_id', $playerId)
            ->with('player')
            ->get();
    }

    public function playerTotalPoints(Season $season, int $playerId): int
    {
        return (int) DB::table('point_events')
            ->where('season_id', $season->id)
            ->where('trackmania_player_id', $playerId)
            ->sum('points');
    }

    public function higherScoringPlayersCount(Season $season, int $playerTotalPoints): int
    {
        return DB::table('point_events')
            ->where('season_id', $season->id)
            ->select('trackmania_player_id')
            ->selectRaw('SUM(points) as total')
            ->groupBy('trackmania_player_id')
            ->havingRaw('SUM(points) > ?', [$playerTotalPoints])
            ->count();
    }
}
