<?php

namespace App\Domains\Trackmania\Http\Controllers\Admin;

use App\Domains\Trackmania\Exceptions\TrackmaniaClientException;
use App\Domains\Trackmania\Http\Requests\Admin\SyncClubRequest;
use App\Domains\Trackmania\Http\Resources\TrackmaniaClubResource;
use App\Domains\Trackmania\Models\TrackmaniaClub;
use App\Domains\Trackmania\Services\TrackmaniaClubSyncService;
use App\Http\Controllers\Controller;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

#[Group('Trackmania - Admin', description: 'Admin-only Trackmania club synchronization and management endpoints.', weight: 30)]
class AdminClubController extends Controller
{
    /**
     * Get the primary club.
     *
     * Returns the configured primary club, falling back to the oldest imported club when needed.
     */
    public function primary(): TrackmaniaClubResource|JsonResponse
    {
        $club = $this->resolvePrimaryClub();

        if (! $club) {
            return response()->json([
                'message' => 'No primary club configured yet.',
            ], 404);
        }

        return new TrackmaniaClubResource($club);
    }

    /**
     * List admin Trackmania clubs.
     *
     * Returns paginated clubs ordered by most recently imported first.
     *
     * @response AnonymousResourceCollection<TrackmaniaClubResource>
     */
    public function index(): AnonymousResourceCollection
    {
        return TrackmaniaClubResource::collection(
            TrackmaniaClub::query()->latest('id')->paginate(25)
        );
    }

    /**
     * Get an admin Trackmania club.
     *
     * Returns the selected club and admin-visible metadata.
     */
    public function show(TrackmaniaClub $club): TrackmaniaClubResource
    {
        return new TrackmaniaClubResource($club);
    }

    /**
     * Sync a Trackmania club.
     *
     * Imports or updates club members from Trackmania services for the provided club ID.
     */
    public function sync(SyncClubRequest $request, TrackmaniaClubSyncService $syncService): JsonResponse
    {
        $clubId = $request->string('club_id')->toString();
        if ($clubId === '') {
            return response()->json([
                'message' => 'The club_id field is required for this endpoint.',
            ], 422);
        }

        try {
            $summary = $syncService->syncClub($clubId);
        } catch (TrackmaniaClientException $exception) {
            return response()->json([
                'message' => 'Trackmania API failure while syncing club.',
                'error' => $exception->getMessage(),
            ], 422);
        } catch (\Throwable) {
            return response()->json([
                'message' => 'Unexpected failure while syncing club.',
            ], 503);
        }

        return response()->json([
            'club' => new TrackmaniaClubResource($summary['club']),
            'imported' => $summary['imported'],
            'updated' => $summary['updated'],
            'deactivated' => $summary['deactivated'],
            'enriched' => $summary['enriched'] ?? 0,
            'total_members' => $summary['total_members'],
        ]);
    }

    /**
     * Sync the primary Trackmania club.
     *
     * Uses the supplied club ID when present, otherwise syncs the configured primary club.
     */
    public function syncPrimary(SyncClubRequest $request, TrackmaniaClubSyncService $syncService): JsonResponse
    {
        $clubId = $request->string('club_id')->toString();

        if ($clubId === '') {
            $primary = $this->resolvePrimaryClub();
            if (! $primary) {
                return response()->json([
                    'message' => 'No primary club configured yet.',
                ], 404);
            }

            $clubId = $primary->club_id;
        }

        try {
            $summary = $syncService->syncClub($clubId);
        } catch (TrackmaniaClientException $exception) {
            return response()->json([
                'message' => 'Trackmania API failure while syncing club.',
                'error' => $exception->getMessage(),
            ], 422);
        } catch (\Throwable) {
            return response()->json([
                'message' => 'Unexpected failure while syncing club.',
            ], 503);
        }

        return response()->json([
            'club' => new TrackmaniaClubResource($summary['club']),
            'imported' => $summary['imported'],
            'updated' => $summary['updated'],
            'deactivated' => $summary['deactivated'],
            'enriched' => $summary['enriched'] ?? 0,
            'total_members' => $summary['total_members'],
        ]);
    }

    private function resolvePrimaryClub(): ?TrackmaniaClub
    {
        $primary = TrackmaniaClub::primary();
        if ($primary) {
            return $primary;
        }

        $fallback = TrackmaniaClub::query()->oldest('id')->first();
        if (! $fallback) {
            return null;
        }

        $fallback->is_primary = true;
        $fallback->save();

        return $fallback;
    }
}
