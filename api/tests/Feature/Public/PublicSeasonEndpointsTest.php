<?php

namespace Tests\Feature\Public;

use App\Domains\Trackmania\Models\Map;
use App\Domains\Seasons\Models\Season;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicSeasonEndpointsTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_seasons_and_maps_endpoints_return_expected_data(): void
    {
        $season = Season::query()->create([
            'name' => 'Public Season',
            'status' => 'active',
            'description' => 'Public description',
        ]);

        $map1 = Map::query()->create(['uid' => 'pub-1', 'name' => 'Public 1']);
        $map2 = Map::query()->create(['uid' => 'pub-2', 'name' => 'Public 2']);

        $season->maps()->attach($map1->id, ['order_index' => 10, 'is_active' => true]);
        $season->maps()->attach($map2->id, ['order_index' => 2, 'is_active' => false]);

        $this->getJson('/api/seasons')
            ->assertOk()
            ->assertJsonPath('data.0.slug', $season->slug);

        $detail = $this->getJson('/api/seasons/'.$season->slug)
            ->assertOk();

        $this->assertSame('pub-2', $detail->json('data.maps.0.uid'));
        $this->assertSame('pub-1', $detail->json('data.maps.1.uid'));

        $this->getJson('/api/maps/pub-1')
            ->assertOk()
            ->assertJsonPath('data.uid', 'pub-1')
            ->assertJsonPath('data.name', 'Public 1');
    }

    public function test_draft_season_not_listed_publicly(): void
    {
        Season::query()->create([
            'name' => 'Hidden Draft',
            'status' => 'draft',
        ]);

        $this->getJson('/api/seasons')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }
}
