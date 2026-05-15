<?php

namespace App\Services\Trackmania;

use App\Models\ClubMember;
use App\Models\TrackmaniaClub;
use App\Models\TrackmaniaPlayer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TrackmaniaClubSyncService
{
    public function __construct(
        private readonly TrackmaniaClient $trackmaniaClient,
        private readonly TrackmaniaIoClient $trackmaniaIoClient,
    ) {
    }

    public function syncClub(int|string $clubId): array
    {
        $clubData = $this->trackmaniaClient->getClub($clubId);
        $members = $this->trackmaniaClient->getClubMembers($clubId);

        $summary = DB::transaction(function () use ($clubData, $members): array {
            $club = TrackmaniaClub::query()->firstOrNew([
                'club_id' => (string) $clubData['club_id'],
            ]);

            $club->fill([
                'name' => $clubData['name'] ?: sprintf('Club %s', $clubData['club_id']),
                'tag' => $clubData['tag'],
                'description' => $clubData['description'],
                'member_count' => $clubData['member_count'],
                'icon_url' => $clubData['icon_url'],
            ]);

            if (! $club->exists) {
                $club->is_primary = TrackmaniaClub::primary() === null;
            }

            $club->save();

            $memberSummary = $this->syncClubMembersSnapshot($club, $members);

            $club->forceFill([
                'member_count' => $memberSummary['total_members'],
                'last_synced_at' => now(),
            ])->save();

            return [
                'club' => $club->fresh(),
                ...$memberSummary,
            ];
        });

        $enriched = $this->enrichPlayersDisplayNames($summary['account_ids_to_enrich']);
        unset($summary['account_ids_to_enrich']);
        $summary['enriched'] = $enriched;

        return $summary;
    }

    public function syncClubMembers(int|string $clubId): array
    {
        $club = TrackmaniaClub::query()->where('club_id', (string) $clubId)->firstOrFail();
        $members = $this->trackmaniaClient->getClubMembers($clubId);

        $summary = DB::transaction(function () use ($club, $members): array {
            return $this->syncClubMembersSnapshot($club, $members);
        });

        $enriched = $this->enrichPlayersDisplayNames($summary['account_ids_to_enrich']);
        unset($summary['account_ids_to_enrich']);
        $summary['enriched'] = $enriched;

        return $summary;
    }

    private function syncClubMembersSnapshot(TrackmaniaClub $club, array $members): array
    {
        $now = now();
        $imported = 0;
        $updated = 0;
        $syncedAccountIds = [];
        $accountIdsToEnrich = [];

        foreach ($members as $member) {
            $player = TrackmaniaPlayer::query()->firstOrNew([
                'account_id' => $member['account_id'],
            ]);

            $wasExistingPlayer = $player->exists;
            $incomingDisplayName = (string) $member['display_name'];
            $existingDisplayName = (string) ($player->display_name ?? '');
            $existingIsResolved = $existingDisplayName !== '' && $existingDisplayName !== $player->account_id;
            $incomingIsResolved = $incomingDisplayName !== '' && $incomingDisplayName !== $player->account_id;
            $incomingZoneId = is_string($member['zone_id']) && $member['zone_id'] !== '' ? $member['zone_id'] : null;
            $incomingZoneName = is_string($member['zone_name']) && $member['zone_name'] !== '' ? $member['zone_name'] : null;

            $displayNameToPersist = $incomingIsResolved
                ? $incomingDisplayName
                : ($existingIsResolved ? $existingDisplayName : $player->account_id);

            $player->fill([
                'display_name' => $displayNameToPersist,
                'zone_id' => $incomingZoneId ?? $player->zone_id,
                'zone_name' => $incomingZoneName ?? $player->zone_name,
                'is_active' => true,
                'last_seen_in_club_at' => $now,
            ]);
            $player->save();

            if (! $wasExistingPlayer) {
                $imported++;
            } else {
                $updated++;
            }

            if (
                $player->display_name === $player->account_id
                || $player->display_name === ''
                || $player->zone_name === null
                || $player->zone_name === ''
                || $player->zone_id === null
                || $player->zone_id === ''
            ) {
                $accountIdsToEnrich[] = $player->account_id;
            }

            $membership = ClubMember::query()->firstOrNew([
                'trackmania_club_id' => $club->id,
                'trackmania_player_id' => $player->id,
            ]);

            $membership->fill([
                'joined_at' => $member['joined_at'],
                'synced_at' => $now,
                'is_active' => true,
            ]);
            $membership->save();

            $syncedAccountIds[] = $player->account_id;
        }

        $syncedPlayerIds = TrackmaniaPlayer::query()
            ->whereIn('account_id', $syncedAccountIds)
            ->pluck('id');

        $deactivationQuery = ClubMember::query()
            ->where('trackmania_club_id', $club->id)
            ->where('is_active', true);

        if ($syncedPlayerIds->isNotEmpty()) {
            $deactivationQuery->whereNotIn('trackmania_player_id', $syncedPlayerIds);
        }

        $deactivatedMemberships = $deactivationQuery->update([
            'is_active' => false,
            'synced_at' => $now,
            'updated_at' => $now,
        ]);

        $this->deactivatePlayersWithoutActiveClubs();

        return [
            'imported' => $imported,
            'updated' => $updated,
            'deactivated' => $deactivatedMemberships,
            'total_members' => count($members),
            'account_ids_to_enrich' => array_values(array_unique($accountIdsToEnrich)),
        ];
    }

    private function enrichPlayersDisplayNames(array $accountIds): int
    {
        $enriched = 0;

        foreach ($accountIds as $accountId) {
            try {
                $tmioPlayer = $this->trackmaniaIoClient->getPlayer((string) $accountId);
            } catch (\Throwable $exception) {
                Log::warning('trackmania.io player lookup failed', [
                    'account_id' => $accountId,
                    'error' => $exception->getMessage(),
                ]);

                continue;
            }

            if (! $tmioPlayer) {
                continue;
            }

            $player = TrackmaniaPlayer::query()->where('account_id', $accountId)->first();
            if (! $player) {
                continue;
            }

            if (! empty($tmioPlayer['display_name']) && $tmioPlayer['display_name'] !== $player->account_id) {
                $player->display_name = $tmioPlayer['display_name'];
            }
            if (! $player->zone_id && ! empty($tmioPlayer['zone_id'])) {
                $player->zone_id = $tmioPlayer['zone_id'];
            }
            if (! $player->zone_name && ! empty($tmioPlayer['zone_name'])) {
                $player->zone_name = $tmioPlayer['zone_name'];
            }
            $player->save();

            if ($player->wasChanged(['display_name', 'zone_id', 'zone_name'])) {
                $enriched++;
            }
        }

        return $enriched;
    }

    private function deactivatePlayersWithoutActiveClubs(): void
    {
        $activePlayerIds = ClubMember::query()
            ->where('is_active', true)
            ->pluck('trackmania_player_id');

        TrackmaniaPlayer::query()
            ->whereNotIn('id', $activePlayerIds)
            ->where('is_active', true)
            ->update(['is_active' => false]);
    }
}
