# Identity Domain

## Scope

Authentication, user identity, and admin authorization.

## Models

- `User`: application user account, auth principal, and admin flag owner.

## HTTP Surface

Routes file: `api/routes/domains/identity.php`

- `GET /api/me` (auth required): returns current authenticated user profile.

## Controllers and Middleware

- `DiscordAuthController`: authentication callback/me endpoint behavior.
- `EnsureUserIsAdmin`: authorization middleware for admin-only endpoints.

## Features

- Discord OAuth login flow.
- Sanctum-backed authenticated session/user retrieval.
- Admin gate enforcement via middleware.
