<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreSeasonRequest;
use App\Http\Requests\Admin\UpdateSeasonRequest;
use App\Http\Resources\SeasonResource;
use App\Models\Season;
use Illuminate\Http\Response;

class AdminSeasonController extends Controller
{
    public function index()
    {
        $seasons = Season::query()
            ->with(['createdBy'])
            ->latest('id')
            ->paginate(25);

        return SeasonResource::collection($seasons);
    }

    public function show(Season $season): SeasonResource
    {
        $season->load([
            'createdBy',
            'maps' => fn ($query) => $query->orderBy('season_maps.order_index')->orderBy('season_maps.id'),
        ]);

        return new SeasonResource($season);
    }

    public function store(StoreSeasonRequest $request): SeasonResource
    {
        $season = Season::query()->create([
            ...$request->validated(),
            'created_by_user_id' => $request->user()?->id,
        ]);

        $season->load('createdBy');

        return new SeasonResource($season);
    }

    public function update(UpdateSeasonRequest $request, Season $season): SeasonResource
    {
        $season->fill($request->validated());
        $season->save();

        $season->load([
            'createdBy',
            'maps' => fn ($query) => $query->orderBy('season_maps.order_index')->orderBy('season_maps.id'),
        ]);

        return new SeasonResource($season);
    }

    public function destroy(Season $season): Response
    {
        $season->delete();

        return response()->noContent();
    }
}
