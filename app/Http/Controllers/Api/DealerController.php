<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\DealerResource;
use App\Support\Catalog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class DealerController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $dealers = Catalog::query('dealer')
            ->with('province')
            ->when($request->integer('province_id'), fn ($q, $id) => $q->where('province_id', $id))
            ->orderBy('name')
            ->get();

        return DealerResource::collection($dealers);
    }
}
