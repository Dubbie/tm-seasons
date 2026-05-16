<?php

namespace App\Domains\Trackmania\Http\Controllers\Admin;

use App\Domains\Trackmania\Exceptions\TrackmaniaClientException;
use App\Domains\Trackmania\Http\Requests\Admin\ImportMapRequest;
use App\Domains\Trackmania\Http\Requests\Admin\UpdateMapRequest;
use App\Domains\Trackmania\Http\Resources\MapResource;
use App\Domains\Trackmania\Models\Map;
use App\Domains\Trackmania\Services\MapImportService;
use App\Http\Controllers\Controller;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

#[Group('Trackmania - Admin', description: 'Admin-only Trackmania club synchronization and management endpoints.', weight: 30)]
class AdminMapController extends Controller
{
    /**
     * List maps for admins.
     *
     * Returns paginated imported maps ordered by most recently imported first.
     *
     * @response AnonymousResourceCollection<MapResource>
     */
    public function index(): AnonymousResourceCollection
    {
        $maps = Map::query()->latest('id')->paginate(25);

        return MapResource::collection($maps);
    }

    /**
     * Get a map for admins.
     *
     * Returns the selected imported map and admin-visible metadata.
     */
    public function show(Map $map): MapResource
    {
        return new MapResource($map);
    }

    /**
     * Import a Trackmania map.
     *
     * Fetches map metadata from Trackmania services by UID and stores or updates the local map.
     */
    public function import(ImportMapRequest $request, MapImportService $mapImportService): MapResource|JsonResponse
    {
        try {
            $map = $mapImportService->importByUid($request->string('uid')->toString());
        } catch (TrackmaniaClientException $exception) {
            $status = str_contains(strtolower($exception->getMessage()), 'not found') ? 404 : 422;

            return response()->json([
                'message' => 'Unable to import map from Trackmania services.',
                'error' => $exception->getMessage(),
            ], $status);
        } catch (\Throwable $exception) {
            return response()->json([
                'message' => 'Trackmania API failure while importing map.',
            ], 503);
        }

        return new MapResource($map);
    }

    /**
     * Update map metadata.
     *
     * Updates editable fields for an imported Trackmania map.
     */
    public function update(UpdateMapRequest $request, Map $map): MapResource
    {
        $map->fill($request->validated());
        $map->save();

        return new MapResource($map->fresh());
    }

    /**
     * Delete a map.
     *
     * Removes an imported map from the local database.
     */
    public function destroy(Map $map): Response
    {
        $map->delete();

        return response()->noContent();
    }
}
