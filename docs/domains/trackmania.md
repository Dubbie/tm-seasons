# Trackmania Domain

## Scope

Trackmania-specific entities and external API integration: clubs, members, players, maps, and leaderboard ingestion.

## Models

- `TrackmaniaClub`
- `ClubMember`
- `TrackmaniaPlayer`
- `Map`

## Data and Exceptions

- Data objects:
  - `TrackmaniaLeaderboard`
  - `TrackmaniaLeaderboardEntry`
  - `TrackmaniaMap`
- Exceptions:
  - `TrackmaniaClientException`
  - `TrackmaniaTokenException`

## Services

- `TrackmaniaTokenService`: obtains and refreshes Nadeo tokens.
- `TrackmaniaClient`: reads map/leaderboard data from official Trackmania services.
- `TrackmaniaIoClient`: retrieves player enrichment from Trackmania.io.
- `TrackmaniaClubSyncService`: syncs primary/selected club and members.
- `MapImportService`: map import workflow.
- `ActiveClubPlayerService`: active-player resolution for scoring workflows.

## HTTP Requests

- `ImportMapRequest`
- `UpdateMapRequest`
- `SyncClubRequest`

## HTTP Surface

Routes file: `api/routes/domains/trackmania.php`

Public endpoints:

- `GET /api/maps/{uid}`
- `GET /api/clubs`
- `GET /api/clubs/{club}`
- `GET /api/clubs/{club}/members`

Admin endpoints (auth + admin):

- `GET /api/admin/maps`
- `GET /api/admin/maps/{map}`
- `POST /api/admin/maps/import`
- `PATCH /api/admin/maps/{map}`
- `DELETE /api/admin/maps/{map}`
- `GET /api/admin/clubs`
- `GET /api/admin/clubs/{club}`
- `POST /api/admin/clubs/sync`
- `GET /api/admin/clubs/{club}/members`
- `GET /api/admin/club`
- `POST /api/admin/club/sync`
- `GET /api/admin/club/members`

## Features

- Club and member sync from Trackmania providers.
- Map catalog import and CRUD operations.
- Leaderboard data access used by Seasons polling/scoring.
