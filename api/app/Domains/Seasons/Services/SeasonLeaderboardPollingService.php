<?php

namespace App\Domains\Seasons\Services;

use App\Domains\Seasons\Models\LeaderboardPoll;
use App\Domains\Seasons\Models\Season;
use App\Domains\Trackmania\Models\ClubMember;
use App\Domains\Trackmania\Models\TrackmaniaClub;
use App\Domains\Trackmania\Models\TrackmaniaPlayer;
use App\Domains\Trackmania\Services\TrackmaniaClient;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

class SeasonLeaderboardPollingService
{
    private const ENTRIES_PER_PAGE = 100;

    private const MAX_ENTRIES = 1000;

    public function __construct(
        private readonly TrackmaniaClient $trackmaniaClient,
        private readonly SeasonScoringService $scoringService,
        private readonly ?SeasonPollingPersistenceService $pollingPersistenceService = null,
    ) {}

    public function pollSeason(Season $season): array
    {
        if (! $season->canPoll()) {
            return [
                'maps_processed' => 0,
                'snapshots_created' => 0,
                'records_updated' => 0,
                'map_errors' => ['Season is not active.'],
                'total_maps' => 0,
            ];
        }

        $pollingPersistenceService = $this->pollingPersistenceService ?? app(SeasonPollingPersistenceService::class);
        $poll = $pollingPersistenceService->startPoll($season);

        try {
            $result = $this->executePoll($season, $poll);

            $pollingPersistenceService->completePoll($poll, $result);

            return $result;
        } catch (\Throwable $exception) {
            $pollingPersistenceService->failPoll($poll, $exception);

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
                'records_updated' => 0,
                'map_errors' => [],
                'total_maps' => $maps->count(),
            ];
        }

        $mapsProcessed = 0;
        $snapshotsCreated = 0;
        $recordsUpdated = 0;
        $mapErrors = [];

        foreach ($maps as $map) {
            try {
                $entries = $this->fetchMapLeaderboardForClub($map->uid, $activePlayers);

                $clubRankedEntries = $this->computeClubPositions($entries);

                foreach ($clubRankedEntries as $rankedEntry) {
                    $player = $activePlayers[$rankedEntry['account_id']];

                    ($this->pollingPersistenceService ?? app(SeasonPollingPersistenceService::class))
                        ->createSnapshot($poll, $season, $map, $player, $rankedEntry);

                    $snapshotsCreated++;

                    $updated = ($this->pollingPersistenceService ?? app(SeasonPollingPersistenceService::class))
                        ->updatePlayerRecordAndScore(
                            $season,
                            $map,
                            $player,
                            $rankedEntry,
                            $this->scoringService,
                        );

                    if ($updated) {
                        $recordsUpdated++;
                    }
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

        ($this->pollingPersistenceService ?? app(SeasonPollingPersistenceService::class))
            ->attachMapErrors($poll, $mapErrors);

        return [
            'maps_processed' => $mapsProcessed,
            'snapshots_created' => $snapshotsCreated,
            'records_updated' => $recordsUpdated,
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

    private function computeClubPositions(array $entries): array
    {
        $sorted = collect($entries)->sortBy('score')->values();

        return $sorted->map(function (array $entry, int $index): array {
            $entry['club_position'] = $index + 1;

            return $entry;
        })->toArray();
    }
}
