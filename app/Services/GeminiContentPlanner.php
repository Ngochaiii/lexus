<?php

namespace App\Services;

use App\Models\ContentIdea;
use App\Models\Page;
use App\Models\Post;
use App\Models\PostCategory;
use App\Models\Product;
use App\Support\ArticleContext;
use App\Support\Catalog;
use App\Support\Insights;
use App\Support\PostContent;
use App\Support\Url;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Giao cho Gemini việc "kéo từ khoá" một cách có kế hoạch:
 *
 *   1. propose()   — đề xuất chủ đề: từ khoá chính, ý định tìm kiếm, trang cần
 *                    đẩy (link nội bộ), tránh trùng bài đã có/đã đề xuất.
 *   2. writeDraft() — viết chủ đề thành bài NHÁP (GeminiArticleWriter), bắt buộc
 *                    link về trang cần đẩy. Người đọc lại số liệu rồi mới đăng —
 *                    Google phạt nội dung AI đăng hàng loạt không ai biên tập.
 */
class GeminiContentPlanner
{
    /** Nhóm chủ đề → chuyên mục bài viết (slug) đang có trên website. */
    private const CATEGORY_BY_CLUSTER = [
        'gia' => 'bang-gia-mua-xe',
        'tra-gop' => 'bang-gia-mua-xe',
        'so-sanh' => 'tu-van-chon-xe',
        'kinh-nghiem' => 'tu-van-chon-xe',
        'dia-phuong' => 'lexus-thang-long',
    ];

    public function __construct(
        private readonly GeminiClient $gemini,
        private readonly GeminiArticleWriter $writer,
    ) {}

    /** @return Collection<int, ContentIdea> các chủ đề mới đã lưu */
    public function propose(int $count = 8, ?string $focus = null): Collection
    {
        $count = max(3, min(12, $count));
        $targets = $this->targetUrls();
        $result = $this->gemini->json($this->ideasPayload($count, $focus, $targets), 'kế hoạch chủ đề');

        $seen = $this->coveredKeywords();
        $products = Catalog::query('product')->published()->get();
        $saved = collect();

        foreach (array_slice((array) ($result['ideas'] ?? []), 0, $count) as $row) {
            $title = trim((string) ($row['title'] ?? ''));
            $keyword = trim((string) ($row['primary_keyword'] ?? ''));
            if ($title === '' || $keyword === '' || isset($seen[$norm = self::norm($keyword)])) {
                continue;
            }
            $seen[$norm] = true;

            $target = trim((string) ($row['target_url'] ?? ''));
            if (! array_key_exists($target, $targets)) {
                // Gemini chọn đường dẫn không có thật → trang dòng xe được nhắc, không thì bảng giá.
                $product = ArticleContext::focusProducts($products, $title.' '.$keyword)->first();
                $target = $product ? route('products.show', $product->slug, false) : $this->fallbackTarget($targets);
            }

            $cluster = (string) ($row['cluster'] ?? '');
            $saved->push(ContentIdea::create([
                'title' => Str::limit($title, 190, ''),
                'primary_keyword' => Str::limit(Str::lower($keyword), 190, ''),
                'secondary_keywords' => collect($row['secondary_keywords'] ?? [])->filter(fn ($k) => is_string($k) && filled($k))
                    ->map(fn ($k) => trim($k))->take(10)->values()->all(),
                'search_intent' => Str::limit(trim((string) ($row['search_intent'] ?? '')), 490, ''),
                'angle' => trim((string) ($row['angle'] ?? '')),
                'target_url' => $target,
                'cluster' => array_key_exists($cluster, ContentIdea::CLUSTERS) ? $cluster : null,
                'priority' => max(1, min(3, (int) ($row['priority'] ?? 2))),
                'stage' => array_key_exists($stage = (string) ($row['stage'] ?? ''), ContentIdea::STAGES) ? $stage : null,
                'ai_prompts' => collect($row['ai_prompts'] ?? [])->filter(fn ($q) => is_string($q) && filled(trim($q)))
                    ->map(fn ($q) => Str::limit(trim($q), 250, ''))->take(5)->values()->all(),
            ]));
        }

        if ($saved->isEmpty()) {
            throw new RuntimeException('Gemini chưa đề xuất được chủ đề mới (các chủ đề trả về trùng bài đã có). Thử lại với "Hướng tập trung" khác.');
        }

        return $saved;
    }

    /** Viết chủ đề thành bài nháp; trả về bài vừa tạo. */
    public function writeDraft(ContentIdea $idea): Post
    {
        $cover = $this->coverFor($idea);
        if ($cover === null) {
            throw new RuntimeException('Không tìm được ảnh bìa cho chủ đề này (dòng xe chưa có ảnh hero, chưa có ảnh chia sẻ mặc định).');
        }

        $article = $this->writer->generate($idea->title, $cover, $this->instructionsFor($idea));

        $faqText = PostContent::faqToText([[
            'type' => 'faq',
            'rows' => collect($article['faq'])->map(fn (array $q): array => ['label' => $q['question'], 'value' => $q['answer']])->all(),
        ]]);

        $post = new Post([
            'title' => $idea->title,
            'excerpt' => $article['excerpt'],
            'cover' => $cover,
            'status' => 'draft',
            'post_category_id' => PostCategory::query()->where('slug', self::CATEGORY_BY_CLUSTER[$idea->cluster] ?? '')->value('id'),
            'sections' => PostContent::withFaq(PostContent::intoSections($article['article_html']), $faqText),
            'seo' => [
                'title' => $article['seo_title'],
                'description' => $article['meta_description'],
                'keywords' => implode(', ', $article['keywords']),
            ],
        ]);
        $post->slug = $post->generateUniqueSlug($article['slug'] ?: $idea->title);
        $post->save();

        return $post;
    }

    /** Cách viết theo từng giai đoạn hành trình khách (GEO). */
    private const STAGE_GUIDE = [
        'tim-hieu' => 'khách đang tìm hiểu kiến thức, chưa chọn xe. Giải thích dễ hiểu, đúng sự thật, có ví dụ số liệu từ hồ sơ; dẫn sang dòng xe liên quan; lời mời liên hệ nhẹ nhàng ở cuối.',
        'lua-chon' => 'khách đang so các dòng xe, phiên bản Lexus đang bán hoặc cách mua (trả thẳng, trả góp). Có bảng so sánh theo tiêu chí khách quan tâm và kết luận rõ "bản nào hợp với ai". Không xếp hạng hay so sánh đại lý, sale khác; không so với hãng khác bằng số liệu không có trong hồ sơ.',
        'quyet-dinh' => 'khách đã gần mua: cần con số đầy đủ (giá, lăn bánh, trả trước), các bước làm, thời gian, và đường liên hệ rõ ràng (nhận báo giá, lái thử, gọi chuyên viên).',
    ];

    /** Chỉ dẫn gửi kèm tiêu đề cho GeminiArticleWriter. */
    public function instructionsFor(ContentIdea $idea): string
    {
        $prompts = collect($idea->ai_prompts ?? [])->filter()->values();

        return implode("\n", array_filter([
            'Từ khoá chính BẮT BUỘC dùng làm primary_keyword: "'.$idea->primary_keyword.'".',
            filled($idea->secondary_keywords) ? 'Từ khoá phụ nên dùng tự nhiên: '.implode(', ', $idea->secondary_keywords).'.' : null,
            filled($idea->search_intent) ? 'Ý định tìm kiếm: '.$idea->search_intent : null,
            filled($idea->angle) ? 'Bài phải trả lời được: '.$idea->angle : null,
            isset(self::STAGE_GUIDE[$idea->stage])
                ? 'Giai đoạn: '.ContentIdea::STAGES[$idea->stage].' — '.self::STAGE_GUIDE[$idea->stage]
                : null,
            $prompts->isNotEmpty()
                ? "Câu khách hỏi ChatGPT/Gemini mà bài PHẢI trả lời trực tiếp (dùng ít nhất 2 câu làm đề mục h2 dạng câu hỏi, câu đầu của mục đó là câu trả lời):\n"
                    .$prompts->map(fn ($q) => '- '.$q)->implode("\n")
                : null,
            filled($idea->target_url)
                ? 'TRANG CẦN ĐẨY: '.$idea->target_url.' — chèn ít nhất 2 link tới đúng đường dẫn này (1 link trong 3 đoạn đầu), anchor text chứa từ khoá hoặc tên xe/phiên bản.'
                : null,
        ]));
    }

    /**
     * Đường dẫn nội bộ hợp lệ để làm "trang cần đẩy": dòng xe, phiên bản,
     * trang tĩnh chính, báo giá, lái thử.
     *
     * @return array<string, string> đường dẫn => mô tả
     */
    public function targetUrls(): array
    {
        $urls = [];
        $products = Catalog::query('product')->published()->with(['variants' => fn ($q) => $q->orderBy('sort')])->orderBy('sort')->get();

        foreach ($products as $p) {
            $urls[route('products.show', $p->slug, false)] = $p->name.' (trang dòng xe)';
            foreach ($p->variants->filter(fn ($v) => filled($v->slug)) as $v) {
                $urls[Url::variant($p->slug, $v->slug)] = $v->name.' (trang phiên bản)';
            }
        }

        Page::query()->published()->whereIn('slug', ['bang-gia', 'tai-chinh', 'uu-dai', 'showroom', 'dich-vu'])->get(['slug', 'title'])
            ->each(function (Page $pg) use (&$urls) {
                $urls[route('pages.show', $pg->slug, false)] = $pg->title;
            });

        $urls[route('quote', [], false)] = 'Nhận báo giá lăn bánh';
        $urls[route('booking', [], false)] = 'Đăng ký lái thử';

        return $urls;
    }

    /** @param array<string, string> $targets */
    private function ideasPayload(int $count, ?string $focus, array $targets): array
    {
        $site = catalog_setting('site_name', config('app.name'));
        $facts = ArticleContext::build('Kế hoạch nội dung '.$focus, $focus);
        $focusLine = filled($focus) ? 'Hướng tập trung đợt này: '.trim($focus) : 'Hướng tập trung: tự chọn theo cơ hội tốt nhất.';

        $covered = Post::query()->latest('id')->take(80)->get(['title', 'seo', 'status'])
            ->map(fn (Post $p) => '- '.$p->title.(filled($k = data_get($p->seo, 'keywords')) ? ' [từ khoá: '.Str::limit((string) $k, 90).']' : ''))
            ->merge(ContentIdea::query()->latest('id')->take(80)->get(['title', 'primary_keyword', 'status'])
                ->map(fn (ContentIdea $i) => '- '.$i->title.' [từ khoá: '.$i->primary_keyword.']'.($i->status === 'dismissed' ? ' (đã loại, không đề xuất lại)' : '')))
            ->implode("\n") ?: '- (chưa có bài nào)';

        $signals = collect((new Insights(90))->byVariant())->take(8)
            ->map(fn ($r) => '- '.$r['label'].': '.$r['leads'].' khách hỏi')->implode("\n");
        $clicks = collect((new Insights(90))->contactClicks())->take(6)
            ->map(fn ($r) => '- '.$r['path'].': '.$r['total'].' lượt bấm Gọi/Zalo')->implode("\n");

        $targetList = collect($targets)->map(fn ($label, $url) => '- '.$url.' — '.$label)->implode("\n");
        $clusters = collect(ContentIdea::CLUSTERS)->map(fn ($label, $key) => $key.' = '.$label)->implode('; ');

        $prompt = <<<PROMPT
        # NHIỆM VỤ
        Bạn lập kế hoạch nội dung SEO cho {$site} — website tư vấn của chuyên viên bán xe tại đại lý Lexus chính hãng ở Hà Nội. Mục tiêu: có khách tìm trên Google/AI → đọc bài → bấm sang trang xe/báo giá → để lại số điện thoại.
        Đề xuất đúng {$count} chủ đề bài viết MỚI. {$focusLine}

        # DỮ LIỆU THẬT CỦA WEBSITE
        {$facts}

        # TÍN HIỆU TỪ KHÁCH THẬT (90 ngày)
        Phiên bản khách hỏi nhiều:
        {$signals}
        Trang khách bấm Gọi/Zalo nhiều:
        {$clicks}

        # BÀI ĐÃ CÓ / ĐÃ ĐỀ XUẤT — KHÔNG đề xuất chủ đề hoặc từ khoá chính trùng ý các bài này
        {$covered}

        # TRANG CẦN ĐẨY (target_url CHỈ được chọn trong danh sách này)
        {$targetList}

        # GIAI ĐOẠN HÀNH TRÌNH KHÁCH (GEO)
        Khách không chỉ gõ Google mà còn hỏi thẳng ChatGPT, Gemini — mỗi giai đoạn hỏi khác nhau. Mỗi chủ đề gắn đúng một stage:
        - tim-hieu (Tìm hiểu): hỏi kiến thức trước khi chọn xe — "xe hybrid Lexus có phải cắm sạc không", "lăn bánh gồm những khoản gì", "bảo hành pin hybrid Lexus bao lâu".
        - lua-chon (Lựa chọn): so dòng xe, phiên bản Lexus đang bán, cách mua — "nên mua ES 350h hay RX 350h cho gia đình", "ES 350h Premium và Luxury khác gì", "trả góp hay trả thẳng". Ở website này "lựa chọn" là chọn XE và CÁCH MUA: không xếp hạng đại lý, sale khác, không viết "top đại lý/sale uy tín", không so với hãng khác.
        - quyet-dinh (Quyết định): gần mua — "giá lăn bánh ES 350h Premium Hà Nội", "trả trước bao nhiêu để mua RX 350h", "thủ tục mua Lexus trả góp", "lái thử Lexus ở Hà Nội cần gì".
        Trong {$count} chủ đề: mỗi giai đoạn ít nhất một chủ đề (nếu {$count} ≥ 3); khoảng một nửa là quyet-dinh vì gần ra khách nhất.
        ai_prompts: 3–5 câu hỏi HỘI THOẠI đầy đủ mà khách thật sẽ gõ cho ChatGPT/Gemini về chủ đề này, có ngữ cảnh (ngân sách, gia đình mấy người, đi phố hay đi tỉnh, ở Hà Nội) — khác với từ khoá ngắn gõ Google. Không đặt câu hỏi về đại lý hay sale khác.

        # CÁCH CHỌN CHỦ ĐỀ
        1. Ưu tiên từ khoá có Ý ĐỊNH MUA gần: "giá lăn bánh…", "… trả góp", "so sánh A và B", "có nên mua…", "… bản nào đáng mua", "… hà nội". Tránh chủ đề tin tức chung chung, lịch sử hãng, chủ đề không dẫn tới mua xe.
        2. Mỗi chủ đề nhắm MỘT từ khoá chính 3–7 chữ (viết thường, đúng cách người Việt gõ) và phải khác hẳn nhau — không để 2 bài tranh cùng một từ khoá.
        3. Phủ đều các dòng xe/phiên bản đang bán; dòng khách hỏi nhiều thì thêm bài; phiên bản chưa có bài riêng thì ưu tiên.
        4. target_url: trang mà bài sẽ dẫn khách sang — thường là trang phiên bản/dòng xe được nhắc; bài về chi phí/trả góp có thể dẫn /bao-gia hoặc trang tài chính.
        5. angle: 2–3 câu nêu bài phải trả lời ý gì, dùng số liệu nào trong dữ liệu thật, khác gì bài của đối thủ. Không hứa khuyến mại, không bịa số.
        6. priority: 1 = viết ngay (ý định mua rõ, cạnh tranh vừa), 2 = nên viết, 3 = để sau.
        7. cluster: một trong {$clusters}.
        8. title: tiêu đề tiếng Việt tự nhiên 50–75 ký tự, chứa từ khoá chính (viết hoa đúng tên riêng Lexus, ES 350h, Hà Nội), có yếu tố gợi nhấp (con số, năm 2026, "chi tiết từng phiên bản").
        PROMPT;

        $schema = [
            'type' => 'object',
            'properties' => [
                'ideas' => [
                    'type' => 'array',
                    'minItems' => 1,
                    'maxItems' => $count,
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'title' => ['type' => 'string'],
                            'primary_keyword' => ['type' => 'string'],
                            'secondary_keywords' => ['type' => 'array', 'items' => ['type' => 'string'], 'maxItems' => 10],
                            'search_intent' => ['type' => 'string'],
                            'angle' => ['type' => 'string'],
                            'target_url' => ['type' => 'string'],
                            'cluster' => ['type' => 'string', 'enum' => array_keys(ContentIdea::CLUSTERS)],
                            'priority' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 3],
                            'stage' => ['type' => 'string', 'enum' => array_keys(ContentIdea::STAGES)],
                            'ai_prompts' => ['type' => 'array', 'items' => ['type' => 'string'], 'minItems' => 3, 'maxItems' => 5],
                        ],
                        'required' => ['title', 'primary_keyword', 'secondary_keywords', 'search_intent', 'angle', 'target_url', 'cluster', 'priority', 'stage', 'ai_prompts'],
                    ],
                ],
            ],
            'required' => ['ideas'],
        ];

        return [
            'systemInstruction' => ['parts' => [[
                'text' => 'Bạn là chuyên gia SEO tiếng Việt cho ngành ô tô hạng sang. Chọn chủ đề mang lại khách mua xe thật, không chạy theo lượt xem. Chỉ dùng số liệu và đường dẫn trong dữ liệu được cung cấp.',
            ]]],
            'contents' => [['role' => 'user', 'parts' => [['text' => $prompt]]]],
            'generationConfig' => [
                'temperature' => 0.7,
                'maxOutputTokens' => (int) config('services.gemini.max_output_tokens', 16384),
                'responseFormat' => ['text' => ['mimeType' => 'APPLICATION_JSON', 'schema' => $schema]],
            ],
        ];
    }

    /** @return array<string, true> từ khoá chính/tiêu đề đã có (chuẩn hoá) */
    private function coveredKeywords(): array
    {
        $posts = Post::withTrashed()->get(['title', 'seo'])->flatMap(fn (Post $p) => [
            $p->title,
            Str::before((string) data_get($p->seo, 'keywords'), ','),
        ]);
        $ideas = ContentIdea::query()->get(['title', 'primary_keyword'])->flatMap(fn ($i) => [$i->title, $i->primary_keyword]);

        return $posts->merge($ideas)->filter()->mapWithKeys(fn ($t) => [self::norm((string) $t) => true])->all();
    }

    private function coverFor(ContentIdea $idea): ?string
    {
        $products = Catalog::query('product')->published()->get();
        $product = ArticleContext::focusProducts($products, $idea->title.' '.$idea->primary_keyword)->first()
            ?? $products->first(fn (Product $p) => filled($idea->target_url) && Str::startsWith($idea->target_url, route('products.show', $p->slug, false)));

        return data_get($product?->hero, 'src') ?: (catalog_setting('social_image') ?: null);
    }

    /** @param array<string, string> $targets */
    private function fallbackTarget(array $targets): string
    {
        $bangGia = route('pages.show', 'bang-gia', false);

        return array_key_exists($bangGia, $targets) ? $bangGia : route('quote', [], false);
    }

    /** "Giá lăn bánh Lexus ES!" → "gia lan banh lexus es" — so trùng không phân biệt dấu/hoa. */
    private static function norm(string $text): string
    {
        return trim(preg_replace('/[^a-z0-9]+/', ' ', Str::lower(Str::ascii($text))) ?? '');
    }
}
