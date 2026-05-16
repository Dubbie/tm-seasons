<?php

namespace App\Domains\Seasons\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Domains\Seasons\Http\Requests\Admin\AttachSeasonMapRequest;
use App\Domains\Seasons\Http\Requests\Admin\UpdateSeasonMapRequest;
use App\Domains\Seasons\Http\Resources\SeasonResource;
use App\Domains\Trackmania\Models\Map;
use App\Domains\Seasons\Models\Season;
use Illuminate\Http\Response;

class AdminSeasonMapController extends Controller
{
    public function store(AttachSeasonMapRequest $request, Season $season): SeasonResource
    {
        $mapId = (int) $request->integer('map_id');
        $orderIndex = (int) ($request->input('order_index', 0));

        $season->maps()->attach($mapId, [
            'order_index' => $orderIndex,
            'is_active' => true,
        ]);

        $season->load([
            'createdBy',
            'maps' => fn ($query) => $query->orderBy('season_maps.order_index')->orderBy('season_maps.id'),
        ]);

        return new SeasonResource($season);
    }

    public function update(UpdateSeasonMapRequest $request, Season $season, Map $map): SeasonResource
    {
        $existing = $season->maps()->where('maps.id', $map->id)->exists();
        if (! $existing) {
            abort(404, 'Map is not attached to this season.');
        }

        $season->maps()->updateExistingPivot($map->id, $request->validated());

        $season->load([
            'createdBy',
            'maps' => fn ($query) => $query->orderBy('season_maps.order_index')->orderBy('season_maps.id'),
        ]);

        return new SeasonResource($season);
    }

    public function destroy(Season $season, Map $map): Response
    {
        $season->maps()->detach($map->id);

        return response()->noContent();
    }
}
