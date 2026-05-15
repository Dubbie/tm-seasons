# Trackmania Club Seasons — Codex Project Brief

## Project Goal

Build a Trackmania club season platform for a small/medium Discord community.

The app should let admins create competitive but participation-friendly seasons around selected Trackmania maps. Club members should be able to view season leaderboards, per-map standings, and a transparent event log showing exactly how points were earned.

The project will use:

- Backend: Laravel REST API
- Frontend: Vue 3 + TypeScript + Vite
- Styling: Tailwind CSS preferred
- External data source: Trackmania.io / node-trackmania.io package where possible
- Authentication: Discord OAuth login for the website
- Future integration: Discord bot notifications, but this should not be required for the MVP

The main design principle is:

> Reward meaningful participation and progress, not only raw skill.

The system should avoid encouraging players to farm points by intentionally setting bad times and then improving in tiny increments.

---

## Important External API Notes

Trackmania.io is a community service and is not affiliated with or endorsed by Nadeo or Ubisoft. API availability and behavior may change.

Do not design the system around aggressive polling. Prefer snapshot-based polling every few minutes instead of trying to catch every run in real time.

Relevant leaderboard use cases:

- Fetch map leaderboard data
- Fetch map leaderboard data for club members, if supported by the package/API
- Fetch map metadata
- Compare stored leaderboard snapshots against the latest fetched data

The system should be resilient to API failures, timeouts, missing data, and rate limits.

---

## Core Product Modules

### 1. Public Season Dashboard

Club members should be able to view:

- Active season overview
- Overall season standings
- Per-map standings
- Recent point events / activity log
- Selected season maps
- Player season stats

The public dashboard should make the season feel alive and readable.

Example public pages:

- `/seasons`
- `/seasons/:seasonId`
- `/seasons/:seasonId/leaderboard`
- `/seasons/:seasonId/maps/:mapId`
- `/players/:playerId`
- `/events`

---

### 2. Event Log

Every awarded point must be explainable by an immutable point event.

Example event:

```txt
Shoobeeh earned +15 on Ice Thing 03
Reason: Improved season PB by 0.50s
Old time: 31.420
New time: 30.901
```

The event log should support:

- filtering by season
- filtering by player
- filtering by map
- filtering by event type
- sorting newest first

Point totals should either be calculated from point events or kept denormalized while remaining auditable against point events.

---

### 3. Admin Panel

The admin panel should allow trusted users to manage seasons, maps, and users.

Required admin areas:

#### Seasons

Admins can:

- create a season
- edit season name, description, start date, end date
- activate/deactivate a season
- archive a finished season
- choose or view the scoring preset

#### Maps

Admins can:

- add a Trackmania map by map UID / map ID / Trackmania.io identifier
- fetch metadata from Trackmania.io
- store map name, author, UID, thumbnail URL if available
- enable/disable maps
- view known maps in the system

#### Season Maps

Admins can:

- attach maps to a season
- remove maps from a season before the season starts
- order maps in a season
- mark maps as active/inactive within a season
- optionally mark maps as weekly focus maps later

#### Users

Users should log into the website with Discord OAuth. The local `users` table represents the app user, while `discord_accounts` stores Discord identity details. Trackmania accounts should be linked separately for leaderboard tracking.

Admins can:

- view registered users
- view linked Discord accounts
- view linked Trackmania accounts
- promote/demote admins

For the MVP, a simple role model is enough:

- owner
- admin
- member

---

## Suggested Data Model

The exact schema can evolve, but start from this shape.

### users

Represents local app users authenticated through Discord OAuth.

Fields:

- id
- name
- email nullable
- role: owner/admin/member
- created_at
- updated_at

Notes:

- Keep Laravel's default users table if convenient, but adapt it for OAuth-first login.
- Do not require password login for the MVP unless useful for local development.
- The `users` table is still needed for app roles, permissions, admin ownership, and linking external accounts.

---

### trackmania_accounts

Represents a linked Trackmania identity.

Fields:

- id
- user_id nullable
- account_id / player_id from Trackmania
- display_name
- zone nullable
- avatar_url nullable
- raw_metadata json nullable
- created_at
- updated_at

Notes:

- Some players may appear in fetched leaderboard data before they register.
- Therefore `user_id` should be nullable.

---

### discord_accounts

Represents the Discord identity used to log into the website. This should be implemented in the MVP.

Fields:

- id
- user_id
- discord_id unique
- username
- global_name nullable
- avatar_url nullable
- raw_metadata json nullable
- created_at
- updated_at

Notes:

- Discord OAuth is the primary website login method.
- On first login, create a local user and a linked `discord_accounts` row.
- The first configured Discord account or seeded user should become `owner`.
- Later, Discord guild membership can optionally be checked to restrict access to club members.

---

### maps

Represents Trackmania maps known to the app.

Fields:

- id
- trackmania_map_uid
- trackmania_map_id nullable
- name
- author_account_id nullable
- author_name nullable
- thumbnail_url nullable
- external_url nullable
- raw_metadata json nullable
- is_enabled boolean
- created_at
- updated_at

Important:

- Enforce uniqueness on map UID where possible.

---

### seasons

Fields:

- id
- name
- slug
- description nullable
- starts_at
- ends_at
- status: draft/active/finished/archived
- scoring_preset_key
- created_by_user_id
- created_at
- updated_at

Notes:

- Only one active season is needed initially, but do not hardcode that too deeply.

---

### season_maps

Pivot between seasons and maps.

Fields:

- id
- season_id
- map_id
- sort_order
- is_active boolean
- starts_at nullable
- ends_at nullable
- created_at
- updated_at

Notes:

- Nullable starts_at/ends_at allows weekly map rotations later.

---

### season_participants

Represents a Trackmania account participating in a season.

Fields:

- id
- season_id
- trackmania_account_id
- points_total integer default 0
- maps_finished_count integer default 0
- created_at
- updated_at

Notes:

- Participants can be created automatically when detected on a season map leaderboard.

---

### season_map_entries

Current known state for one player on one map in one season.

Fields:

- id
- season_id
- map_id
- trackmania_account_id
- baseline_time_ms nullable
- current_best_time_ms nullable
- current_rank nullable
- previous_best_time_ms nullable
- previous_rank nullable
- points_total integer default 0
- claimed_milestones json
- first_seen_at nullable
- last_improved_at nullable
- last_checked_at nullable
- created_at
- updated_at

Important:

- `baseline_time_ms` is the player's reference time for improvement scoring.
- For players with an existing time at season start, baseline is their season-start PB.
- For players with no previous time, their first detected finish becomes their baseline and earns finish/first-time points, but should not generate huge improvement points immediately.

---

### point_events

Immutable source of truth for awarded points.

Fields:

- id
- season_id
- map_id nullable
- trackmania_account_id
- points integer
- type string
- title string
- description text nullable
- metadata json nullable
- created_at

Example types:

- first_finish
- first_season_time
- improvement_milestone
- rank_milestone
- seasonal_wr
- admin_adjustment

Notes:

- Do not update/delete events casually.
- If correction is needed, create an admin adjustment event.

---

### leaderboard_snapshots

Stores raw or summarized data from polling.

Fields:

- id
- season_id
- map_id
- poll_run_id
- fetched_at
- raw_data json nullable
- created_at

This may grow large. For MVP, storing raw JSON is useful for debugging. Later, pruning can be added.

---

### poll_runs

Tracks each polling job execution.

Fields:

- id
- season_id nullable
- map_id nullable
- status: started/succeeded/failed/partial
- error_message nullable
- started_at
- finished_at nullable
- created_at
- updated_at

---

## Scoring Philosophy

The scoring system should be participation-first, but still reward competitive achievement.

Do not award points for every tiny PB improvement. Award milestone points only.

### Recommended MVP Scoring

Per season map:

```txt
Finish map: +10
First seasonal valid time: +5
Improve over baseline by 0.10s total: +5
Improve over baseline by 0.25s total: +10
Improve over baseline by 0.50s total: +15
Improve over baseline by 1.00s total: +25
Improve over baseline by 2.00s total: +40
Enter top 20: +10
Enter top 10: +20
Enter top 5: +35
Take seasonal WR: +50
```

Each milestone can only be claimed once per player per map.

Use `claimed_milestones` on `season_map_entries` to prevent duplicate awards.

Example `claimed_milestones`:

```json
{
  "finish": true,
  "first_season_time": true,
  "improvement_100ms": true,
  "improvement_250ms": true,
  "top_20": true,
  "top_10": false,
  "top_5": false,
  "seasonal_wr": false
}
```

---

## Anti-Farming Rules

This is critical.

### Problem

A fast player could intentionally set a bad time, then improve slightly over many runs to farm points.

### Solution

Use season baselines and one-time milestones.

Rules:

1. Improvement points are based on total improvement over `baseline_time_ms`, not the previous poll's time.
2. Each improvement threshold can only be awarded once.
3. First detected finish for a new player becomes their baseline.
4. The first finish does not itself grant improvement milestone points.
5. Improvement points per map should be capped naturally by the finite milestone list.
6. Tiny repeated improvements should not produce repeated points.

Example:

```txt
Baseline: 31.500
Current: 31.220
Total improvement: 280ms
Award 100ms and 250ms milestones once.
Do not award anything again until they cross 500ms.
```

---

## Polling Strategy

Do not poll every 30 seconds.

Use snapshot polling.

Recommended MVP:

- Poll active season maps every 5–15 minutes.
- Poll less frequently if there are many maps.
- Use Laravel scheduler + queued jobs.
- Keep API failures non-fatal.
- Store poll runs for debugging.

Example flow:

```txt
For each active season map:
  fetch latest leaderboard from Trackmania.io/node package
  store snapshot or useful summary
  upsert Trackmania accounts found in leaderboard
  upsert season participants
  compare fetched records to season_map_entries
  detect new finish, PB improvement, rank milestone, seasonal WR
  create point_events for newly unlocked milestones
  update season_map_entries
  update denormalized point totals
```

Important:

- The app does not need to detect every individual run.
- It only needs to detect that the player's currently known PB improved since the previous snapshot.

---

## Authentication

Use Discord OAuth for website login. Laravel Socialite is a good fit.

Recommended auth flow:

```txt
GET /api/auth/discord/redirect
GET /api/auth/discord/callback
POST /api/auth/logout
GET /api/me
```

After Discord callback:

1. Fetch Discord user profile.
2. Find or create `discord_accounts` by `discord_id`.
3. Find or create the local `users` row.
4. Store/update Discord username, global name, avatar, and raw metadata.
5. Issue an app session/token for the Vue frontend.

For a separate Vue frontend, use a simple, explicit API auth strategy. Laravel Sanctum is acceptable if configured correctly for SPA auth, but token-based auth is also fine for MVP.

Trackmania account linking is separate from Discord login. A user can log in with Discord first, then link or claim their Trackmania account later.

## API Design Suggestions

Use REST endpoints grouped by concern.

### Public API

```txt
GET /api/seasons
GET /api/seasons/{season}
GET /api/seasons/{season}/leaderboard
GET /api/seasons/{season}/maps
GET /api/seasons/{season}/maps/{map}/leaderboard
GET /api/seasons/{season}/events
GET /api/players/{trackmaniaAccount}
```

### Admin API

```txt
GET    /api/admin/seasons
POST   /api/admin/seasons
GET    /api/admin/seasons/{season}
PATCH  /api/admin/seasons/{season}
DELETE /api/admin/seasons/{season}

GET    /api/admin/maps
POST   /api/admin/maps
GET    /api/admin/maps/{map}
PATCH  /api/admin/maps/{map}
DELETE /api/admin/maps/{map}
POST   /api/admin/maps/import-from-trackmania

POST   /api/admin/seasons/{season}/maps
PATCH  /api/admin/seasons/{season}/maps/{seasonMap}
DELETE /api/admin/seasons/{season}/maps/{seasonMap}

GET    /api/admin/users
PATCH  /api/admin/users/{user}
```

### Polling / Jobs API

Usually internal/admin-only:

```txt
POST /api/admin/seasons/{season}/poll
POST /api/admin/seasons/{season}/maps/{map}/poll
GET  /api/admin/poll-runs
```

---

## Frontend Views

### Public Views

- Season list
- Active season dashboard
- Overall leaderboard
- Per-map leaderboard
- Event log
- Player profile

### Admin Views

- Admin dashboard
- Season list/create/edit
- Map list/create/edit/import
- Season map management
- User management
- Poll run logs

---

## MVP Scope

Build this first:

1. Laravel REST API scaffold
2. Vue frontend scaffold
3. Discord OAuth login for website users
4. User roles: owner/admin/member
5. Admin maps CRUD
6. Admin seasons CRUD
7. Attach maps to seasons
8. Poll selected season maps manually or via scheduler
9. Store leaderboard entries
10. Award milestone point events
11. Public season leaderboard
12. Public per-map leaderboard
13. Public event log

Do NOT build these in the first pass unless the basics are stable:

- Discord bot
- Trackmania OAuth
- complex dynamic scoring UI
- team seasons
- fantasy/prediction systems
- live websocket updates
- advanced achievements

---

## Implementation Preferences

- Keep backend domain logic in services, not controllers.
- Use Form Requests for validation.
- Use API Resources for response shaping.
- Use queued jobs for polling.
- Use Laravel Scheduler for recurring polling.
- Keep scoring logic isolated in a service class.
- Prefer explicit DTO-like arrays/classes for fetched leaderboard records.
- Keep external API calls behind a service interface.

Suggested services:

```txt
DiscordAuthService
TrackmaniaApiService
SeasonPollingService
SeasonScoringService
PointEventService
LeaderboardSnapshotService
```

Suggested jobs:

```txt
PollSeasonMapLeaderboardJob
PollActiveSeasonMapsJob
```

---

## Suggested Laravel Service Responsibilities

### TrackmaniaApiService

Responsible for:

- fetching map metadata
- fetching map leaderboard
- normalizing external API responses
- handling API errors/timeouts

It should return normalized internal structures, not raw package objects.

### SeasonPollingService

Responsible for:

- choosing which maps to poll
- creating poll run records
- calling TrackmaniaApiService
- passing normalized records to scoring
- storing snapshots

### SeasonScoringService

Responsible for:

- detecting first finishes
- setting baselines
- detecting improvement milestones
- detecting rank milestones
- preventing duplicate milestone awards
- returning point events to create

### PointEventService

Responsible for:

- creating immutable point events
- updating denormalized totals if used

---

## Testing Priorities

Write tests for auth and scoring logic early.

Auth test cases:

1. Discord OAuth login creates a local user.
2. Discord OAuth login creates or updates the linked Discord account.
3. Existing Discord account logs into the same local user.
4. Non-admin users cannot access admin routes.
5. Admin users can access admin routes.

Scoring test cases:

1. Player gets finish points once.
2. Player gets first seasonal time points once.
3. Player with no previous time uses first finish as baseline.
4. First finish does not grant improvement points.
5. Improvement milestone is awarded once.
6. Multiple crossed milestones in one poll are all awarded once.
7. Repeated polling with same time does not duplicate events.
8. Worse times do not change current best.
9. Better time updates current best.
10. Rank milestones are awarded once.
11. Admin adjustment events affect totals.

---

## Example Scoring Scenario

Player has baseline of 31.500 on a map.

Poll 1:

```txt
Current time: 31.300
Improvement: 200ms
Award:
- improvement_100ms +5
```

Poll 2:

```txt
Current time: 31.180
Improvement: 320ms
Award:
- improvement_250ms +10
```

Poll 3:

```txt
Current time: 31.120
Improvement: 380ms
Award:
- nothing new
```

Poll 4:

```txt
Current time: 30.980
Improvement: 520ms
Award:
- improvement_500ms +15
```

---

## UX Tone

The app should feel community-focused and fun, not like a sterile analytics dashboard.

Use language like:

- climbed the standings
- improved by 0.420s
- entered top 10
- took the seasonal crown
- finished their first run
- biggest mover today

But keep the database/event types clean and boring internally.

---

## Long-Term Ideas, Not MVP

- Discord bot posts for milestone events
- Weekly map rotations
- Team seasons
- Rivalry detection
- Player badges
- Hall of fame
- Season archive pages
- Mapper stats
- Map-of-the-week
- Club newspaper / weekly digest
- Discord roles for season winners

