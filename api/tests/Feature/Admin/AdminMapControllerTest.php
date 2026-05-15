<?php

namespace Tests\Feature\Admin;

use App\Exceptions\Trackmania\TrackmaniaClientException;
use App\Models\Map;
use App\Models\User;
use App\Services\Maps\MapImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class AdminMapControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_admin_cannot_access_admin_maps(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)
            ->getJson('/api/admin/maps')
            ->assertForbidden();
    }

    public function test_admin_can_list_show_update_and_delete_maps(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $map = Map::query()->create([
            'uid' => 'map-uid-1',
            'name' => 'Map One',
            'author_name' => 'Author',
        ]);

        $this->actingAs($admin)
            ->getJson('/api/admin/maps')
            ->assertOk()
            ->assertJsonPath('data.0.uid', 'map-uid-1');

        $this->actingAs($admin)
            ->getJson('/api/admin/maps/'.$map->id)
            ->assertOk()
            ->assertJsonPath('data.id', $map->id);

        $this->actingAs($admin)
            ->patchJson('/api/admin/maps/'.$map->id, [
                'name' => 'Map One Updated',
                'map_style' => 'Tech',
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Map One Updated')
            ->assertJsonPath('data.map_style', 'Tech');

        $this->actingAs($admin)
            ->deleteJson('/api/admin/maps/'.$map->id)
            ->assertNoContent();

        $this->assertDatabaseMissing('maps', ['id' => $map->id]);
    }

    public function test_import_map_successfully(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $map = Map::query()->create([
            'uid' => 'imported-uid',
            'name' => 'Imported Name',
            'author_name' => 'Imported Author',
            'map_type' => 'Race',
        ]);

        $service = Mockery::mock(MapImportService::class);
        $service->shouldReceive('importByUid')->once()->with('imported-uid')->andReturn($map);
        $this->app->instance(MapImportService::class, $service);

        $this->actingAs($admin)
            ->postJson('/api/admin/maps/import', ['uid' => 'imported-uid'])
            ->assertCreated()
            ->assertJsonPath('data.uid', 'imported-uid')
            ->assertJsonPath('data.name', 'Imported Name');
    }

    public function test_import_map_returns_not_found_for_missing_uid(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $service = Mockery::mock(MapImportService::class);
        $service->shouldReceive('importByUid')
            ->once()
            ->with('missing-uid')
            ->andThrow(new TrackmaniaClientException('Trackmania map not found for uid [missing-uid].'));
        $this->app->instance(MapImportService::class, $service);

        $this->actingAs($admin)
            ->postJson('/api/admin/maps/import', ['uid' => 'missing-uid'])
            ->assertNotFound()
            ->assertJsonPath('message', 'Unable to import map from Trackmania services.');
    }
}
