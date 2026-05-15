<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaderboardSnapshot extends Model
{
    protected $fillable = [
        'leaderboard_poll_id',
        'season_id',
        'map_id',
        'trackmania_player_id',
        'position',
        'time_ms',
        'zone_name',
        'recorded_at',
    ];

    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'time_ms' => 'integer',
            'recorded_at' => 'datetime',
        ];
    }

    public function poll(): BelongsTo
    {
        return $this->belongsTo(LeaderboardPoll::class, 'leaderboard_poll_id');
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
