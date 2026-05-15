<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PointEvent extends Model
{
    protected $fillable = [
        'season_id',
        'map_id',
        'trackmania_player_id',
        'type',
        'points',
        'description',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'season_id' => 'integer',
            'map_id' => 'integer',
            'trackmania_player_id' => 'integer',
            'points' => 'integer',
            'metadata' => 'array',
        ];
    }

    public function season(): BelongsTo
    {
        return $this->belongsTo(Season::class);
    }

    public function map(): BelongsTo
    {
        return $this->belongsTo(Map::class);
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(TrackmaniaPlayer::class, 'trackmania_player_id');
    }
}
