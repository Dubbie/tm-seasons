<?php

namespace App\Services\Scoring;

use App\Models\ClubMember;
use App\Models\Map;
use App\Models\PlayerMapMilestone;
use App\Models\PointEvent;
use App\Models\Season;
use App\Models\SeasonMapPlayerRecord;
use App\Models\SeasonStatus;
use App\Models\TrackmaniaClub;
use App\Models\TrackmaniaPlayer;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SeasonScoringService
{
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

        $points = config('season_scoring.first_finish', 10);

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

        $medalRewards = config('season_scoring.medal_rewards', []);

        $orderedMedals = ['bronze', 'silver', 'gold', 'author'];

        foreach ($orderedMedals as $medal) {
            $threshold = $medalThresholds[$medal] ?? null;

            if ($threshold === null) {
                continue;
            }

            if ($timeMs > $threshold) {
                continue;
            }

            $milestoneKey = 'medal_' . $medal;

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
                description: 'Earned ' . $medalLabel . ' medal',
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
        if ($createdAt !== null) {
            DB::table('point_events')->insert([
                'season_id' => $season->id,
                'map_id' => $map->id,
                'trackmania_player_id' => $player->id,
                'type' => $type,
                'points' => $points,
                'description' => $description,
                'metadata' => $metadata !== null ? json_encode($metadata) : null,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);

            return;
        }

        PointEvent::query()->create([
            'season_id' => $season->id,
            'map_id' => $map->id,
            'trackmania_player_id' => $player->id,
            'type' => $type,
            'points' => $points,
            'description' => $description,
            'metadata' => $metadata,
        ]);
    }

    public function recalculate(Season $season): void
    {
        $primary = TrackmaniaClub::primary();

        $activePlayerIds = $primary !== null
            ? ClubMember::query()
                ->where('trackmania_club_id', $primary->id)
                ->where('is_active', true)
                ->pluck('trackmania_player_id')
            : null;

        DB::transaction(function () use ($season, $activePlayerIds): void {
            PointEvent::query()->where('season_id', $season->id)->delete();
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
