<?php

namespace App\Domains\Seasons\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Domains\Seasons\Http\Requests\Admin\StoreSeasonRequest;
use App\Domains\Seasons\Http\Requests\Admin\UpdateSeasonRequest;
use App\Domains\Seasons\Http\Resources\SeasonResource;
use App\Domains\Seasons\Models\SeasonStatus;
use App\Domains\Seasons\Models\Season;
use App\Domains\Seasons\Services\SeasonLifecycleService;
use Illuminate\Http\JsonResponse;
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

    public function store(StoreSeasonRequest $request, SeasonLifecycleService $lifecycleService): SeasonResource
    {
        $season = Season::query()->create([
            ...$request->validated(),
            'status' => $request->string('status', SeasonStatus::Draft->value)->toString(),
            'created_by_user_id' => $request->user()?->id,
        ]);

        $lifecycleService->assertTransitionAllowed($season, $season->status);

        $season->load('createdBy');

        return new SeasonResource($season);
    }

    public function update(UpdateSeasonRequest $request, Season $season, SeasonLifecycleService $lifecycleService): SeasonResource
    {
        $season->fill($request->validated());
        $lifecycleService->assertTransitionAllowed($season, $season->status);
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

    public function finalize(Season $season, SeasonLifecycleService $lifecycleService): JsonResponse
    {
        try {
            $result = $lifecycleService->finalizeSeason($season, (int) request()->user()?->id);
        } catch (\RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json(['data' => $result]);
    }

    public function updateStatuses(SeasonLifecycleService $lifecycleService): JsonResponse
    {
        return response()->json(['data' => $lifecycleService->updateAutomaticStatuses()]);
    }
}
