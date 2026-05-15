<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\ClubMember */
class ClubMemberResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'trackmania_club_id' => $this->trackmania_club_id,
            'trackmania_player_id' => $this->trackmania_player_id,
            'joined_at' => $this->joined_at?->toIso8601String(),
            'synced_at' => $this->synced_at?->toIso8601String(),
            'is_active' => $this->is_active,
            'player' => new TrackmaniaPlayerResource($this->whenLoaded('player')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
