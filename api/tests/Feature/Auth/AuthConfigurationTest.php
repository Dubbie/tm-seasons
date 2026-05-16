<?php

namespace Tests\Feature\Auth;

use App\Domains\Identity\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class AuthConfigurationTest extends TestCase
{
    use RefreshDatabase;

    public function test_auth_provider_model_points_to_identity_user(): void
    {
        $modelClass = config('auth.providers.users.model');

        $this->assertSame(User::class, $modelClass);
        $this->assertTrue(class_exists($modelClass));
    }

    public function test_web_guard_provider_can_retrieve_identity_user_by_id(): void
    {
        $user = User::factory()->create();

        $provider = Auth::guard('web')->getProvider();
        $resolved = $provider->retrieveById($user->getAuthIdentifier());

        $this->assertNotNull($resolved);
        $this->assertSame(User::class, $resolved::class);
        $this->assertSame($user->id, $resolved->id);
    }
}
