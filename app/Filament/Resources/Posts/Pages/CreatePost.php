<?php

namespace App\Filament\Resources\Posts\Pages;

use App\Filament\Resources\Posts\Concerns\PollsGeminiArticle;
use App\Filament\Resources\Posts\PostResource;
use App\Support\PostContent;
use Filament\Resources\Pages\CreateRecord;

class CreatePost extends CreateRecord
{
    use PollsGeminiArticle;

    protected static string $resource = PostResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['sections'] = PostContent::withFaq(
            PostContent::intoSections($data['article_body'] ?? null),
            $data['faq_text'] ?? null,
        );
        unset($data['article_body'], $data['faq_text'], $data['ai_instructions']);

        return $data;
    }
}
