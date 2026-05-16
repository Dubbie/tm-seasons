<?php

namespace App\Domains\Seasons\Http\Resources;

use App\Domains\Seasons\Models\SeasonMapPlayerRecord;
use App\Domains\Trackmania\Http\Resources\MapResource;
use App\Domains\Trackmania\Http\Resources\TrackmaniaPlayerResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin SeasonMapPlayerRecord */
class SeasonMapPlayerRecordResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'season_id' => $this->season_id,
            'map_id' => $this->map_id,
            'map' => new MapResource($this->whenLoaded('map')),
            'trackmania_player_id' => $this->trackmania_player_id,
            'player' => new TrackmaniaPlayerResource($this->whenLoaded('player')),
            'global_position' => $this->global_position,
            'current_position' => $this->current_position,
            'time_ms' => $this->time_ms,
            'baseline_time_ms' => $this->baseline_time_ms,
            'first_seen_at' => $this->first_seen_at?->toIso8601String(),
            'last_seen_at' => $this->last_seen_at?->toIso8601String(),
            'last_improved_at' => $this->last_improved_at?->toIso8601String(),
            'total_improvements' => $this->total_improvements,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
