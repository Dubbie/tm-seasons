<?php

namespace App\Domains\Trackmania\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Domains\Trackmania\Http\Resources\ClubMemberResource;
use App\Domains\Trackmania\Models\ClubMember;
use App\Domains\Trackmania\Models\TrackmaniaClub;
use Illuminate\Http\Request;

class AdminClubMemberController extends Controller
{
    public function primary(Request $request)
    {
        $club = TrackmaniaClub::primary();

        if (! $club) {
            $club = TrackmaniaClub::query()->oldest('id')->first();

            if ($club) {
                $club->is_primary = true;
                $club->save();
            }
        }

        if (! $club) {
            return response()->json([
                'message' => 'No primary club configured yet.',
            ], 404);
        }

        return $this->index($request, $club);
    }

    public function index(Request $request, TrackmaniaClub $club)
    {
        $query = ClubMember::query()
            ->where('club_members.trackmania_club_id', $club->id)
            ->with('player');

        $search = trim((string) $request->query('search', ''));
        if ($search !== '') {
            $query->whereHas('player', function ($playerQuery) use ($search): void {
                $playerQuery->where('display_name', 'like', sprintf('%%%s%%', $search));
            });
        }

        $active = $request->query('active');
        if ($active !== null && $active !== '') {
            $query->where('club_members.is_active', filter_var($active, FILTER_VALIDATE_BOOLEAN));
        }

        $sort = (string) $request->query('sort', 'display_name');
        $direction = strtolower((string) $request->query('direction', 'asc')) === 'desc' ? 'desc' : 'asc';

        if ($sort === 'last_seen') {
            $query->join('trackmania_players', 'trackmania_players.id', '=', 'club_members.trackmania_player_id')
                ->orderBy('trackmania_players.last_seen_in_club_at', $direction)
                ->select('club_members.*');
        } else {
            $query->join('trackmania_players', 'trackmania_players.id', '=', 'club_members.trackmania_player_id')
                ->orderBy('trackmania_players.display_name', $direction)
                ->select('club_members.*');
        }

        return ClubMemberResource::collection($query->paginate(25));
    }
}
