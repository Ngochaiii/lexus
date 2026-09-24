<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use App\Support\Catalog;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Icons\Heroicon;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->applyTemplateAction(),
        ];
    }

    /** Tạo từ mẫu: đổ sẵn khung mục trống, chỉ việc thay ảnh và chữ. */
    protected function applyTemplateAction(): Action
    {
        $templates = Catalog::query('template')
            ->where('entity_type', 'product')
            ->orderBy('name')
            ->pluck('name', 'id');

        return Action::make('applyTemplate')
            ->label('Tạo từ mẫu')
            ->icon(Heroicon::OutlinedBookmarkSquare)
            ->color('gray')
            ->visible($templates->isNotEmpty())
            ->schema([
                Select::make('template_id')
                    ->label('Mẫu bố cục')
                    ->options($templates)
                    ->required(),
            ])
            ->action(function (array $data): void {
                $template = Catalog::query('template')->findOrFail($data['template_id']);

                // fill() thay vì gán thẳng $this->data — để repeater tự sinh key.
                $this->form->fill([
                    ...$this->data,
                    'sections' => $template->blankSections(),
                    'specs'    => $template->payload['specs'] ?? [],
                ]);

                Notification::make()->title('Đã áp mẫu "'.$template->name.'"')->success()->send();
            });
    }
}
