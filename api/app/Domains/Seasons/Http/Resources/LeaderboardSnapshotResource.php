<?php

namespace App\Domains\Seasons\Http\Resources;

use App\Domains\Seasons\Models\LeaderboardSnapshot;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin LeaderboardSnapshot */
class LeaderboardSnapshotResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'leaderboard_poll_id' => $this->leaderboard_poll_id,
            'season_id' => $this->season_id,
            'map_id' => $this->map_id,
            'trackmania_player_id' => $this->trackmania_player_id,
            'player' => new TrackmaniaPlayerResource($this->whenLoaded('player')),
            'map' => new MapResource($this->whenLoaded('map')),
            'global_position' => $this->global_position,
            'current_position' => $this->current_position,
            'time_ms' => $this->time_ms,
            'zone_name' => $this->zone_name,
            'recorded_at' => $this->recorded_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
