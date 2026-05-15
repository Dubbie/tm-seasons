# Discord OAuth Setup

This project uses Discord OAuth2 website login with Laravel Socialite and Sanctum SPA cookie auth.

## Required Discord OAuth Scopes

- `identify`
- `email`

## Discord App Settings

Create a Discord application and configure OAuth2 redirect URI:

- Local redirect URI: `http://localhost:8000/auth/discord/callback`

The redirect URI must exactly match `DISCORD_REDIRECT_URI` in the Laravel `.env` file.

## Local Environment Example

Backend (`api/.env`):

```env
APP_URL=http://localhost:8000
FRONTEND_URL=http://localhost:5173

DISCORD_CLIENT_ID=your_discord_client_id
DISCORD_CLIENT_SECRET=your_discord_client_secret
DISCORD_REDIRECT_URI=http://localhost:8000/auth/discord/callback

SANCTUM_STATEFUL_DOMAINS=localhost:5173,localhost:8000,127.0.0.1:5173,127.0.0.1:8000
SESSION_DOMAIN=localhost
```

Frontend (`web/.env`):

```env
VITE_API_URL=http://localhost:8000
```

## Login Flow

1. Frontend sends user to `GET /auth/discord/redirect`
2. User authorizes in Discord
3. Discord returns to `GET /auth/discord/callback`
4. Backend creates/updates local user, logs in session, redirects to frontend `/auth/callback`
5. Frontend calls `GET /api/me` with credentials and routes to dashboard

## Logout Flow

1. Frontend requests `GET /sanctum/csrf-cookie`
2. Frontend calls `POST /auth/logout` with credentials
3. Backend invalidates session

