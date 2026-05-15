<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\TrackmaniaClub */
class TrackmaniaClubResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'club_id' => $this->club_id,
            'name' => $this->name,
            'tag' => $this->tag,
            'description' => $this->description,
            'member_count' => $this->member_count,
            'icon_url' => $this->icon_url,
            'is_primary' => $this->is_primary,
            'last_synced_at' => $this->last_synced_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
