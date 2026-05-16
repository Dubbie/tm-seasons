<?php

namespace App\Domains\Trackmania\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Domains\Trackmania\Http\Resources\MapResource;
use App\Domains\Trackmania\Models\Map;

class PublicMapController extends Controller
{
    public function show(string $uid): MapResource
    {
        $map = Map::query()->where('uid', $uid)->firstOrFail();

        return new MapResource($map);
    }
}
