<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Resources\MapResource;
use App\Models\Map;

class PublicMapController extends Controller
{
    public function show(string $uid): MapResource
    {
        $map = Map::query()->where('uid', $uid)->firstOrFail();

        return new MapResource($map);
    }
}
