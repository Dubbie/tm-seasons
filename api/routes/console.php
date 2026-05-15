<?php

use App\Exceptions\Trackmania\TrackmaniaClientException;
use App\Exceptions\Trackmania\TrackmaniaTokenException;
use App\Models\Season;
use App\Models\User;
use App\Services\Trackmania\SeasonLeaderboardPollingService;
use App\Services\Trackmania\TrackmaniaClient;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('trackmania:test-map {mapUid}', function (string $mapUid): int {
    try {
        /** @var TrackmaniaClient $client */
        $client = app(TrackmaniaClient::class);
        $map = $client->getMapInfo($mapUid);
        $leaderboard = $client->getMapLeaderboard($mapUid, 100, 0, 'Personal_Best');
    } catch (TrackmaniaTokenException|TrackmaniaClientException $exception) {
        $this->error($exception->getMessage());

        return self::FAILURE;
    } catch (Throwable $exception) {
        $this->error('Unexpected Trackmania error: '.$exception->getMessage());

        return self::FAILURE;
    }

    $entries = $leaderboard->entries;
    $wr = $entries[0] ?? null;

    $fmt = static fn (?int $value): string => ($value === null || $value < 0) ? 'n/a' : (string) $value;

    $this->info('Trackmania map fetch complete');
    $this->line(sprintf('Map: %s (%s)', $map->name ?? 'Unknown', $map->uid));
    $this->line(sprintf('Author: %s | Gold: %s | Silver: %s | Bronze: %s', $fmt($map->authorTime), $fmt($map->goldTime), $fmt($map->silverTime), $fmt($map->bronzeTime)));
    $this->line(sprintf('Leaderboard entries: %d', count($entries)));
    $this->line(sprintf('WR accountId: %s', $wr?->accountId ?? 'n/a'));
    $this->line(sprintf('WR score: %s', $fmt($wr?->score)));
    $this->line(sprintf('WR timestamp: %s', $wr?->timestamp !== null ? (string) $wr->timestamp : 'n/a'));

    return self::SUCCESS;
})->purpose('Test Trackmania map and leaderboard API access');

Artisan::command('season:poll {seasonId}', function (string $seasonId): int {
    $season = Season::query()->find($seasonId);

    if (! $season) {
        $this->error(sprintf('Season [%s] not found.', $seasonId));

        return self::FAILURE;
    }

    try {
        /** @var SeasonLeaderboardPollingService $pollingService */
        $pollingService = app(SeasonLeaderboardPollingService::class);
        $result = $pollingService->pollSeason($season);
    } catch (Throwable $exception) {
        $this->error(sprintf('Poll failed: %s', $exception->getMessage()));

        return self::FAILURE;
    }

    $this->info('Season poll completed.');
    $this->line(sprintf('Maps processed: %d / %d', $result['maps_processed'], $result['total_maps']));
    $this->line(sprintf('Snapshots created: %d', $result['snapshots_created']));
    $this->line(sprintf('Improvements detected: %d', $result['improvements_detected']));

    if ($result['map_errors'] !== []) {
        $this->warn(sprintf('Map errors: %d', count($result['map_errors'])));

        foreach ($result['map_errors'] as $error) {
            $this->line(sprintf('  - %s', $error));
        }
    }

    return self::SUCCESS;
})->purpose('Poll leaderboard data for a season by ID');

Artisan::command('season:poll-active', function (): int {
    $season = Season::query()->where('is_active', true)->first();

    if (! $season) {
        $this->warn('No active season found.');

        return self::SUCCESS;
    }

    $this->info(sprintf('Polling active season [%s] (%s)...', $season->name, $season->slug));

    return $this->call('season:poll', ['seasonId' => (string) $season->id]);
})->purpose('Poll leaderboard data for the currently active season');

// Schedule: poll active season every 5 minutes
// Schedule::command('season:poll-active')->everyFiveMinutes();

Artisan::command('users:make-admin {discord_id}', function (string $discord_id): int {
    $user = User::query()->where('discord_id', $discord_id)->first();

    if (! $user) {
        $this->error(sprintf('User with discord_id [%s] was not found.', $discord_id));

        return self::FAILURE;
    }

    $user->is_admin = true;
    $user->save();

    $this->info(sprintf('User [%s] promoted to admin.', $discord_id));

    return self::SUCCESS;
})->purpose('Promote a user to admin by Discord ID');
