<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Map */
class MapResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uid' => $this->uid,
            'nadeo_map_id' => $this->nadeo_map_id,
            'name' => $this->name,
            'author_account_id' => $this->author_account_id,
            'author_name' => $this->author_name,
            'author_time' => $this->author_time,
            'gold_time' => $this->gold_time,
            'silver_time' => $this->silver_time,
            'bronze_time' => $this->bronze_time,
            'map_type' => $this->map_type,
            'map_style' => $this->map_style,
            'thumbnail_url' => $this->thumbnail_url,
            'collection_name' => $this->collection_name,
            'uploaded_at' => $this->uploaded_at?->toIso8601String(),
            'source_updated_at' => $this->updated_at_source?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'season_pivot' => $this->whenPivotLoaded('season_maps', function (): array {
                return [
                    'id' => $this->pivot->id,
                    'order_index' => $this->pivot->order_index,
                    'is_active' => (bool) $this->pivot->is_active,
                ];
            }),
        ];
    }
}
