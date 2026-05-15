<?php

namespace App\Console\Commands;

use App\Models\Season;
use App\Services\Scoring\SeasonScoringService;
use Illuminate\Console\Command;

class SeasonFinalizeCommand extends Command
{
    protected $signature = 'season:finalize {season? : The season ID or slug to finalize}';

    protected $description = 'Award positional points for a season based on final leaderboard standings';

    public function handle(SeasonScoringService $scoringService): int
    {
        $seasonId = $this->argument('season');

        if ($seasonId !== null) {
            $season = is_numeric($seasonId)
                ? Season::query()->findOrFail((int) $seasonId)
                : Season::query()->where('slug', $seasonId)->firstOrFail();
        } else {
            $season = Season::query()->where('is_active', false)->latest('id')->first();

            if (! $season) {
                $this->error('No inactive season found. Specify a season ID or slug.');

                return 1;
            }
        }

        $this->info("Finalizing season [{$season->name}] ({$season->slug})..");

        $scoringService->finalizeSeason($season);

        $this->info('Season finalized. Positional points awarded.');

        return 0;
    }
}
