<?php

namespace App\Filament\Resources\Posts\Concerns;

use App\Jobs\GenerateArticleWithGemini;
use App\Support\PostContent;
use App\Support\SeoCheck;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Trang tạo/sửa bài hỏi lại kết quả job Gemini (wire:poll trong
 * filament/gemini-poll.blade.php) và điền bài vào form khi xong.
 */
trait PollsGeminiArticle
{
    public function pollGeminiArticle(): void
    {
        $key = data_get($this->data, 'ai_job');
        if (blank($key)) {
            return;
        }

        $result = Cache::get(GenerateArticleWithGemini::cacheKey($key));
        if (! is_array($result) || ($result['status'] ?? null) === 'running') {
            return;
        }

        data_set($this->data, 'ai_job', null);
        Cache::forget(GenerateArticleWithGemini::cacheKey($key));

        if ($result['status'] === 'error') {
            Notification::make()->title('Chưa tạo được bài viết')->body($result['message'])->danger()->persistent()->send();

            return;
        }

        $article = $result['article'];
        $title = (string) data_get($this->data, 'title');

        data_set($this->data, 'excerpt', $article['excerpt']);
        data_set($this->data, 'article_body', $article['article_html']);
        data_set($this->data, 'faq_text', PostContent::faqToText([[
            'type' => 'faq',
            'rows' => collect($article['faq'])->map(fn (array $q): array => ['label' => $q['question'], 'value' => $q['answer']])->all(),
        ]]));
        data_set($this->data, 'seo.title', $article['seo_title']);
        data_set($this->data, 'seo.description', $article['meta_description']);
        data_set($this->data, 'seo.keywords', implode(', ', $article['keywords']));

        // Slug chứa từ khoá chính — chỉ thay khi slug đang là bản tự sinh từ tiêu đề.
        if (filled($article['slug']) && data_get($this->data, 'slug') === Str::slug($title)) {
            data_set($this->data, 'slug', $article['slug']);
        }

        $checks = SeoCheck::article($article);
        $passed = collect($checks)->where('ok', true)->count();

        Notification::make()
            ->title("Gemini đã viết xong · SEO {$passed}/".count($checks))
            ->body('Từ khoá chính: '.$article['primary_keyword']."\n".SeoCheck::summary($checks)."\n\nĐọc lại số liệu trước khi chuyển sang Đã đăng.")
            ->success()
            ->persistent()
            ->send();
    }
}
