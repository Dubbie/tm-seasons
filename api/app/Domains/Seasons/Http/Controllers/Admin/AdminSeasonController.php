<?php

namespace App\Domains\Seasons\Http\Controllers\Admin;

use App\Domains\Seasons\Http\Requests\Admin\StoreSeasonRequest;
use App\Domains\Seasons\Http\Requests\Admin\UpdateSeasonRequest;
use App\Domains\Seasons\Http\Resources\SeasonResource;
use App\Domains\Seasons\Models\Season;
use App\Domains\Seasons\Models\SeasonStatus;
use App\Domains\Seasons\Services\SeasonLifecycleService;
use App\Http\Controllers\Controller;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

#[Group('Seasons - Admin', description: 'Admin-only season lifecycle, map assignment, polling, scoring, and finalization endpoints.', weight: 50)]
class AdminSeasonController extends Controller
{
    /**
     * List seasons for admins.
     *
     * Returns paginated seasons with creator information.
     *
     * @response AnonymousResourceCollection<SeasonResource>
     */
    public function index(): AnonymousResourceCollection
    {
        $seasons = Season::query()
            ->with(['createdBy'])
            ->latest('id')
            ->paginate(25);

        return SeasonResource::collection($seasons);
    }

    /**
     * Get a season for admins.
     *
     * Returns a season with creator and ordered map assignments.
     */
    public function show(Season $season): SeasonResource
    {
        $season->load([
            'createdBy',
            'maps' => fn ($query) => $query->orderBy('season_maps.order_index')->orderBy('season_maps.id'),
        ]);

        return new SeasonResource($season);
    }

    /**
     * Create a season.
     *
     * Stores a new season and records the authenticated admin as creator.
     */
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

    /**
     * Update a season.
     *
     * Updates season settings and validates the requested lifecycle state.
     */
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

    /**
     * Delete a season.
     *
     * Removes the selected season from the local database.
     */
    public function destroy(Season $season): Response
    {
        $season->delete();

        return response()->noContent();
    }

    /**
     * Finalize a season.
     *
     * Calculates final placement rewards and transitions an ended season to finalized.
     */
    public function finalize(Season $season, SeasonLifecycleService $lifecycleService): JsonResponse
    {
        try {
            $result = $lifecycleService->finalizeSeason($season, (int) request()->user()?->id);
        } catch (\RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json(['data' => $result]);
    }

    /**
     * Update automatic season statuses.
     *
     * Applies scheduled lifecycle transitions based on the current time.
     */
    public function updateStatuses(SeasonLifecycleService $lifecycleService): JsonResponse
    {
        return response()->json(['data' => $lifecycleService->updateAutomaticStatuses()]);
    }
}
