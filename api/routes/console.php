<?php

use App\Exceptions\Trackmania\TrackmaniaClientException;
use App\Exceptions\Trackmania\TrackmaniaTokenException;
use App\Services\Trackmania\TrackmaniaClient;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

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
    } catch (\Throwable $exception) {
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
