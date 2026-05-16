<?php

namespace Tests\Feature\Console;

use App\Domains\Identity\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MakeUserAdminCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_command_promotes_existing_user(): void
    {
        $user = User::factory()->create([
            'discord_id' => '999',
            'is_admin' => false,
        ]);

        $this->artisan('users:make-admin', ['discord_id' => '999'])
            ->expectsOutput('User [999] promoted to admin.')
            ->assertSuccessful();

        $this->assertTrue($user->fresh()->is_admin);
    }

    public function test_admin_command_fails_for_missing_user(): void
    {
        $this->artisan('users:make-admin', ['discord_id' => '404'])
            ->expectsOutput('User with discord_id [404] was not found.')
            ->assertFailed();
    }
}
