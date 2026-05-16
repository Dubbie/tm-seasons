<?php

namespace App\Domains\Seasons\Models;

use App\Domains\Trackmania\Models\Map;
use App\Domains\Trackmania\Models\TrackmaniaPlayer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SeasonMapPlayerRecord extends Model
{
    protected $fillable = [
        'season_id',
        'map_id',
        'trackmania_player_id',
        'global_position',
        'current_position',
        'time_ms',
        'baseline_time_ms',
        'first_seen_at',
        'last_seen_at',
        'last_improved_at',
        'total_improvements',
    ];

    protected function casts(): array
    {
        return [
            'global_position' => 'integer',
            'current_position' => 'integer',
            'time_ms' => 'integer',
            'baseline_time_ms' => 'integer',
            'first_seen_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'last_improved_at' => 'datetime',
            'total_improvements' => 'integer',
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
