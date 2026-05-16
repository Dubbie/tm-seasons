<?php

namespace App\Domains\Seasons\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Domains\Seasons\Http\Resources\LeaderboardPollResource;
use App\Domains\Seasons\Models\LeaderboardPoll;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AdminLeaderboardPollController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return LeaderboardPollResource::collection(
            LeaderboardPoll::query()
                ->with('season')
                ->latest('id')
                ->paginate(25),
        );
    }

    public function show(LeaderboardPoll $poll): LeaderboardPollResource
    {
        $poll->load(['season', 'snapshots']);

        return new LeaderboardPollResource($poll);
    }
}
