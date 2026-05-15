<?php

namespace App\Services\Trackmania;

use App\Models\ClubMember;
use App\Models\LeaderboardPoll;
use App\Models\LeaderboardSnapshot;
use App\Models\Map;
use App\Models\Season;
use App\Models\SeasonMapPlayerRecord;
use App\Models\TrackmaniaClub;
use App\Models\TrackmaniaPlayer;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

class SeasonLeaderboardPollingService
{
    private const ENTRIES_PER_PAGE = 100;

    private const MAX_ENTRIES = 1000;

    public function __construct(
        private readonly TrackmaniaClient $trackmaniaClient,
    ) {}

    public function pollSeason(Season $season): array
    {
        $poll = LeaderboardPoll::query()->create([
            'season_id' => $season->id,
            'status' => 'running',
            'started_at' => now(),
        ]);

        try {
            $result = $this->executePoll($season, $poll);

            $poll->update([
                'status' => 'completed',
                'finished_at' => now(),
                'maps_polled_count' => $result['maps_processed'],
                'players_processed_count' => $result['snapshots_created'],
            ]);

            return $result;
        } catch (\Throwable $exception) {
            $poll->update([
                'status' => 'failed',
                'finished_at' => now(),
                'error_message' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }

    private function executePoll(Season $season, LeaderboardPoll $poll): array
    {
        $activePlayers = $this->getActiveClubPlayers();

        $maps = $season->maps()
            ->wherePivot('is_active', true)
            ->orderBy('season_maps.order_index')
            ->orderBy('season_maps.id')
            ->get();

        if ($activePlayers->isEmpty() || $maps->isEmpty()) {
            return [
                'maps_processed' => 0,
                'snapshots_created' => 0,
                'improvements_detected' => 0,
                'map_errors' => [],
                'total_maps' => $maps->count(),
            ];
        }

        $mapsProcessed = 0;
        $snapshotsCreated = 0;
        $improvementsDetected = 0;
        $mapErrors = [];

        foreach ($maps as $map) {
            try {
                $entries = $this->fetchMapLeaderboardForClub($map->uid, $activePlayers);

                foreach ($entries as $entry) {
                    $player = $activePlayers[$entry['account_id']];

                    LeaderboardSnapshot::query()->create([
                        'leaderboard_poll_id' => $poll->id,
                        'season_id' => $season->id,
                        'map_id' => $map->id,
                        'trackmania_player_id' => $player->id,
                        'position' => $entry['position'],
                        'time_ms' => $entry['score'],
                        'zone_name' => $entry['zone_name'],
                        'recorded_at' => now(),
                    ]);

                    $snapshotsCreated++;

                    $this->updatePlayerRecord($season, $map, $player, $entry, $improvementsDetected);
                }

                $mapsProcessed++;
            } catch (\Throwable $exception) {
                $mapErrors[] = sprintf('Map [%s] (%s): %s', $map->uid, $map->name, $exception->getMessage());

                Log::warning('SeasonLeaderboardPollingService: map polling failed', [
                    'season_id' => $season->id,
                    'map_uid' => $map->uid,
                    'poll_id' => $poll->id,
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        $errorMessage = $mapErrors !== [] ? implode(' | ', $mapErrors) : null;

        if ($errorMessage !== null) {
            $poll->update(['error_message' => $errorMessage]);
        }

        return [
            'maps_processed' => $mapsProcessed,
            'snapshots_created' => $snapshotsCreated,
            'improvements_detected' => $improvementsDetected,
            'map_errors' => $mapErrors,
            'total_maps' => $maps->count(),
        ];
    }

    private function getActiveClubPlayers(): Collection
    {
        $club = TrackmaniaClub::primary();

        if (! $club) {
            return new Collection;
        }

        $activeMemberPlayerIds = ClubMember::query()
            ->where('trackmania_club_id', $club->id)
            ->where('is_active', true)
            ->pluck('trackmania_player_id');

        return TrackmaniaPlayer::query()
            ->whereIn('id', $activeMemberPlayerIds)
            ->where('is_active', true)
            ->get()
            ->keyBy('account_id');
    }

    private function fetchMapLeaderboardForClub(string $mapUid, Collection $activePlayers): array
    {
        $matchedEntries = [];
        $offset = 0;

        while ($offset < self::MAX_ENTRIES) {
            $leaderboard = $this->trackmaniaClient->getMapLeaderboard(
                $mapUid,
                self::ENTRIES_PER_PAGE,
                $offset,
            );

            if ($leaderboard->entries === []) {
                break;
            }

            foreach ($leaderboard->entries as $entry) {
                if ($entry->accountId === null) {
                    continue;
                }

                if (! isset($activePlayers[$entry->accountId])) {
                    continue;
                }

                $matchedEntries[] = [
                    'account_id' => $entry->accountId,
                    'position' => $entry->position ?? 0,
                    'score' => $entry->score,
                    'zone_name' => $entry->zoneName,
                ];
            }

            if (count($leaderboard->entries) < self::ENTRIES_PER_PAGE) {
                break;
            }

            $offset += self::ENTRIES_PER_PAGE;
        }

        return $matchedEntries;
    }

    private function updatePlayerRecord(
        Season $season,
        Map $map,
        TrackmaniaPlayer $player,
        array $entry,
        int &$improvementsDetected,
    ): void {
        $record = SeasonMapPlayerRecord::query()->firstOrNew([
            'season_id' => $season->id,
            'map_id' => $map->id,
            'trackmania_player_id' => $player->id,
        ]);

        $now = now();

        if (! $record->exists) {
            $record->fill([
                'global_position' => $entry['position'],
                'time_ms' => $entry['score'],
                'first_seen_at' => $now,
                'last_seen_at' => $now,
                'last_improved_at' => $now,
                'total_improvements' => 0,
            ]);
            $record->save();

            return;
        }

        $record->global_position = $entry['position'];
        $record->last_seen_at = $now;

        if ($entry['score'] > 0 && $entry['score'] < ($record->time_ms ?? PHP_INT_MAX)) {
            $record->time_ms = $entry['score'];
            $record->last_improved_at = $now;
            $record->total_improvements++;
            $improvementsDetected++;
        }

        if ($entry['score'] > ($record->time_ms ?? 0)) {
            Log::warning('Worse time detected in leaderboard poll', [
                'season_id' => $season->id,
                'map_id' => $map->id,
                'player_id' => $player->id,
                'existing_time_ms' => $record->time_ms,
                'incoming_time_ms' => $entry['score'],
            ]);
        }

        $record->save();
    }
}
