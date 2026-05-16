<?php

namespace App\Console\Commands;

use App\Domains\Seasons\Models\Season;
use App\Domains\Seasons\Models\SeasonStatus;
use App\Domains\Seasons\Services\SeasonLifecycleService;
use Illuminate\Console\Command;

class SeasonFinalizeCommand extends Command
{
    protected $signature = 'season:finalize {season? : The season ID or slug to finalize}';

    protected $description = 'Award positional points for a season based on final leaderboard standings';

    public function handle(SeasonLifecycleService $lifecycleService): int
    {
        $seasonId = $this->argument('season');

        if ($seasonId !== null) {
            $season = is_numeric($seasonId)
                ? Season::query()->findOrFail((int) $seasonId)
                : Season::query()->where('slug', $seasonId)->firstOrFail();
        } else {
            $season = Season::query()->where('status', SeasonStatus::Ended)->latest('id')->first();

            if (! $season) {
                $this->error('No inactive season found. Specify a season ID or slug.');

                return 1;
            }
        }

        $this->info("Finalizing season [{$season->name}] ({$season->slug})..");

        if ($season->created_by_user_id === null) {
            $this->error('Season has no creator assigned; use admin API finalization instead.');

            return 1;
        }

        $lifecycleService->finalizeSeason($season, (int) $season->created_by_user_id);

        $this->info('Season finalized. Positional points awarded.');

        return 0;
    }
}
