<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Contracts\User as SocialiteUserContract;
use Laravel\Socialite\Facades\Socialite;
use Mockery;
use Tests\TestCase;

class DiscordCallbackTest extends TestCase
{
    use RefreshDatabase;

    public function test_callback_creates_or_updates_user_without_real_discord_call(): void
    {
        config()->set('app.frontend_url', 'http://localhost:5173');

        $socialiteUser = Mockery::mock(SocialiteUserContract::class);
        $socialiteUser->shouldReceive('getId')->andReturn('disc-1');
        $socialiteUser->shouldReceive('getNickname')->andReturn('Shoobeeh');
        $socialiteUser->shouldReceive('getName')->andReturn('Shoobeeh');
        $socialiteUser->shouldReceive('getEmail')->andReturn('shoobeeh@example.com');
        $socialiteUser->avatar = 'avatar-hash';
        $socialiteUser->user = ['global_name' => 'Shoobeeh'];

        $provider = Mockery::mock();
        $provider->shouldReceive('user')->andReturn($socialiteUser);

        Socialite::shouldReceive('driver')->with('discord')->andReturn($provider);

        $response = $this->get('/auth/discord/callback');

        $response->assertRedirect('http://localhost:5173/auth/callback');

        $this->assertDatabaseHas('users', [
            'discord_id' => 'disc-1',
            'discord_username' => 'Shoobeeh',
            'discord_global_name' => 'Shoobeeh',
            'discord_avatar' => 'avatar-hash',
            'email' => 'shoobeeh@example.com',
        ]);

        $user = User::query()->where('discord_id', 'disc-1')->firstOrFail();
        $this->assertNotNull($user->last_login_at);
    }
}
