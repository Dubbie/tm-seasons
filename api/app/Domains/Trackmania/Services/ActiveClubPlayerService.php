<?php

namespace App\Domains\Trackmania\Services;

use App\Domains\Trackmania\Models\ClubMember;
use App\Domains\Trackmania\Models\TrackmaniaClub;
use Illuminate\Support\Collection;

class ActiveClubPlayerService
{
    public function getActivePlayerIdsForPrimaryClub(): ?Collection
    {
        $primary = TrackmaniaClub::primary();

        if ($primary === null) {
            return null;
        }

        return ClubMember::query()
            ->where('trackmania_club_id', $primary->id)
            ->where('is_active', true)
            ->pluck('trackmania_player_id');
    }
}
