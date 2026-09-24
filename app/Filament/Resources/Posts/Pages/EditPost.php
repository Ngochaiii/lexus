<?php

namespace App\Filament\Resources\Posts\Pages;

use App\Filament\Resources\Posts\Concerns\PollsGeminiArticle;
use App\Filament\Resources\Posts\PostResource;
use App\Support\PostContent;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPost extends EditRecord
{
    use PollsGeminiArticle;

    protected static string $resource = PostResource::class;

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
