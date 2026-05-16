<?php

namespace App\Domains\Trackmania\Http\Resources;

use App\Domains\Trackmania\Models\TrackmaniaPlayer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin TrackmaniaPlayer */
class TrackmaniaPlayerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'account_id' => $this->account_id,
            'display_name' => $this->display_name,
            'zone_id' => $this->zone_id,
            'zone_name' => $this->zone_name,
            'is_active' => $this->is_active,
            'last_seen_in_club_at' => $this->last_seen_in_club_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
