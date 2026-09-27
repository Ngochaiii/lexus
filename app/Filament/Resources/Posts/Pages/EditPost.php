<?php

namespace App\Filament\Resources\Posts\Pages;

use App\Filament\Resources\Posts\Concerns\PollsGeminiArticle;
use App\Filament\Resources\Posts\PostResource;
use App\Jobs\GenerateShareKit;
use App\Support\PostContent;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Cache;

class EditPost extends EditRecord
{
    use PollsGeminiArticle;

    protected static string $resource = PostResource::class;

    /** wire:poll trong filament/share-kit-poll.blade.php — điền bài chia sẻ khi job xong. */
    public function pollShareKit(): void
    {
        $id = (int) data_get($this->data, 'share_job');
        $result = $id ? Cache::get(GenerateShareKit::cacheKey($id)) : null;
        if (! is_array($result) || ($result['status'] ?? null) === 'running') {
            return;
        }

        data_set($this->data, 'share_job', null);
        Cache::forget(GenerateShareKit::cacheKey($id));

        if ($result['status'] === 'error') {
            Notification::make()->title('Chưa soạn được bài chia sẻ')->body($result['message'])->danger()->persistent()->send();

            return;
        }

        data_set($this->data, 'share_kit', $this->getRecord()->fresh()->share_kit);
        Notification::make()->title('Gemini đã soạn xong bài chia sẻ')
            ->body('Đọc lại, sửa giọng cho giống bạn rồi bấm Copy từng ô. Link đã gắn mã theo kênh.')
            ->success()->send();
    }

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['article_body'] = PostContent::fromSections($data['sections'] ?? []);
        $data['faq_text'] = PostContent::faqToText($data['sections'] ?? []);

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['sections'] = PostContent::withFaq(
            PostContent::intoSections($data['article_body'] ?? null, $this->getRecord()->sections ?? []),
            $data['faq_text'] ?? null,
        );
        unset($data['article_body'], $data['faq_text'], $data['ai_instructions']);

        return $data;
    }
}
