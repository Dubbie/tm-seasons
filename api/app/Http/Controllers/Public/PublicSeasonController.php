<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Resources\SeasonResource;
use App\Models\Season;
use App\Models\SeasonStatus;

class PublicSeasonController extends Controller
{
    public function index()
    {
        $seasons = Season::query()
            ->whereIn('status', [
                SeasonStatus::Scheduled,
                SeasonStatus::Active,
                SeasonStatus::Ended,
                SeasonStatus::Finalized,
            ])
            ->latest('starts_at')
            ->latest('id')
            ->get();

        return SeasonResource::collection($seasons);
    }

    public function show(string $slug): SeasonResource
    {
        $season = Season::query()
            ->where('slug', $slug)
            ->whereIn('status', [
                SeasonStatus::Scheduled,
                SeasonStatus::Active,
                SeasonStatus::Ended,
                SeasonStatus::Finalized,
            ])
            ->with([
                'maps' => fn ($query) => $query->orderBy('season_maps.order_index')->orderBy('season_maps.id'),
            ])
            ->firstOrFail();

        return new SeasonResource($season);
    }
}
