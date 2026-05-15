# tm-bot

Trackmania club seasons platform with:

- `api/`: Laravel REST API
- `web/`: Vue 3 + TypeScript frontend

The app lets admins run seasonal competitions on selected Trackmania maps and gives players transparent standings and point events.

## What it does

- Discord OAuth login for website access
- Admin-managed seasons and season maps
- Automatic season lifecycle transitions (`scheduled -> active -> ended`)
- Automatic polling of the active season leaderboard
- Event-based scoring (first finish, medal rewards, final placement rewards)
- Public views for season overview, standings, events, and player details

## Architecture

- Backend: Laravel 13, Sanctum (SPA/session auth), Socialite (Discord)
- Frontend: Vue 3, Vue Router, TypeScript, Vite, Tailwind CSS
- Data model highlights:
  - `seasons`, `maps`, `season_maps`
  - `trackmania_players`, `season_map_player_records`
  - `point_events` (auditable scoring log)
  - `leaderboard_polls`, `leaderboard_snapshots`

## External APIs and integrations

Important: this project uses two similarly named but different Trackmania APIs.

### 1) Discord OAuth

Used for website authentication.

- Redirect endpoint: `/auth/discord/callback`
- Required scopes: `identify`, `email`
- Docs: [docs/discord-oauth-setup.md](./docs/discord-oauth-setup.md)

### 2) Trackmania (Nadeo Live Services, official)

Used to fetch map metadata and leaderboard data.

- Auth base URL (default): `https://prod.trackmania.core.nadeo.online`
- API base URL (default): `https://live-services.trackmania.nadeo.live`
- Uses dedicated server login/password for token acquisition
- Docs: [docs/trackmania-client-setup.md](./docs/trackmania-client-setup.md)

### 3) Trackmania.io (community API, account enrichment)

Used for player/account detail enrichment (for example display names and zone info) during club sync.

- Base URL (default): `https://trackmania.io`
- Endpoint used: `GET /api/player/{accountId}`
- Config key: `services.trackmania_io` in `api/config/services.php`
- Dedicated docs: [docs/trackmania-io-account-api.md](./docs/trackmania-io-account-api.md)

## Scoring model

Configured in [api/config/season_scoring.php](./api/config/season_scoring.php):

- `first_finish`: 10 points
- Medal rewards:
  - bronze: 5
  - silver: 10
  - gold: 20
  - author: 35
- Final placement rewards:
  - top 20: 10
  - top 10: 20
  - top 5: 35
  - top 1: 50

Each award is stored as an immutable point event so totals are explainable.

## Scheduler automation

Configured in [api/routes/console.php](./api/routes/console.php):

- `season:update-statuses` runs every minute
- `season:poll-active` runs every minute

Run this to enable automation:

```bash
cd api
php artisan schedule:work
```

Finalization is intentionally manual (`ended -> finalized`).

## API overview

Defined in [api/routes/api.php](./api/routes/api.php).

Public endpoints include:

- `GET /api/seasons`
- `GET /api/seasons/{slug}`
- `GET /api/seasons/{slug}/leaderboard`
- `GET /api/seasons/{slug}/standings`
- `GET /api/seasons/{slug}/events`

Admin endpoints (auth + admin middleware) include:

- season CRUD + finalize
- map import/management
- season map attach/order/active toggles
- manual poll + records
- points/events/recalculation

## Local development

## 1) Backend (`api/`)

```bash
cd api
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve
```

Set required env vars for Discord and Trackmania in `api/.env` (see docs above).

## 2) Frontend (`web/`)

```bash
cd web
npm install
cp .env.example .env
npm run dev
```

Set `VITE_API_URL` in `web/.env` to your API URL (commonly `http://localhost:8000`).

## 3) Run scheduler worker

In a separate terminal:

```bash
cd api
php artisan schedule:work
```

## Useful commands

Backend:

```bash
cd api
php artisan test
php artisan schedule:list
php artisan trackmania:test-map {mapUid}
php artisan season:poll-active
php artisan season:update-statuses
php artisan season:finalize {seasonIdOrSlug}
```

Frontend:

```bash
cd web
npm run type-check
npm run test:unit
npm run test:e2e
npm run build
```
