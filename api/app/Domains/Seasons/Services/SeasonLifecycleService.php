<?php

namespace App\Domains\Seasons\Services;

use App\Domains\Activity\Services\SeasonPointEventWriteService;
use App\Domains\Seasons\Models\Season;
use App\Domains\Seasons\Models\SeasonMapPlayerRecord;
use App\Domains\Seasons\Models\SeasonStatus;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SeasonLifecycleService
{
    public function __construct(
        private readonly SeasonPointEventWriteService $pointEventWriteService,
    ) {}

    public function activateSeason(Season $season): Season
    {
        $this->assertTransitionAllowed($season, SeasonStatus::Active);

        $season->status = SeasonStatus::Active;
        $season->save();

        return $season->refresh();
    }

    public function endSeason(Season $season): Season
    {
        $this->assertTransitionAllowed($season, SeasonStatus::Ended);

        $season->status = SeasonStatus::Ended;
        $season->save();

        return $season->refresh();
    }

    public function finalizeSeason(Season $season, int $finalizedByUserId): array
    {
        if ($season->status !== SeasonStatus::Ended) {
            throw new \RuntimeException('Only ended seasons can be finalized.');
        }

        $now = CarbonImmutable::now();

        return DB::transaction(function () use ($season, $finalizedByUserId, $now): array {
            $rows = SeasonMapPlayerRecord::query()
                ->where('season_id', $season->id)
                ->whereNotNull('current_position')
                ->whereNotNull('time_ms')
                ->with('player:id')
                ->get();

            $bestByPlayer = $rows
                ->groupBy('trackmania_player_id')
                ->map(function (Collection $records): ?SeasonMapPlayerRecord {
                    return $records->sortBy('current_position')->first();
                })
                ->filter();

            $standings = $bestByPlayer
                ->sortBy('current_position')
                ->values();

            $rewards = config('seasons.scoring.final_position_rewards', []);
            ksort($rewards);

            $granted = [];

            foreach ($standings as $record) {
                $position = (int) $record->current_position;
                $playerId = (int) $record->trackmania_player_id;
                $reward = $this->resolveFinalReward($position, $rewards);

                if ($reward === null) {
                    continue;
                }

                [$threshold, $points] = $reward;
                $type = 'final_top_' . $threshold;

                $exists = $this->pointEventWriteService->eventExistsForPlayer($season, $playerId, $type);

                if ($exists) {
                    continue;
                }

                $this->pointEventWriteService->createSeasonRewardEvent(
                    season: $season,
                    playerId: $playerId,
                    type: $type,
                    points: (int) $points,
                    description: sprintf('Final placement reward (Top %d)', $threshold),
                    metadata: [
                        'final_position' => $position,
                        'threshold' => (int) $threshold,
                    ],
                );

                $granted[] = [
                    'player_id' => $playerId,
                    'position' => $position,
                    'type' => $type,
                    'points' => (int) $points,
                ];
            }

            $season->status = SeasonStatus::Finalized;
            $season->finalized_at = $now;
            $season->finalized_by_user_id = $finalizedByUserId;
            $season->save();

            return [
                'rewards_granted' => $granted,
                'players_processed' => $standings->count(),
                'final_standings' => $standings->map(fn (SeasonMapPlayerRecord $record): array => [
                    'player_id' => (int) $record->trackmania_player_id,
                    'current_position' => (int) $record->current_position,
                    'time_ms' => (int) $record->time_ms,
                ])->toArray(),
            ];
        });
    }

    public function updateAutomaticStatuses(): array
    {
        $now = CarbonImmutable::now();

        $activated = Season::query()
            ->where('status', SeasonStatus::Scheduled)
            ->whereNotNull('starts_at')
            ->where('starts_at', '<=', $now)
            ->get()
            ->map(function (Season $season): int {
                $this->activateSeason($season);

                return $season->id;
            })
            ->toArray();

        $ended = Season::query()
            ->where('status', SeasonStatus::Active)
            ->whereNotNull('ends_at')
            ->where('ends_at', '<=', $now)
            ->get()
            ->map(function (Season $season): int {
                $this->endSeason($season);

                return $season->id;
            })
            ->toArray();

        return [
            'activated' => $activated,
            'ended' => $ended,
        ];
    }

    public function assertTransitionAllowed(Season $season, SeasonStatus $target): void
    {
        $current = $season->status;

        if (in_array($target, [SeasonStatus::Scheduled, SeasonStatus::Active], true)) {
            if ($season->starts_at === null || $season->ends_at === null) {
                throw new \RuntimeException('starts_at and ends_at are required for scheduled/active seasons.');
            }

            if ($season->ends_at->lte($season->starts_at)) {
                throw new \RuntimeException('ends_at must be after starts_at.');
            }
        }

        if ($current === $target) {
            return;
        }

        if ($current === SeasonStatus::Finalized) {
            throw new \RuntimeException('Finalized seasons cannot transition.');
        }

        if ($current === SeasonStatus::Ended && $target === SeasonStatus::Active) {
            throw new \RuntimeException('Ended seasons cannot transition back to active.');
        }
    }

    private function resolveFinalReward(int $position, array $rewards): ?array
    {
        foreach ($rewards as $threshold => $points) {
            if ($position <= (int) $threshold) {
                return [(int) $threshold, (int) $points];
            }
        }

        return null;
    }
}
