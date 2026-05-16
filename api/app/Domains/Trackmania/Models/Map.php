<?php

namespace App\Domains\Trackmania\Models;

use App\Domains\Seasons\Models\Season;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Map extends Model
{
    use HasFactory;

    protected $fillable = [
        'uid',
        'nadeo_map_id',
        'name',
        'author_account_id',
        'author_name',
        'author_time',
        'gold_time',
        'silver_time',
        'bronze_time',
        'map_type',
        'map_style',
        'thumbnail_url',
        'collection_name',
        'uploaded_at',
        'updated_at_source',
    ];

    protected function casts(): array
    {
        return [
            'uploaded_at' => 'datetime',
            'updated_at_source' => 'datetime',
        ];
    }

    public function seasons(): BelongsToMany
    {
        return $this->belongsToMany(Season::class, 'season_maps')
            ->withPivot(['id', 'order_index', 'is_active'])
            ->withTimestamps();
    }
}
