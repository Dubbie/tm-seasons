<?php

namespace App\Domains\Seasons\Http\Controllers\Public;

use App\Domains\Seasons\Http\Resources\SeasonResource;
use App\Domains\Seasons\Models\Season;
use App\Domains\Seasons\Models\SeasonStatus;
use App\Http\Controllers\Controller;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

#[Group('Seasons - Public', description: 'Public season discovery, leaderboards, standings, and activity endpoints.', weight: 40)]
class PublicSeasonController extends Controller
{
    /**
     * List public seasons.
     *
     * Returns scheduled, active, ended, and finalized seasons ordered by start date.
     *
     * @unauthenticated
     *
     * @response AnonymousResourceCollection<SeasonResource>
     */
    public function index(): AnonymousResourceCollection
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

    /**
     * Get a public season.
     *
     * Returns a visible season by slug with its maps ordered for season play.
     *
     * @unauthenticated
     */
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
