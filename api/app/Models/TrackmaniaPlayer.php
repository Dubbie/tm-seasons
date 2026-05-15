<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class TrackmaniaPlayer extends Model
{
    use HasFactory;

    protected $fillable = [
        'account_id',
        'display_name',
        'zone_id',
        'zone_name',
        'is_active',
        'last_seen_in_club_at',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'last_seen_in_club_at' => 'datetime',
        ];
    }

    public function clubs(): BelongsToMany
    {
        return $this->belongsToMany(TrackmaniaClub::class, 'club_members')
            ->withPivot(['id', 'joined_at', 'synced_at', 'is_active'])
            ->withTimestamps();
    }
}
