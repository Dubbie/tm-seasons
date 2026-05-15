<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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
        'created_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_active' => 'boolean',
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

        static::saving(function (self $season): void {
            if ($season->is_active) {
                static::query()
                    ->whereKeyNot($season->getKey())
                    ->where('is_active', true)
                    ->update(['is_active' => false]);
            }
        });
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
}
