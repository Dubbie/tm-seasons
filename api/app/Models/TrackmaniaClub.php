<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class TrackmaniaClub extends Model
{
    use HasFactory;

    protected $fillable = [
        'club_id',
        'name',
        'tag',
        'description',
        'member_count',
        'icon_url',
        'is_primary',
        'last_synced_at',
    ];

    protected function casts(): array
    {
        return [
            'member_count' => 'integer',
            'is_primary' => 'boolean',
            'last_synced_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $club): void {
            if (! $club->is_primary) {
                return;
            }

            static::query()
                ->whereKeyNot($club->getKey())
                ->where('is_primary', true)
                ->update(['is_primary' => false]);
        });
    }

    public static function primary(): ?self
    {
        return static::query()->where('is_primary', true)->first();
    }

    public function players(): BelongsToMany
    {
        return $this->belongsToMany(TrackmaniaPlayer::class, 'club_members')
            ->withPivot(['id', 'joined_at', 'synced_at', 'is_active'])
            ->withTimestamps();
    }
}
