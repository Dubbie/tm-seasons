<?php

namespace App\Domains\Activity\Services;

use App\Domains\Activity\Models\PointEvent;
use App\Domains\Seasons\Models\Season;
use App\Domains\Trackmania\Models\Map;
use App\Domains\Trackmania\Models\TrackmaniaPlayer;
use Illuminate\Support\Facades\DB;

class SeasonPointEventWriteService
{
    public function eventExistsForPlayer(Season $season, int $playerId, string $type): bool
    {
        return PointEvent::query()
            ->where('season_id', $season->id)
            ->where('trackmania_player_id', $playerId)
            ->where('type', $type)
            ->exists();
    }

    public function createEvent(
        Season $season,
        Map $map,
        TrackmaniaPlayer $player,
        string $type,
        int $points,
        ?string $description = null,
        ?array $metadata = null,
        ?\DateTimeInterface $createdAt = null,
    ): void {
        if ($createdAt !== null) {
            DB::table('point_events')->insert([
                'season_id' => $season->id,
                'map_id' => $map->id,
                'trackmania_player_id' => $player->id,
                'type' => $type,
                'points' => $points,
                'description' => $description,
                'metadata' => $metadata !== null ? json_encode($metadata) : null,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);

            return;
        }

        PointEvent::query()->create([
            'season_id' => $season->id,
            'map_id' => $map->id,
            'trackmania_player_id' => $player->id,
            'type' => $type,
            'points' => $points,
            'description' => $description,
            'metadata' => $metadata,
        ]);
    }

    public function createSeasonRewardEvent(
        Season $season,
        int $playerId,
        string $type,
        int $points,
        ?string $description = null,
        ?array $metadata = null,
    ): void {
        PointEvent::query()->create([
            'season_id' => $season->id,
            'map_id' => null,
            'trackmania_player_id' => $playerId,
            'type' => $type,
            'points' => $points,
            'description' => $description,
            'metadata' => $metadata,
        ]);
    }

    public function clearSeasonEvents(Season $season): void
    {
        PointEvent::query()
            ->where('season_id', $season->id)
            ->delete();
    }
}
