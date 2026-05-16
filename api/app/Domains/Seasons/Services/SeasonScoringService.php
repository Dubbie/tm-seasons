<?php

namespace App\Domains\Seasons\Services;

use App\Domains\Activity\Services\SeasonPointEventWriteService;
use App\Domains\Seasons\Models\PlayerMapMilestone;
use App\Domains\Seasons\Models\Season;
use App\Domains\Seasons\Models\SeasonMapPlayerRecord;
use App\Domains\Seasons\Models\SeasonStatus;
use App\Domains\Trackmania\Models\Map;
use App\Domains\Trackmania\Models\TrackmaniaPlayer;
use App\Domains\Trackmania\Services\ActiveClubPlayerService;
use Illuminate\Support\Facades\DB;

class SeasonScoringService
{
    public function __construct(
        private readonly ActiveClubPlayerService $activeClubPlayerService,
        private readonly SeasonPointEventWriteService $pointEventWriteService,
    ) {}

    public function evaluateNewRecord(
        Season $season,
        Map $map,
        TrackmaniaPlayer $player,
        int $timeMs,
        int $currentPosition,
        bool $isNew,
        ?\DateTimeInterface $createdAt = null,
    ): void {
        if ($season->status !== SeasonStatus::Active) {
            return;
        }

        $existingMilestones = PlayerMapMilestone::query()
            ->where('season_id', $season->id)
            ->where('map_id', $map->id)
            ->where('trackmania_player_id', $player->id)
            ->pluck('milestone_key')
            ->toArray();

        DB::transaction(function () use ($season, $map, $player, $timeMs, $currentPosition, $isNew, $createdAt, $existingMilestones): void {
            if ($isNew) {
                $this->awardFirstFinish($season, $map, $player, $timeMs, $currentPosition, $existingMilestones, $createdAt);
            }

            $this->evaluateMedalRewards($season, $map, $player, $timeMs, $currentPosition, $existingMilestones, $createdAt);
        });
    }

    private function awardFirstFinish(
        Season $season,
        Map $map,
        TrackmaniaPlayer $player,
        int $timeMs,
        int $currentPosition,
        array $existingMilestones,
        ?\DateTimeInterface $createdAt = null,
    ): void {
        $milestoneKey = 'first_finish';

        if (in_array($milestoneKey, $existingMilestones, true)) {
            return;
        }

        $points = config('seasons.scoring.first_finish', 10);

        $this->createPointEvent(
            season: $season,
            map: $map,
            player: $player,
            type: 'first_finish',
            points: $points,
            description: 'Finished map',
            metadata: [
                'time_ms' => $timeMs,
                'club_position' => $currentPosition,
            ],
            createdAt: $createdAt,
        );

        $this->markMilestone($season, $map, $player, $milestoneKey, $createdAt);
    }

    private function evaluateMedalRewards(
        Season $season,
        Map $map,
        TrackmaniaPlayer $player,
        int $timeMs,
        int $currentPosition,
        array $existingMilestones,
        ?\DateTimeInterface $createdAt = null,
    ): void {
        $medalThresholds = [
            'author' => $map->author_time,
            'gold' => $map->gold_time,
            'silver' => $map->silver_time,
            'bronze' => $map->bronze_time,
        ];

        $medalRewards = config('seasons.scoring.medal_rewards', []);

        $orderedMedals = ['bronze', 'silver', 'gold', 'author'];

        foreach ($orderedMedals as $medal) {
            $threshold = $medalThresholds[$medal] ?? null;

            if ($threshold === null) {
                continue;
            }

            if ($timeMs > $threshold) {
                continue;
            }

            $milestoneKey = 'medal_'.$medal;

            if (in_array($milestoneKey, $existingMilestones, true)) {
                continue;
            }

            $points = $medalRewards[$medal] ?? 0;

            $medalLabel = ucfirst($medal);

            $this->createPointEvent(
                season: $season,
                map: $map,
                player: $player,
                type: $milestoneKey,
                points: $points,
                description: 'Earned '.$medalLabel.' medal',
                metadata: [
                    'time_ms' => $timeMs,
                    'medal' => $medal,
                    'threshold_ms' => $threshold,
                    'club_position' => $currentPosition,
                ],
                createdAt: $createdAt,
            );

            $this->markMilestone($season, $map, $player, $milestoneKey, $createdAt);
        }
    }

    public function hasMilestone(Season $season, Map $map, TrackmaniaPlayer $player, string $milestoneKey): bool
    {
        return PlayerMapMilestone::query()
            ->where('season_id', $season->id)
            ->where('map_id', $map->id)
            ->where('trackmania_player_id', $player->id)
            ->where('milestone_key', $milestoneKey)
            ->exists();
    }

    public function markMilestone(Season $season, Map $map, TrackmaniaPlayer $player, string $milestoneKey, ?\DateTimeInterface $createdAt = null): void
    {
        $data = [
            'season_id' => $season->id,
            'map_id' => $map->id,
            'trackmania_player_id' => $player->id,
            'milestone_key' => $milestoneKey,
            'achieved_at' => $createdAt ?? now(),
        ];

        PlayerMapMilestone::query()->create($data);
    }

    public function createPointEvent(
        Season $season,
        Map $map,
        TrackmaniaPlayer $player,
        string $type,
        int $points,
        ?string $description = null,
        ?array $metadata = null,
        ?\DateTimeInterface $createdAt = null,
    ): void {
        $this->pointEventWriteService->createEvent(
            season: $season,
            map: $map,
            player: $player,
            type: $type,
            points: $points,
            description: $description,
            metadata: $metadata,
            createdAt: $createdAt,
        );
    }

    public function recalculate(Season $season): void
    {
        $activePlayerIds = $this->activeClubPlayerService->getActivePlayerIdsForPrimaryClub();

        DB::transaction(function () use ($season, $activePlayerIds): void {
            $this->pointEventWriteService->clearSeasonEvents($season);
            PlayerMapMilestone::query()->where('season_id', $season->id)->delete();

            $records = SeasonMapPlayerRecord::query()
                ->where('season_id', $season->id)
                ->when($activePlayerIds !== null, fn ($q) => $q->whereIn('trackmania_player_id', $activePlayerIds))
                ->with(['map', 'player'])
                ->whereNotNull('time_ms')
                ->orderBy('map_id')
                ->orderBy('time_ms')
                ->get();

            $maps = $records->groupBy('map_id');

            foreach ($maps as $mapId => $mapRecords) {
                $map = $mapRecords->first()->map;

                if ($map === null) {
                    continue;
                }

                $clubPosition = 0;

                foreach ($mapRecords as $record) {
                    $clubPosition++;

                    $player = $record->player;

                    if ($player === null) {
                        continue;
                    }

                    $previousRecords = SeasonMapPlayerRecord::query()
                        ->where('season_id', $season->id)
                        ->where('map_id', $map->id)
                        ->where('trackmania_player_id', $player->id)
                        ->where('id', '!=', $record->id)
                        ->whereNotNull('time_ms')
                        ->exists();

                    $isNew = ! $previousRecords;
                    $timestamp = $isNew ? $record->created_at : ($record->last_improved_at ?? $record->updated_at);

                    $this->evaluateNewRecord(
                        season: $season,
                        map: $map,
                        player: $player,
                        timeMs: $record->time_ms,
                        currentPosition: $clubPosition,
                        isNew: $isNew,
                        createdAt: $timestamp,
                    );
                }
            }
        });
    }
}
