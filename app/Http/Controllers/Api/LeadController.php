<?php

namespace App\Http\Controllers\Api;

use App\Actions\StoreLead;
use App\Support\Catalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LeadController extends Controller
{
    public function store(Request $request, StoreLead $storeLead): JsonResponse
    {
        $request->validate(['form' => ['required', 'string']]);

        $form = Catalog::query('form')
            ->with('fields')
            ->where('key', $request->string('form'))
            ->where('is_active', true)
            ->firstOrFail();

        // Validate, honeypot và chống trùng nằm trong action — dùng chung với
        // form Blade để hai cửa vào không lệch luật.
        $lead = $storeLead->handle($request, $form);

        return response()->json([
            'message' => $form->success_message ?: 'Đã nhận thông tin, chúng tôi sẽ liên hệ sớm.',
            'data'    => ['id' => $lead?->id],
        ], 201);
    }
}
