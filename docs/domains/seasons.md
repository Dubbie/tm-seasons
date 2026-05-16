# Seasons Domain

## Scope

Season lifecycle, map assignment, polling snapshots, standings, and season-focused admin/public endpoints.

## Models

- `Season`
- `SeasonStatus`
- `LeaderboardPoll`
- `LeaderboardSnapshot`
- `SeasonMapPlayerRecord`
- `PlayerMapMilestone`

## Services

- `SeasonLifecycleService`: lifecycle transitions and finalization orchestration.
- `SeasonLeaderboardPollingService`: seasonal leaderboard polling orchestration using Trackmania leaderboard data.
- `SeasonScoringService`: points logic and scoring recalculation flow.
- `SeasonStandingsService`: standings computation and ranking output.
- `SeasonPollingPersistenceService`: leaderboard polling persistence responsibilities.

## HTTP Requests

- `StoreSeasonRequest`
- `UpdateSeasonRequest`
- `AttachSeasonMapRequest`
- `UpdateSeasonMapRequest`

## HTTP Resources

- `SeasonResource`
- `LeaderboardPollResource`
- `LeaderboardSnapshotResource`
- `SeasonMapPlayerRecordResource`

## HTTP Surface

Routes file: `api/routes/domains/seasons.php`

Public endpoints:

- `GET /api/seasons`
- `GET /api/seasons/{slug}`
- `GET /api/seasons/{slug}/leaderboard`
- `GET /api/seasons/{slug}/maps/{map}/leaderboard`
- `GET /api/seasons/{slug}/standings`
- `GET /api/seasons/{slug}/events`
- `GET /api/seasons/{slug}/players/{player}`

Admin endpoints (auth + admin):

- `GET /api/admin/seasons`
- `GET /api/admin/seasons/{season}`
- `POST /api/admin/seasons`
- `PATCH /api/admin/seasons/{season}`
- `DELETE /api/admin/seasons/{season}`
- `POST /api/admin/seasons/{season}/finalize`
- `POST /api/admin/seasons/update-statuses`
- `POST /api/admin/seasons/{season}/maps`
- `PATCH /api/admin/seasons/{season}/maps/{map}`
- `DELETE /api/admin/seasons/{season}/maps/{map}`
- `POST /api/admin/seasons/{season}/poll`
- `GET /api/admin/seasons/{season}/records`
- `GET /api/admin/seasons/{season}/points`
- `GET /api/admin/seasons/{season}/events`
- `POST /api/admin/seasons/{season}/recalculate`
- `GET /api/admin/polls`
- `GET /api/admin/polls/{poll}`

## Features

- Season CRUD and lifecycle management.
- Manual season finalization.
- Poll and snapshot management for seasonal leaderboards.
- Standings and event-driven scoring views.
