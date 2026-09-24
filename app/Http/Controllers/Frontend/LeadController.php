<?php

namespace App\Http\Controllers\Frontend;

use App\Actions\StoreLead;
use App\Http\Controllers\Controller;
use App\Models\Form;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Form Blade gửi vào đây theo hai cách:
 *   - fetch nhận JSON để không tải lại trang;
 *   - POST thường + redirect làm đường dự phòng khi trình duyệt tắt/lỗi JS.
 *
 * Cùng action với API nên honeypot, chống trùng, mail và webhook y hệt.
 */
class LeadController extends Controller
{
    public function __invoke(Request $request, Form $form, StoreLead $storeLead): RedirectResponse|JsonResponse
    {
        abort_unless($form->is_active, 404);

        $form->load('fields');

        $lead = $storeLead->handle($request, $form);
        $message = $form->success_message ?: 'Đã nhận thông tin, chúng tôi sẽ liên hệ sớm.';

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'data' => ['id' => $lead?->id],
            ], 201);
        }

        // Quay lại đúng trang có form, kèm câu cảm ơn do người nhập tự đặt.
        return back()
            ->with('lead_form_key', $form->key)
            ->with('lead_success', $message);
    }
}
