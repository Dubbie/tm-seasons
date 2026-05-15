<?php

namespace App\Http\Resources;

use App\Models\LeaderboardPoll;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin LeaderboardPoll */
class LeaderboardPollResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'season_id' => $this->season_id,
            'season' => new SeasonResource($this->whenLoaded('season')),
            'status' => $this->status,
            'started_at' => $this->started_at?->toIso8601String(),
            'finished_at' => $this->finished_at?->toIso8601String(),
            'maps_polled_count' => $this->maps_polled_count,
            'players_processed_count' => $this->players_processed_count,
            'snapshot_count' => $this->when($this->relationLoaded('snapshots'), fn () => $this->snapshots->count()),
            'error_message' => $this->error_message,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
