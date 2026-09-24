<?php

namespace App\Actions;

use App\Events\LeadReceived;
use App\Jobs\ResolveLeadLocation;
use App\Support\Phone;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

/**
 * Nhận một lead. Dùng chung cho hai cửa vào:
 *   - API JSON  (POST /api/v1/leads)         — cho SPA/landing page ngoài
 *   - Form Blade (POST /gui-form/{form})     — trang khách xem, không cần JS
 *
 * Luật validate, honeypot và chống trùng nằm ở đây, không nằm ở controller,
 * để hai cửa vào không bao giờ lệch nhau.
 */
class StoreLead
{
    /**
     * @return ?Model  lead vừa tạo · lead trùng gần đây · null nếu là bot
     */
    public function handle(Request $request, Model $form): ?Model
    {
        // Bẫy bot: ô ẩn người thật để trống. Trả null, phía gọi vẫn báo thành
        // công để bot không dò được là đã bị chặn.
        if (filled($request->input(config('catalog.leads.honeypot', 'website')))) {
            return null;
        }

        // Đưa mọi ô kiểu "tel" về dạng 0xxxxxxxxx TRƯỚC khi validate, để
        // "098 765 4321" hay "+84987654321" đều qua rule VietnamPhone và lưu
        // cùng một dạng — dedupe theo số mới bắt được.
        foreach ($form->fields->where('type', 'tel') as $field) {
            if ($request->has($field->key)) {
                $request->merge([$field->key => Phone::normalize($request->input($field->key))]);
            }
        }

        // Luật validate dựng từ form_fields, không hardcode. ValidationException
        // tự trả 422 JSON cho API và redirect kèm lỗi cho form Blade.
        //
        // Bag đặt tên theo $form->key: khi trang có nhiều form dùng chung tên
        // trường (VD "name", "phone" ở cả "Đặt cọc" lẫn "Đăng ký lái thử"),
        // lỗi của form này không được tràn sang @error() của form khác. Chỉ
        // ảnh hưởng cách lỗi được gắn vào session cho Blade — JSON trả về
        // cho API vẫn y hệt vì response không phụ thuộc tên bag.
        $data = $request->validateWithBag(
            $form->key,
            $form->validationRules(),
            [],
            $form->validationAttributes(),
        );

        if ($existing = $this->recentDuplicate($form, Arr::get($data, 'phone'))) {
            return $existing;
        }

        // Phiên bản (tùy chọn): chỉ nhận khi thuộc đúng dòng xe khách chọn —
        // đổi dòng xe trong form mà ô ẩn còn phiên bản cũ thì bỏ qua lặng lẽ.
        $productId = $request->integer('product_id') ?: null;
        $variant = $this->variant($request->integer('variant_id') ?: null, $productId);

        if ($variant) {
            $data['variant'] = $variant->name;   // hiện luôn trong mail/webhook
        }

        $lead = $form->leads()->create([
            'data'       => $data,
            'name'       => Arr::get($data, 'name'),
            'phone'      => Arr::get($data, 'phone'),
            'email'      => Arr::get($data, 'email'),
            'product_id' => $productId ?? $variant?->product_id,
            'product_variant_id' => $variant?->getKey(),
            'utm'        => $request->collect()->only([
                'utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content',
            ])->filter()->all() ?: null,
            'referrer'   => $request->header('referer'),
            'ip'         => $request->ip(),
        ]);

        // Mail cho notify_emails + bắn webhook_url do listener lo (queued).
        LeadReceived::dispatch($lead);

        // Tra IP → tỉnh/thành chạy nền, không bắt khách chờ API ngoài.
        ResolveLeadLocation::dispatch($lead);

        return $lead;
    }

    /** Phiên bản hợp lệ: tồn tại, thuộc xe đã đăng và (nếu có) đúng dòng xe đã chọn. */
    protected function variant(?int $id, ?int $productId): ?Model
    {
        if (! $id) {
            return null;
        }

        $variant = \App\Models\ProductVariant::query()->with('product')->find($id);

        if (! $variant || ! $variant->product || $variant->product->status !== 'published') {
            return null;
        }

        return $productId === null || $variant->product_id === $productId ? $variant : null;
    }

    /** Cùng form + cùng số trong cửa sổ ngắn thì trả lại lead cũ, không tạo mới. */
    protected function recentDuplicate(Model $form, ?string $phone): ?Model
    {
        $minutes = (int) config('catalog.leads.dedupe_minutes', 0);

        if ($minutes <= 0 || blank($phone)) {
            return null;
        }

        return $form->leads()
            ->where('phone', $phone)
            ->where('created_at', '>=', now()->subMinutes($minutes))
            ->latest('id')
            ->first();
    }
}
