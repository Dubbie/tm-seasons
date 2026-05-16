<?php

namespace App\Domains\Seasons\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LeaderboardPoll extends Model
{
    protected $fillable = [
        'season_id',
        'status',
        'started_at',
        'finished_at',
        'maps_polled_count',
        'players_processed_count',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'maps_polled_count' => 'integer',
            'players_processed_count' => 'integer',
        ];
    }

    public function season(): BelongsTo
    {
        return $this->belongsTo(Season::class);
    }

    public function snapshots(): HasMany
    {
        return $this->hasMany(LeaderboardSnapshot::class, 'leaderboard_poll_id');
    }
}
