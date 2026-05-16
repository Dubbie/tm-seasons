# AGENTS

Guidance for agents and contributors working in this Laravel modular monolith.

## Architecture

- This is a Laravel-native modular monolith, not a microservice system.
- Domain code lives in `api/app/Domains/<Domain>`.
- Domain routes live in `api/routes/domains/<domain>.php`.
- Shared Laravel framework glue may stay in conventional Laravel paths when it is genuinely framework-level, for example the base controller, providers, console commands, and bootstrap wiring.
- Prefer small, behavior-preserving changes over broad restructuring.

## Domain Ownership

- `Identity` owns users, Discord auth, current-user profile, and admin authorization.
- `Trackmania` owns clubs, members, players, maps, Trackmania/Nadeo clients, Trackmania.io clients, sync logic, and map import logic.
- `Seasons` owns season lifecycle, polling orchestration, scoring, standings, records, snapshots, and finalization.
- `Activity` owns point events, event feeds, scoring event writes, and activity/stat aggregation support.
- `Integrations` is reserved for concrete future adapters such as Discord bots, overlays, plugins, webhooks, and external/public APIs.

## Domain Boundaries

- External Trackmania HTTP/API calls belong in the Trackmania domain.
- Season polling orchestration belongs in the Seasons domain and should consume Trackmania services rather than making raw HTTP calls.
- Scoring event persistence belongs in Activity services and should be called through clear service methods.
- Controllers should stay thin: validate/request data, call services or models, return resources/responses.
- Do not put reusable business workflows directly in controllers, routes, resources, or console command closures.
- Cross-domain usage is acceptable when it reflects real domain collaboration, but prefer calling another domain's service over reaching through several models manually.

## Services

- Use concrete services by default.
- Avoid interface-per-class, repository-per-model, and speculative contract layers.
- Add an interface only when there is an active multi-implementation need today.
- Prefer constructor injection for service dependencies.
- Avoid hidden `app(...)` fallbacks in new code unless there is a clear compatibility reason.
- Keep services named after business responsibilities, not technical patterns.

## Models And Queries

- Keep Eloquent models in their owning domain.
- Define relationships on models when they reflect real persistence relationships.
- Use eager loading for API responses and service workflows that traverse relationships.
- Avoid raw SQL unless Eloquent/query builder cannot express the query clearly.
- Keep complex aggregation/query behavior in services when it is reused or business-significant.

## HTTP Layer

- Controllers, Form Requests, and Resources should live under the owning domain's `Http` folder.
- Keep public and admin controllers separated where routes have different audiences.
- Use Form Requests for admin input validation.
- Use API Resources for response shape consistency.
- Preserve existing route URLs and response behavior unless the task explicitly changes the API.

## Routes

- Add API routes to the correct file under `api/routes/domains/`.
- Keep public routes and admin routes visually separated.
- Admin routes should use `auth:sanctum` and `admin` middleware.
- Keep route definitions declarative; avoid business logic in route files.

## Config

- Domain-specific config should live in domain-oriented config files such as `config/trackmania.php`, `config/seasons.php`, and `config/integrations.php`.
- Read environment variables only inside config files.
- Use `config(...)` in application code.
- Avoid hardcoded retry counts, timeouts, URLs, scoring values, and feature flags in services.

## Testing

- Run `php artisan test` before claiming backend work is complete.
- Add or update tests for behavior changes, namespace moves that affect resolution, and boundary changes.
- Critical areas need coverage: auth, club sync, map import, polling, scoring, standings, lifecycle, and finalization.
- Prefer feature tests for API and workflow behavior; use unit tests for isolated mapping or calculation logic.
- Keep tests in the domain-oriented feature area when possible, for example `tests/Feature/Seasons` or `tests/Feature/Trackmania`.

## Documentation

- Keep `README.md` useful for onboarding and demos.
- Keep domain docs in `docs/domains/*` aligned with actual ownership.
- Keep architecture guidance in `README.md`, `AGENTS.md`, and `docs/domains/*` current after architecture-level changes.
- Document current behavior and near-term direction, not speculative enterprise architecture.

## Cleanup Rules

- Remove dead code, unused helpers, stale imports, old namespace references, and empty legacy directories when safe.
- Do not keep wrapper classes for old namespaces.
- Do not introduce compatibility shims unless there is an active migration need.
- Do not move code only for symmetry; move it when ownership becomes clearer.

## Before Finishing

- Scan for stale references after moving classes, especially old `App\Models`, `App\Services`, `App\Http\Requests`, and `App\Http\Resources` namespaces.
- Run route checks when routes/controllers changed: `php artisan route:list --path=api`.
- Run tests: `php artisan test`.
- Summarize what changed, what was verified, and any remaining architectural concerns.
