# Activity Domain

## Scope

Cross-cutting season activity/event storage and read models used by scoring and event feeds.

## Models

- `PointEvent`: immutable scoring/event ledger entries.

## Services

- `SeasonActivityFeedService`: event feed query logic.
- `SeasonActivityStatsService`: activity/standings aggregation support.
- `SeasonPointEventWriteService`: point-event writes and clear/rebuild operations.

## HTTP Resources

- `PointEventResource`

## HTTP Surface

Routes file: `api/routes/domains/activity.php`

- No standalone public/admin routes yet.
- Activity behavior is currently consumed through Seasons endpoints and services.

## Features

- Centralized event persistence for explainable scoring history.
- Shared activity queries reused by season scoring controllers/services.
