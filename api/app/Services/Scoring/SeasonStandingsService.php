<?php

namespace App\Services\Scoring;

use App\Models\PointEvent;
use App\Models\Season;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SeasonStandingsService
{
    public function getStandings(Season $season): Collection
    {
        $events = PointEvent::query()
            ->where('season_id', $season->id)
            ->with('player')
            ->get();

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
        $events = PointEvent::query()
            ->where('season_id', $season->id)
            ->where('trackmania_player_id', $playerId)
            ->with('player')
            ->get();

        if ($events->isEmpty()) {
            return null;
        }

        $player = $events->first()->player;
        $totalPoints = $events->sum('points');
        $uniqueMaps = $events->pluck('map_id')->filter()->unique()->count();
        $eventTypes = $events->pluck('type')->countBy()->toArray();

        $playerTotal = (int) DB::table('point_events')
            ->where('season_id', $season->id)
            ->where('trackmania_player_id', $playerId)
            ->sum('points');

        $position = DB::table('point_events')
            ->where('season_id', $season->id)
            ->select('trackmania_player_id')
            ->selectRaw('SUM(points) as total')
            ->groupBy('trackmania_player_id')
            ->havingRaw('SUM(points) > ?', [$playerTotal])
            ->count() + 1;

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
