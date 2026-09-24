<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\MenuItemResource;
use App\Support\Catalog;
use Illuminate\Http\JsonResponse;

class MenuController extends Controller
{
    public function show(string $key): JsonResponse
    {
        $menu = Catalog::query('menu')->with('rootItems')->where('key', $key)->firstOrFail();

        return response()->json([
            'data' => [
                'key'   => $menu->key,
                'name'  => $menu->name,
                'items' => MenuItemResource::collection($menu->rootItems)->resolve(),
            ],
        ]);
    }
}
