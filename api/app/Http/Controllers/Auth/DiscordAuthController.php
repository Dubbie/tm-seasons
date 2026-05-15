<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;

class DiscordAuthController extends Controller
{
    public function redirect(): RedirectResponse
    {
        return Socialite::driver('discord')
            ->scopes(['identify', 'email'])
            ->redirect();
    }

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

    public function logout(Request $request): JsonResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => 'Logged out']);
    }

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
