<?php

namespace App\Domains\Trackmania\Http\Controllers\Public;

use App\Domains\Trackmania\Http\Resources\MapResource;
use App\Domains\Trackmania\Models\Map;
use App\Http\Controllers\Controller;
use Dedoc\Scramble\Attributes\Group;

#[Group('Trackmania - Public', description: 'Public Trackmania club, member, and map endpoints.', weight: 20)]
class PublicMapController extends Controller
{
    /**
     * Get a Trackmania map by UID.
     *
     * Returns imported map metadata for the requested Trackmania map UID.
     *
     * @unauthenticated
     */
    public function show(string $uid): MapResource
    {
        $map = Map::query()->where('uid', $uid)->firstOrFail();

        return new MapResource($map);
    }
}
