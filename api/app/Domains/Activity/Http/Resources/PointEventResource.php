<?php

namespace App\Domains\Activity\Http\Resources;

use App\Domains\Trackmania\Http\Resources\MapResource;
use App\Domains\Trackmania\Http\Resources\TrackmaniaPlayerResource;
use App\Domains\Activity\Models\PointEvent;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin PointEvent */
class PointEventResource extends JsonResource
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
            'type' => $this->type,
            'points' => $this->points,
            'description' => $this->description,
            'metadata' => $this->metadata,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
