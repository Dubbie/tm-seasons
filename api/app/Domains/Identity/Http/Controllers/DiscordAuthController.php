<?php

namespace App\Domains\Identity\Http\Controllers;

use App\Domains\Identity\Models\User;
use App\Http\Controllers\Controller;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;

#[Group('Identity', description: 'Current-user profile and Discord authentication endpoints.', weight: 10)]
class DiscordAuthController extends Controller
{
    /**
     * Start Discord OAuth authentication.
     *
     * Redirects the browser to Discord with the identity and email scopes.
     */
    public function redirect(): RedirectResponse
    {
        return Socialite::driver('discord')
            ->scopes(['identify', 'email'])
            ->redirect();
    }

    /**
     * Complete Discord OAuth authentication.
     *
     * Creates or updates the local user from the Discord profile and starts a web session.
     */
    public function callback(): RedirectResponse
    {
        $frontendUrl = rtrim((string) config('app.frontend_url'), '/');

        try {
            $discordUser = Socialite::driver('discord')->user();

            $user = User::query()->firstOrNew([
                'discord_id' => (string) $discordUser->getId(),
            ]);

            $user->name = $discordUser->getNickname() ?: $discordUser->getName() ?: $discordUser->getId();
            $user->email = $discordUser->getEmail() ?: sprintf('%s@discord.local', $discordUser->getId());
            $user->discord_username = $discordUser->getNickname() ?: $discordUser->getName();
            $user->discord_global_name = $discordUser->user['global_name'] ?? null;
            $user->discord_avatar = $discordUser->avatar ?? null;
            $user->last_login_at = now();

            if (! $user->exists) {
                $user->password = Hash::make(bin2hex(random_bytes(24)));
            }

            $user->save();

            Auth::login($user);

            return redirect()->away($frontendUrl.'/auth/callback');
        } catch (\Throwable) {
            return redirect()->away($frontendUrl.'/login?error=discord_auth_failed');
        }
    }

    /**
     * End the current web session.
     *
     * Logs out the current user, invalidates the session, and rotates the CSRF token.
     */
    public function logout(Request $request): JsonResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => 'Logged out']);
    }

    /**
     * Get the current authenticated user.
     *
     * Returns the signed-in user's profile, Discord identity fields, admin flag, and last login timestamp.
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'discord_id' => $user->discord_id,
            'discord_username' => $user->discord_username,
            'discord_global_name' => $user->discord_global_name,
            'discord_avatar' => $user->discord_avatar,
            'is_admin' => $user->is_admin,
            'last_login_at' => $user->last_login_at?->toIso8601String(),
        ]);
    }
}
