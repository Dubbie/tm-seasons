<?php

namespace App\Domains\Seasons\Http\Controllers\Admin;

use App\Domains\Seasons\Http\Resources\LeaderboardPollResource;
use App\Domains\Seasons\Models\LeaderboardPoll;
use App\Http\Controllers\Controller;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

#[Group('Seasons - Admin', description: 'Admin-only season lifecycle, map assignment, polling, scoring, and finalization endpoints.', weight: 50)]
class AdminLeaderboardPollController extends Controller
{
    /**
     * List leaderboard polls.
     *
     * Returns paginated poll runs with their seasons.
     *
     * @response AnonymousResourceCollection<LeaderboardPollResource>
     */
    public function index(): AnonymousResourceCollection
    {
        return LeaderboardPollResource::collection(
            LeaderboardPoll::query()
                ->with('season')
                ->latest('id')
                ->paginate(25),
        );
    }

    /**
     * Get a leaderboard poll.
     *
     * Returns a poll run with its season and captured snapshots.
     */
    public function show(LeaderboardPoll $poll): LeaderboardPollResource
    {
        $poll->load(['season', 'snapshots']);

        return new LeaderboardPollResource($poll);
    }
}
