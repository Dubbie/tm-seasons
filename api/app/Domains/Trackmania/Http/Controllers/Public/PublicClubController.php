<?php

namespace App\Domains\Trackmania\Http\Controllers\Public;

use App\Domains\Trackmania\Http\Resources\ClubMemberResource;
use App\Domains\Trackmania\Http\Resources\TrackmaniaClubResource;
use App\Domains\Trackmania\Models\ClubMember;
use App\Domains\Trackmania\Models\TrackmaniaClub;
use App\Http\Controllers\Controller;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

#[Group('Trackmania - Public', description: 'Public Trackmania club, member, and map endpoints.', weight: 20)]
class PublicClubController extends Controller
{
    /**
     * List public Trackmania clubs.
     *
     * Returns paginated clubs ordered by most recently imported first.
     *
     * @unauthenticated
     *
     * @response AnonymousResourceCollection<TrackmaniaClubResource>
     */
    public function index(): AnonymousResourceCollection
    {
        return TrackmaniaClubResource::collection(
            TrackmaniaClub::query()->latest('id')->paginate(25)
        );
    }

    /**
     * Get a public Trackmania club.
     *
     * Returns the selected club and its public metadata.
     *
     * @unauthenticated
     */
    public function show(TrackmaniaClub $club): TrackmaniaClubResource
    {
        return new TrackmaniaClubResource($club);
    }

    /**
     * List public club members.
     *
     * Returns paginated members for a club, optionally filtered by active membership.
     *
     * @unauthenticated
     *
     * @response AnonymousResourceCollection<ClubMemberResource>
     */
    public function members(Request $request, TrackmaniaClub $club): AnonymousResourceCollection
    {
        $query = ClubMember::query()
            ->where('club_members.trackmania_club_id', $club->id)
            ->with('player');

        $active = $request->query('active', '1');
        if ($active !== null && $active !== '') {
            $query->where('club_members.is_active', filter_var($active, FILTER_VALIDATE_BOOLEAN));
        }

        $query->join('trackmania_players', 'trackmania_players.id', '=', 'club_members.trackmania_player_id')
            ->orderBy('trackmania_players.display_name')
            ->select('club_members.*');

        return ClubMemberResource::collection($query->paginate(25));
    }
}
