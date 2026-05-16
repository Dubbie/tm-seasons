<?php

namespace App\Domains\Seasons\Services;

use App\Domains\Activity\Services\SeasonActivityStatsService;
use App\Domains\Seasons\Models\Season;
use Illuminate\Support\Collection;

class SeasonStandingsService
{
    public function __construct(
        private readonly ?SeasonActivityStatsService $activityStatsService = null,
    ) {}

    public function getStandings(Season $season): Collection
    {
        $events = ($this->activityStatsService ?? app(SeasonActivityStatsService::class))
            ->seasonEventsWithPlayers($season);

        $grouped = $events->groupBy('trackmania_player_id');

        $standings = $grouped->map(function (Collection $playerEvents, int $playerId): array {
            $player = $playerEvents->first()->player;
            $totalPoints = $playerEvents->sum('points');
            $uniqueMaps = $playerEvents->pluck('map_id')->filter()->unique()->count();
            $eventTypes = $playerEvents->pluck('type')->countBy()->toArray();

            return [
                'player_id' => $playerId,
                'player' => $player,
                'total_points' => $totalPoints,
                'maps_completed' => $uniqueMaps,
                'event_count' => $playerEvents->count(),
                'events_by_type' => $eventTypes,
            ];
        })->sortByDesc('total_points')->values();

        $position = 0;

        return $standings->map(function (array $entry) use (&$position): array {
            $position++;

            return array_merge($entry, ['position' => $position]);
        });
    }

    public function getPlayerStanding(Season $season, int $playerId): ?array
    {
        $activityStatsService = $this->activityStatsService ?? app(SeasonActivityStatsService::class);
        $events = $activityStatsService->playerEventsWithPlayer($season, $playerId);

        if ($events->isEmpty()) {
            return null;
        }

        $player = $events->first()->player;
        $totalPoints = $events->sum('points');
        $uniqueMaps = $events->pluck('map_id')->filter()->unique()->count();
        $eventTypes = $events->pluck('type')->countBy()->toArray();

        $playerTotal = $activityStatsService->playerTotalPoints($season, $playerId);
        $position = $activityStatsService->higherScoringPlayersCount($season, $playerTotal) + 1;

        return [
            'player_id' => $playerId,
            'player' => $player,
            'total_points' => $totalPoints,
            'position' => $position,
            'maps_completed' => $uniqueMaps,
            'event_count' => $events->count(),
            'events_by_type' => $eventTypes,
            'events' => $events->sortByDesc('created_at')->values(),
        ];
    }

    public function getPlayerMilestones(Season $season, int $playerId): Collection
    {
        return $season->milestones()
            ->where('trackmania_player_id', $playerId)
            ->with('map')
            ->orderBy('achieved_at', 'desc')
            ->get();
    }
}
