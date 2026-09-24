<?php

namespace App\Http\Controllers\Api;

use App\Support\Catalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Tính lăn bánh theo tỉnh. Chỉ bật khi config('catalog.features.fee_calc').
 */
class FeeCalculatorController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $input = $request->validate([
            'variant_id'  => ['required', 'integer', 'exists:product_variants,id'],
            'province_id' => ['required', 'integer', 'exists:provinces,id'],
        ]);

        $variant = Catalog::query('variant')->findOrFail($input['variant_id']);
        $province = Catalog::query('province')->findOrFail($input['province_id']);

        $price = (float) $variant->price;

        $fees = [
            'registration' => round($price * (float) $province->registration_fee_rate),
            'plate'        => (float) $province->plate_fee,
            'inspection'   => (float) $province->inspection_fee,
            'road'         => (float) $province->road_fee,
            'insurance'    => (float) $province->insurance_fee,
        ];

        return response()->json([
            'data' => [
                'variant'  => ['id' => $variant->id, 'name' => $variant->name, 'price' => $price],
                'province' => ['id' => $province->id, 'name' => $province->name],
                'fees'     => $fees,
                'total'    => $price + array_sum($fees),
            ],
        ]);
    }
}
