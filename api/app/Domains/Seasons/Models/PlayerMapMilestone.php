<?php

namespace App\Domains\Seasons\Models;

use App\Domains\Trackmania\Models\Map;
use App\Domains\Trackmania\Models\TrackmaniaPlayer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlayerMapMilestone extends Model
{
    protected $fillable = [
        'season_id',
        'map_id',
        'trackmania_player_id',
        'milestone_key',
        'achieved_at',
    ];

    protected function casts(): array
    {
        return [
            'achieved_at' => 'datetime',
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
