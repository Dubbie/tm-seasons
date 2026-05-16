<?php

namespace App\Domains\Trackmania\Http\Controllers\Admin;

use App\Domains\Trackmania\Exceptions\TrackmaniaClientException;
use App\Http\Controllers\Controller;
use App\Domains\Trackmania\Http\Requests\Admin\ImportMapRequest;
use App\Domains\Trackmania\Http\Requests\Admin\UpdateMapRequest;
use App\Domains\Trackmania\Http\Resources\MapResource;
use App\Domains\Trackmania\Models\Map;
use App\Domains\Trackmania\Services\MapImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class AdminMapController extends Controller
{
    public function index()
    {
        $maps = Map::query()->latest('id')->paginate(25);

        return MapResource::collection($maps);
    }

    public function show(Map $map): MapResource
    {
        return new MapResource($map);
    }

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

    public function update(UpdateMapRequest $request, Map $map): MapResource
    {
        $map->fill($request->validated());
        $map->save();

        return new MapResource($map->fresh());
    }

    public function destroy(Map $map): Response
    {
        $map->delete();

        return response()->noContent();
    }
}
