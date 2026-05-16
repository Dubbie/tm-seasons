<?php

namespace App\Domains\Seasons\Services;

use App\Domains\Seasons\Models\LeaderboardPoll;
use App\Domains\Seasons\Models\LeaderboardSnapshot;
use App\Domains\Trackmania\Models\Map;
use App\Domains\Seasons\Models\Season;
use App\Domains\Seasons\Models\SeasonMapPlayerRecord;
use App\Domains\Trackmania\Models\TrackmaniaPlayer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SeasonPollingPersistenceService
{
    public function startPoll(Season $season): LeaderboardPoll
    {
        return LeaderboardPoll::query()->create([
            'season_id' => $season->id,
            'status' => 'running',
            'started_at' => now(),
        ]);
    }

    public function completePoll(LeaderboardPoll $poll, array $result): void
    {
        $poll->update([
            'status' => 'completed',
            'finished_at' => now(),
            'maps_polled_count' => $result['maps_processed'],
            'players_processed_count' => $result['snapshots_created'],
        ]);
    }

    public function failPoll(LeaderboardPoll $poll, \Throwable $exception): void
    {
        $poll->update([
            'status' => 'failed',
            'finished_at' => now(),
            'error_message' => $exception->getMessage(),
        ]);
    }

    public function attachMapErrors(LeaderboardPoll $poll, array $mapErrors): void
    {
        if ($mapErrors === []) {
            return;
        }

        $poll->update(['error_message' => implode(' | ', $mapErrors)]);
    }

    public function createSnapshot(
        LeaderboardPoll $poll,
        Season $season,
        Map $map,
        TrackmaniaPlayer $player,
        array $rankedEntry,
    ): void {
        LeaderboardSnapshot::query()->create([
            'leaderboard_poll_id' => $poll->id,
            'season_id' => $season->id,
            'map_id' => $map->id,
            'trackmania_player_id' => $player->id,
            'global_position' => $rankedEntry['position'],
            'current_position' => $rankedEntry['club_position'],
            'time_ms' => $rankedEntry['score'],
            'zone_name' => $rankedEntry['zone_name'],
            'recorded_at' => now(),
        ]);
    }

    public function updatePlayerRecordAndScore(
        Season $season,
        Map $map,
        TrackmaniaPlayer $player,
        array $entry,
        SeasonScoringService $scoringService,
    ): bool {
        return DB::transaction(function () use ($season, $map, $player, $entry, $scoringService): bool {
            $record = SeasonMapPlayerRecord::query()->firstOrNew([
                'season_id' => $season->id,
                'map_id' => $map->id,
                'trackmania_player_id' => $player->id,
            ]);

            $now = now();

            if (! $record->exists) {
                $record->fill([
                    'global_position' => $entry['position'],
                    'current_position' => $entry['club_position'],
                    'time_ms' => $entry['score'],
                    'baseline_time_ms' => $entry['score'],
                    'first_seen_at' => $now,
                    'last_seen_at' => $now,
                    'last_improved_at' => $now,
                    'total_improvements' => 0,
                ]);
                $record->save();

                $scoringService->evaluateNewRecord(
                    season: $season,
                    map: $map,
                    player: $player,
                    timeMs: $entry['score'],
                    currentPosition: $entry['club_position'],
                    isNew: true,
                );

                return true;
            }

            $timeChanged = $entry['score'] > 0 && $entry['score'] < ($record->time_ms ?? PHP_INT_MAX);
            $positionChanged = $entry['club_position'] !== $record->current_position;

            $record->global_position = $entry['position'];
            $record->current_position = $entry['club_position'];
            $record->last_seen_at = $now;

            if ($timeChanged) {
                $record->time_ms = $entry['score'];
                $record->last_improved_at = $now;
                $record->total_improvements++;
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

            if (! $timeChanged && ! $positionChanged) {
                return false;
            }

            $scoringService->evaluateNewRecord(
                season: $season,
                map: $map,
                player: $player,
                timeMs: $record->time_ms ?? $entry['score'],
                currentPosition: $entry['club_position'],
                isNew: false,
            );

            return true;
        });
    }
}
