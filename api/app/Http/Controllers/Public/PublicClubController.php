<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Resources\ClubMemberResource;
use App\Http\Resources\TrackmaniaClubResource;
use App\Models\ClubMember;
use App\Models\TrackmaniaClub;
use Illuminate\Http\Request;

class PublicClubController extends Controller
{
    public function index()
    {
        return TrackmaniaClubResource::collection(
            TrackmaniaClub::query()->latest('id')->paginate(25)
        );
    }

    public function show(TrackmaniaClub $club): TrackmaniaClubResource
    {
        return new TrackmaniaClubResource($club);
    }

    public function members(Request $request, TrackmaniaClub $club)
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
