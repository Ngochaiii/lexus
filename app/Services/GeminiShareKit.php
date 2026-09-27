<?php

namespace App\Services;

use App\Models\Post;
use App\Support\Phone;
use App\Support\PostContent;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Bộ bài chia sẻ cho một bài viết: status Facebook, tin nhắn Zalo, câu trả lời
 * diễn đàn (otofun, group xe…). Người thật copy đi đăng — KHÔNG tự đăng hàng
 * loạt: rải link tự động là spam, bị khoá tài khoản và Google phạt.
 *
 * Link do code gắn (mỗi kênh một UTM riêng) để báo cáo biết kênh nào ra khách;
 * Gemini chỉ đặt chỗ {LINK}.
 */
class GeminiShareKit
{
    public const CHANNELS = [
        'facebook' => ['label' => 'Facebook (trang cá nhân / fanpage / group)', 'medium' => 'social'],
        'zalo' => ['label' => 'Zalo (nhắn khách / nhóm Zalo)', 'medium' => 'social'],
        'forum' => ['label' => 'Diễn đàn (otofun, group hỏi đáp xe)', 'medium' => 'referral'],
    ];

    public function __construct(private readonly GeminiClient $gemini) {}

    /** @return array<string, string> kênh => nội dung đã gắn link, kèm 'generated_at' */
    public function generate(Post $post): array
    {
        if ($post->status !== 'published') {
            throw new RuntimeException('Chỉ soạn bài chia sẻ cho bài đã đăng (link phải mở được).');
        }

        $result = $this->gemini->json($this->payload($post), 'bài chia sẻ');
        $kit = [];

        foreach (array_keys(self::CHANNELS) as $channel) {
            $text = trim((string) ($result[$channel] ?? ''));
            if (mb_strlen($text) < 40) {
                throw new RuntimeException('Gemini trả về thiếu nội dung cho '.$channel.'. Vui lòng tạo lại.');
            }
            // Phòng khi Gemini dùng ký hiệu font website/Zalo không hiện đúng.
            $text = preg_replace('/\s*[≈~]\s*/u', ' khoảng ', $text) ?? $text;
            $link = self::link($post, $channel);
            $kit[$channel] = str_contains($text, '{LINK}') ? str_replace('{LINK}', $link, $text) : $text."\n\n".$link;
        }

        $kit['generated_at'] = now()->toDateTimeString();

        return $kit;
    }

    /** Link bài kèm UTM riêng từng kênh — trang Báo cáo và GA4 tách được nguồn. */
    public static function link(Post $post, string $channel): string
    {
        return route('posts.show', $post->slug).'?'.http_build_query([
            'utm_source' => $channel,
            'utm_medium' => self::CHANNELS[$channel]['medium'] ?? 'social',
            'utm_campaign' => Str::limit($post->slug, 60, ''),
        ]);
    }

    private function payload(Post $post): array
    {
        $advisor = catalog_setting('advisor_name') ?: 'chuyên viên tư vấn';
        $site = catalog_setting('site_name', config('app.name'));
        $hotline = filled($h = catalog_setting('hotline')) ? Phone::format($h) : '';
        $body = Str::limit(trim(preg_replace('/\s+/u', ' ', strip_tags(PostContent::fromSections($post->sections ?? []))) ?? ''), 6000);
        $faq = collect($post->sections ?? [])->firstWhere('type', 'faq')['rows'] ?? [];
        $faqText = collect($faq)->map(fn ($r) => '- '.($r['label'] ?? '').' → '.($r['value'] ?? ''))->implode("\n");

        $prompt = <<<PROMPT
        # NHIỆM VỤ
        Soạn 3 nội dung để {$advisor} ({$site}, hotline {$hotline}) tự tay chia sẻ bài viết dưới đây, kéo người đang tìm mua xe về đọc bài. Người đăng là chuyên viên bán hàng thật, nói thật mình làm ở đại lý.

        # BÀI VIẾT
        Tiêu đề: {$post->title}
        Tóm tắt: {$post->excerpt}
        Nội dung: {$body}
        Hỏi đáp:
        {$faqText}

        # YÊU CẦU CHUNG
        - Mọi con số (giá, lăn bánh, thông số) CHỈ lấy từ bài viết; không bịa khuyến mại, quà tặng, lãi suất, "số lượng có hạn", "giá tốt nhất".
        - Chỗ đặt đường link ghi đúng ký hiệu {LINK} (đúng một lần). Không tự viết URL.
        - Tiếng Việt tự nhiên, không viết hoa cả câu, không dùng ký hiệu ≈ hoặc ~ (viết "khoảng").

        # facebook
        Status 80–150 từ cho trang cá nhân/fanpage/group xe: câu mở đầu gây chú ý bằng một con số hoặc câu hỏi khách hay hỏi; 3–4 gạch đầu dòng ý chính (có thể dùng 1 emoji đầu dòng); mời đọc chi tiết tại {LINK}; cuối cùng 3–5 hashtag (#LexusES350h kiểu viết liền, #LexusHaNoi…).

        # zalo
        Tin nhắn 40–70 từ gửi khách đã hỏi xe: chào ngắn, 1–2 câu nêu thông tin khách cần (con số chính), gửi {LINK}, mời gọi lại khi cần. Không hashtag, không emoji quá 1 cái.

        # forum
        Câu trả lời 120–200 từ đăng trong chủ đề hỏi đáp trên diễn đàn ô tô (vd ai đó hỏi "{$post->title}?"): trả lời thẳng câu hỏi bằng số liệu trước, chia sẻ 1–2 kinh nghiệm thực tế, KHÔNG quảng cáo lộ liễu; câu cuối nói rõ mình là tư vấn viên Lexus và ai cần bảng tính chi tiết thì xem {LINK}. Không hashtag, không emoji.
        PROMPT;

        $schema = [
            'type' => 'object',
            'properties' => [
                'facebook' => ['type' => 'string'],
                'zalo' => ['type' => 'string'],
                'forum' => ['type' => 'string'],
            ],
            'required' => ['facebook', 'zalo', 'forum'],
        ];

        return [
            'systemInstruction' => ['parts' => [[
                'text' => 'Bạn viết nội dung mạng xã hội cho chuyên viên bán xe sang: thật, hữu ích, đúng số liệu, không câu kéo rẻ tiền.',
            ]]],
            'contents' => [['role' => 'user', 'parts' => [['text' => $prompt]]]],
            'generationConfig' => [
                'temperature' => 0.8,
                'maxOutputTokens' => (int) config('services.gemini.max_output_tokens', 16384),
                'responseFormat' => ['text' => ['mimeType' => 'APPLICATION_JSON', 'schema' => $schema]],
            ],
        ];
    }
}
