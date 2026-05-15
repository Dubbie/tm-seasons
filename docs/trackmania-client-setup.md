# Trackmania Client Setup

This document explains how to configure and test the Milestone 0 Trackmania PHP client in the Laravel API.

## Purpose

The current implementation is a connectivity + adapter milestone:

- obtain/cached Nadeo access token
- fetch map info
- fetch map leaderboard
- map responses into internal DTOs

It does not yet implement seasons, scoring, or persistence.

## Prerequisites

- Laravel API dependencies installed (`composer install`)
- Trackmania dedicated server credentials (`login` and `password`)

## Environment Variables

Set these in `api/.env`:

```env
TRACKMANIA_DEDICATED_LOGIN=your_dedicated_login
TRACKMANIA_DEDICATED_PASSWORD=your_dedicated_password
TRACKMANIA_AUDIENCE=NadeoLiveServices
TRACKMANIA_AUTH_BASE_URL=https://prod.trackmania.core.nadeo.online
TRACKMANIA_BASE_URL=https://live-services.trackmania.nadeo.live
TRACKMANIA_TIMEOUT_SECONDS=10
TRACKMANIA_RETRY_TIMES=3
TRACKMANIA_RETRY_SLEEP_MS=200
TRACKMANIA_TOKEN_EXPIRY_SKEW_SECONDS=60
```

Notes:

- Authentication uses the Nadeo `basic` token endpoint with dedicated login/password.
- Audience defaults to `NadeoLiveServices`.
- Tokens are cached and refreshed before expiry.

## Apply Config Changes

From `api/`:

```bash
php artisan config:clear
```

If you use config caching in your local setup:

```bash
php artisan config:cache
```

## Smoke Test Command

Run:

```bash
php artisan trackmania:test-map {mapUid}
```

Example:

```bash
php artisan trackmania:test-map z8LQxJ9m8w6QF7kS8C6W9vNnqVg
```

The command prints:

- map name and UID
- author/gold/silver/bronze times
- fetched leaderboard entry count
- WR accountId
- WR score
- WR timestamp

## Troubleshooting

- `Failed to retrieve Trackmania token.`
  - Verify `TRACKMANIA_DEDICATED_LOGIN` and `TRACKMANIA_DEDICATED_PASSWORD`.
  - Confirm auth endpoint is reachable.

- `Trackmania map not found for uid [...]`
  - Verify the map UID is correct and available.

- `Trackmania leaderboard request failed with status [...]`
  - Usually API availability/rate-limit/transient issue.
  - Retry after a short delay.

- Empty leaderboard output
  - Some maps/groups can legitimately return empty results.
  - Current client normalizes empty shapes to zero entries.

## Where the Code Lives

- Token service: `api/app/Services/Trackmania/TrackmaniaTokenService.php`
- API client: `api/app/Services/Trackmania/TrackmaniaClient.php`
- DTOs: `api/app/DTOs/Trackmania/*`
- Command: `api/routes/console.php` (`trackmania:test-map`)
