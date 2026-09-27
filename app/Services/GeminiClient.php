<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Sleep;
use Illuminate\Support\Str;
use JsonException;
use RuntimeException;

/**
 * Gọi Gemini, nhận về JSON theo schema — dùng chung cho viết bài, lên kế
 * hoạch chủ đề và soạn bài chia sẻ.
 *
 * Google trả 503 "high demand" theo từng đợt vài giây trên các model flash
 * mới. Vì vậy thử hết danh sách model một lượt, nếu vẫn quá tải thì nghỉ
 * 2s, 4s… rồi lặp lại toàn bộ danh sách thay vì chỉ gọi mỗi model một lần.
 * Model trả 429 (hết 20 request/ngày của Free Tier) bị loại khỏi các đợt sau.
 */
class GeminiClient
{
    private const ENDPOINT = 'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent';

    /** Báo lỗi sớm (trước khi đọc ảnh, gọi mạng) khi chưa cấu hình. */
    public function ensureConfigured(): void
    {
        if (trim((string) config('services.gemini.key')) === '') {
            throw new RuntimeException('Gemini chưa được cấu hình. Hãy thêm GEMINI_API_KEY vào file .env.');
        }

        $this->models();
    }

    /**
     * @param  array<string, mixed>  $payload  Thân request generateContent (đã có responseFormat JSON).
     * @param  string  $task  Tên việc cho thông báo lỗi, vd "bài viết", "kế hoạch chủ đề".
     * @return array<mixed>
     */
    public function json(array $payload, string $task = 'bài viết'): array
    {
        $this->ensureConfigured();

        $key = trim((string) config('services.gemini.key'));
        $models = $this->models();
        $rounds = max(1, (int) config('services.gemini.retry_rounds', 3));
        $response = null;
        $usedModel = $models[0];
        $exhausted = [];
        $timedOut = false;

        for ($round = 1; $round <= $rounds; $round++) {
            $remaining = array_values(array_diff($models, $exhausted));

            if ($remaining === []) {
                break;
            }

            if ($round > 1) {
                Sleep::for(2 ** ($round - 1))->seconds();
            }

            foreach ($remaining as $candidateModel) {
                $usedModel = $candidateModel;

                try {
                    $response = Http::connectTimeout(10)
                        ->timeout((int) config('services.gemini.timeout', 90))
                        ->acceptJson()
                        ->withHeaders(['x-goog-api-key' => $key])
                        ->post(sprintf(self::ENDPOINT, $candidateModel), $payload);
                } catch (ConnectionException $exception) {
                    // Hết thời gian chờ ở một model (bài dài, model đang chậm) thì thử
                    // model kế tiếp thay vì bỏ cả lượt — chạy trong queue nên chờ được.
                    Log::warning('Gemini: không kết nối được', ['model' => $candidateModel, 'error' => $exception->getMessage()]);
                    $timedOut = true;

                    continue;
                }

                if (! $response->successful()) {
                    Log::warning('Gemini: HTTP lỗi', [
                        'model' => $candidateModel,
                        'round' => $round,
                        'status' => $response->status(),
                        'error' => Str::limit((string) $response->json('error.message'), 300),
                    ]);
                }

                if ($response->status() === 429) {
                    $exhausted[] = $candidateModel;

                    continue;
                }

                if (! in_array($response->status(), [500, 502, 503, 504], true)) {
                    break 2;
                }
            }
        }

        if ($response === null) {
            throw new RuntimeException($timedOut
                ? 'Gemini phản hồi quá chậm ở mọi model (đang quá tải). Vui lòng thử lại sau ít phút.'
                : "Không có model Gemini hợp lệ để tạo {$task}.");
        }

        if ($response->failed()) {
            if ($response->status() === 429) {
                throw new RuntimeException(
                    'Gemini đang hết hạn mức của dự án API (HTTP 429). Gói miễn phí chỉ cho 20 lượt/ngày với mỗi model; '
                    .'hãy thử lại sau vài phút hoặc sang ngày mai, hoặc bật Billing trong Google AI Studio để nâng hạn mức.',
                );
            }

            if (in_array($response->status(), [500, 502, 503, 504], true)) {
                throw new RuntimeException(
                    'Gemini đang quá tải hoặc tạm thời gián đoạn (HTTP '.$response->status().'). '
                    .'Hệ thống đã thử lại nhưng chưa thành công; vui lòng thử lại sau ít phút.',
                );
            }

            $detail = Str::limit((string) $response->json('error.message'), 180);

            throw new RuntimeException(
                "Gemini không tạo được {$task} (HTTP ".$response->status().').'
                .($detail !== '' ? ' '.$detail : ''),
            );
        }

        $text = collect($response->json('candidates.0.content.parts', []))
            ->pluck('text')
            ->filter()
            ->implode('');

        $context = [
            'task' => $task,
            'model' => $usedModel,
            'finish_reason' => $response->json('candidates.0.finishReason'),
            'usage' => $response->json('usageMetadata'),
            'text' => Str::limit($text, 500),
        ];

        // Gemini 3.x tính cả token "suy nghĩ" (~2.000–2.500) vào trần này,
        // nên trần thấp sẽ cắt cụt JSON giữa chừng và báo lỗi định dạng sai lệch.
        if ($response->json('candidates.0.finishReason') === 'MAX_TOKENS') {
            Log::warning('Gemini: bị cắt vì hết trần token', $context);

            throw new RuntimeException(
                ucfirst($task).' bị cắt vì vượt trần token đầu ra của Gemini. '
                .'Hãy tăng GEMINI_MAX_OUTPUT_TOKENS trong file .env (khuyến nghị 8192 trở lên) rồi tạo lại.',
            );
        }

        if ($text === '') {
            Log::warning('Gemini: không trả về nội dung', $context + ['body' => Str::limit($response->body(), 800)]);

            throw new RuntimeException('Gemini không trả về nội dung. Vui lòng thử lại.');
        }

        try {
            $result = json_decode($text, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            Log::warning('Gemini: JSON không hợp lệ', $context);

            throw new RuntimeException("Gemini trả về dữ liệu không đúng định dạng. Vui lòng tạo lại {$task}.");
        }

        if (! is_array($result)) {
            Log::warning('Gemini: JSON không phải object', $context);

            throw new RuntimeException("Gemini trả về nội dung không đúng cấu trúc. Vui lòng tạo lại {$task}.");
        }

        return $result;
    }

    /** @return array<int, string> */
    private function models(): array
    {
        $primary = trim((string) config('services.gemini.model', 'gemini-3.8-flash'));

        if (! preg_match('/^[a-zA-Z0-9._-]+$/', $primary)) {
            throw new RuntimeException('Tên model Gemini trong cấu hình không hợp lệ.');
        }

        $fallbacks = config('services.gemini.fallback_models', []);
        $fallbacks = is_string($fallbacks) ? explode(',', $fallbacks) : (array) $fallbacks;

        $models = collect([$primary, ...$fallbacks])
            ->map(fn (mixed $model): string => trim((string) $model))
            ->filter()
            ->unique()
            ->values()
            ->all();

        foreach ($models as $model) {
            if (! preg_match('/^[a-zA-Z0-9._-]+$/', $model)) {
                throw new RuntimeException('Tên model Gemini dự phòng trong cấu hình không hợp lệ.');
            }
        }

        return $models;
    }
}
