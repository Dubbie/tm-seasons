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

### season_map_player_records

Current known state for one player on one map in one season.

Fields:

- id
- season_id
- map_id
- trackmania_player_id
- global_position nullable
- current_position nullable (club-relative rank, updated on each poll)
- time_ms nullable (current PB)
- baseline_time_ms nullable (kept for historical reference; does NOT affect scoring)
- first_seen_at nullable
- last_seen_at nullable
- last_improved_at nullable
- total_improvements integer default 0
- created_at
- updated_at

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
- medal_bronze, medal_silver, medal_gold, medal_author
- entered_top_20, entered_top_10, entered_top_5, entered_top_1

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

Points are awarded based on:
1. **First finish** — one-time reward for completing a map
2. **Medal thresholds** — cumulative rewards based on map medal times (bronze/silver/gold/author)
3. **Club position thresholds** — one-time rewards for reaching top 20/10/5/1 in the club

This design is immune to farming: there is no way to earn extra points by intentionally setting bad times.

### Recommended MVP Scoring

Per season map:

```txt
First finish (once per player per map):                    +10
Medal rewards (cumulative — beating gold also awards bronze + silver):
  Bronze medal:                                             +5
  Silver medal:                                            +10
  Gold medal:                                              +20
  Author medal:                                            +35
Position rewards (club-relative, once per threshold):
  Enter Club Top 20:                                       +10
  Enter Club Top 10:                                       +20
  Enter Club Top 5:                                        +35
  Take #1 in Club:                                         +50
```

Each reward can only be claimed once per player per map.

Milestones are stored in a dedicated `player_map_milestones` table with a unique constraint on (season_id, map_id, trackmania_player_id, milestone_key) to prevent duplicates at the database level.

Position rewards use `current_position` (club-relative ranking), not `global_position`. Medal thresholds use map medal times stored locally on the `maps` table (`bronze_time`, `silver_time`, `gold_time`, `author_time`).

---

## Anti-Farming Design

The medal-based scoring system is naturally immune to farming because:

1. **Medal rewards are objective** — based on the map's fixed medal times (bronze, silver, gold, author). There is no way to game them; you either achieved the time or you didn't.
2. **One-time milestones** — each medal/position reward can only be claimed once per player per map. The `player_map_milestones` table enforces this at the database level with a unique constraint.
3. **No improvement-from-baseline scoring** — the old system let players farm points by starting with a bad time and improving incrementally. This is eliminated entirely.
4. **First finish is rewarded once** — no additional points for subsequent finishes on the same map.

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
  fetch club leaderboard from Trackmania.io
  store snapshot (immutable history)
  upsert player records (PB, position)
  evaluate medal rewards (bronze/silver/gold/author)
  evaluate position rewards (top 20/10/5/1)
  create point_events for each newly unlocked milestone
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

- detecting first finishes (one-time per player per map)
- evaluating medal rewards (bronze/silver/gold/author against map medal times)
- evaluating club position rewards (top 20/10/5/1)
- preventing duplicate milestone awards via `player_map_milestones` table
- recalculation (rebuilds all point events from stored records)
- all operations are wrapped in database transactions

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

Scoring test cases (medal-based system):

1. Player gets first finish points once (not duplicated on subsequent calls).
2. Bronze medal is awarded when time <= bronze_time.
3. Gold medal awards bronze + silver + gold cumulatively.
4. Author medal awards all four medals cumulatively.
5. No medals are awarded when time exceeds all thresholds.
6. Medals are not re-awarded on subsequent calls (milestone guard).
7. Improving from bronze to gold awards only the new medals (silver + gold), not bronze again.
8. Position rewards use `current_position` (club-relative), not `global_position`.
9. Position rewards are awarded once per threshold.
10. Recalculation produces deterministic results (same events/totals on repeated runs).
11. No improvement_* event types are ever created.
12. Player improving position from 15 to 3 gets only the new position milestones (top_10 + top_5), not top_20 again.

---

## Example Scoring Scenario

Map has medal times: bronze=60.000, silver=50.000, gold=43.000, author=37.000

**Player A — strong first attempt (37.000s, club position #1):**

```txt
First finish:                              +10
Medal rewards (time <= author_time):
  Bronze medal:                             +5
  Silver medal:                            +10
  Gold medal:                              +20
  Author medal:                            +35
Position rewards (club #1):
  Entered Club Top 20:                     +10
  Entered Club Top 10:                     +20
  Entered Club Top 5:                      +35
  Took 1st place in Club:                  +50
Total:                                    +195
```

**Player B — first attempt at 55.000s (position #30):**

```txt
First finish:                              +10
(No medals — 55.000 > bronze_time of 60.000)
(No position rewards — position 30 > 20)
Total:                                     +10
```

**Player B improves to 42.000s (position #12):**

```txt
No first finish (already awarded)
Medal rewards (time 42.000 <= gold_time 43.000):
  Bronze medal:                             +5
  Silver medal:                            +10
  Gold medal:                              +20
Position rewards (club #12):
  Entered Club Top 20:                     +10
  Entered Club Top 10:                     +20
Total from this poll:                      +65
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

