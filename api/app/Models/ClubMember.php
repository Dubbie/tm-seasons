<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClubMember extends Model
{
    use HasFactory;

    protected $fillable = [
        'trackmania_club_id',
        'trackmania_player_id',
        'joined_at',
        'synced_at',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'joined_at' => 'datetime',
            'synced_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function club(): BelongsTo
    {
        return $this->belongsTo(TrackmaniaClub::class, 'trackmania_club_id');
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(TrackmaniaPlayer::class, 'trackmania_player_id');
    }
}
