<?php

namespace App\Http\Resources;

use App\Models\Season;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Season */
class SeasonResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'starts_at' => $this->starts_at?->toIso8601String(),
            'ends_at' => $this->ends_at?->toIso8601String(),
            'is_active' => $this->is_active,
            'status' => $this->statusLabel(),
            'created_by_user_id' => $this->created_by_user_id,
            'created_by' => $this->whenLoaded('createdBy', fn () => [
                'id' => $this->createdBy?->id,
                'name' => $this->createdBy?->name,
                'discord_username' => $this->createdBy?->discord_username,
            ]),
            'maps' => MapResource::collection($this->whenLoaded('maps')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }

    private function statusLabel(): string
    {
        if ($this->is_active) {
            return 'active';
        }

        $now = now();

        if ($this->starts_at && $this->starts_at->isFuture()) {
            return 'upcoming';
        }

        if ($this->ends_at && $this->ends_at->lt($now)) {
            return 'finished';
        }

        return 'inactive';
    }
}
