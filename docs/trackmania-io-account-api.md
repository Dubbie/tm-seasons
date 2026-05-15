# Trackmania.io Account API (Player Enrichment)

This document covers the `trackmania.io` integration used in this project.

It is separate from Nadeo Live Services:

- Nadeo Live Services: authoritative Trackmania API used for map leaderboard polling
- trackmania.io: community API used here for player/account display enrichment

## Why this exists

When syncing club members, some Trackmania player records may only have `account_id`.
`trackmania.io` is used to enrich those rows with:

- `display_name`
- `zone_id`
- `zone_name`

This makes standings/events/player pages more readable.

## Where it is used

- Client: [api/app/Services/Trackmania/TrackmaniaIoClient.php](../api/app/Services/Trackmania/TrackmaniaIoClient.php)
- Consumer: [api/app/Services/Trackmania/TrackmaniaClubSyncService.php](../api/app/Services/Trackmania/TrackmaniaClubSyncService.php)
- Config: [api/config/services.php](../api/config/services.php) (`services.trackmania_io`)

## Endpoint used

Current client call:

- `GET /api/player/{accountId}`

Default base URL:

- `https://trackmania.io`

## Environment variables

Set in `api/.env` (optional overrides shown with defaults):

```env
TRACKMANIA_IO_BASE_URL=https://trackmania.io
TRACKMANIA_USER_AGENT="tm-bot/1.0 (+https://example.com)"
TRACKMANIA_IO_TIMEOUT_SECONDS=10
TRACKMANIA_IO_RETRY_TIMES=2
TRACKMANIA_IO_RETRY_SLEEP_MS=200
```

## Behavior and fallback

- If `trackmania.io` request fails or returns non-2xx, enrichment returns `null`.
- Club sync continues; existing player values are kept.
- The integration is best-effort and non-blocking.

## Important note

`trackmania.io` is a community service and not the official Ubisoft/Nadeo API.
Treat it as complementary data, not authoritative competition data.
