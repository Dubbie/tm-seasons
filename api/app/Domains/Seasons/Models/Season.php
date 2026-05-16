<?php

namespace App\Domains\Seasons\Models;

use App\Domains\Trackmania\Models\Map;
use App\Domains\Seasons\Models\PlayerMapMilestone;
use App\Domains\Activity\Models\PointEvent;
use App\Domains\Identity\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Str;

class Season extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'starts_at',
        'ends_at',
        'is_active',
        'status',
        'finalized_at',
        'finalized_by_user_id',
        'created_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'status' => SeasonStatus::class,
            'finalized_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $season): void {
            if (! $season->isDirty('name') && $season->slug) {
                return;
            }

            $baseSlug = Str::slug($season->name ?: 'season');
            $slug = $baseSlug;
            $suffix = 2;

            while (static::query()
                ->where('slug', $slug)
                ->whereKeyNot($season->getKey())
                ->exists()) {
                $slug = sprintf('%s-%d', $baseSlug, $suffix);
                $suffix++;
            }

            $season->slug = $slug;
        });

    }

    public function canPoll(): bool
    {
        return $this->status === SeasonStatus::Active;
    }

    public function getIsActiveAttribute(): bool
    {
        return $this->status === SeasonStatus::Active;
    }

    public function setIsActiveAttribute(bool $value): void
    {
        $this->attributes['status'] = $value ? SeasonStatus::Active->value : SeasonStatus::Draft->value;
    }

    public function maps(): BelongsToMany
    {
        return $this->belongsToMany(Map::class, 'season_maps')
            ->withPivot(['id', 'order_index', 'is_active'])
            ->withTimestamps();
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function pointEvents(): HasMany
    {
        return $this->hasMany(PointEvent::class);
    }

    public function milestones(): HasMany
    {
        return $this->hasMany(PlayerMapMilestone::class);
    }

    public function records(): HasMany
    {
        return $this->hasMany(SeasonMapPlayerRecord::class);
    }
}
