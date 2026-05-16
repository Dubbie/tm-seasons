<?php

namespace App\Domains\Activity\Services;

use App\Domains\Activity\Models\PointEvent;
use App\Domains\Seasons\Models\Season;
use Illuminate\Database\Eloquent\Builder;

class SeasonActivityFeedService
{
    public function countForSeason(Season $season): int
    {
        return PointEvent::query()
            ->where('season_id', $season->id)
            ->count();
    }

    public function querySeasonEvents(
        Season $season,
        ?int $playerId = null,
        ?int $mapId = null,
        ?string $type = null,
    ): Builder {
        return PointEvent::query()
            ->where('season_id', $season->id)
            ->when($playerId !== null, fn (Builder $query) => $query->where('trackmania_player_id', $playerId))
            ->when($mapId !== null, fn (Builder $query) => $query->where('map_id', $mapId))
            ->when($type !== null && $type !== '', fn (Builder $query) => $query->where('type', $type))
            ->with(['player', 'map'])
            ->orderBy('created_at', 'desc');
    }
}
