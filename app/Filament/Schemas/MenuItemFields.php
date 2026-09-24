<?php

namespace App\Filament\Schemas;

use App\Support\Catalog;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;

/**
 * Các ô của một mục menu. Dùng chung cho cấp 1 và cấp 2 nên khai báo một lần.
 *
 * Mục menu trỏ tới đâu là do `target_type` + `target_id`, hoặc gõ thẳng `url`.
 * Không hardcode Product::class — danh sách để chọn tra qua config.
 */
class MenuItemFields
{
    /** Các loại đích, đã lọc theo feature đang bật. */
    public static function targetTypes(): array
    {
        return array_filter([
            'product'  => Catalog::label('product.single'),
            'category' => 'Danh mục',
            'page'     => 'Trang',
            'post'     => Catalog::feature('posts') ? 'Bài viết' : null,
            'url'      => 'Đường dẫn tự nhập',
        ]);
    }

    /** @return array<int, \Filament\Schemas\Components\Component> */
    public static function make(): array
    {
        return [
            TextInput::make('label')
                ->label('Nhãn hiển thị')
                ->required(),

            Select::make('target_type')
                ->label('Trỏ tới')
                ->options(static::targetTypes())
                ->default('url')
                ->live()
                ->selectablePlaceholder(false)
                ->afterStateUpdated(fn (callable $set) => $set('target_id', null)),

            Select::make('target_id')
                ->label('Chọn bản ghi')
                ->options(fn (Get $get): array => static::optionsFor($get('target_type')))
                ->searchable()
                ->visible(fn (Get $get): bool => filled($get('target_type')) && $get('target_type') !== 'url')
                ->required(fn (Get $get): bool => filled($get('target_type')) && $get('target_type') !== 'url'),

            TextInput::make('url')
                ->label('Đường dẫn')
                ->placeholder('/lien-he hoặc https://...')
                ->visible(fn (Get $get): bool => $get('target_type') === 'url')
                ->required(fn (Get $get): bool => $get('target_type') === 'url'),
        ];
    }

    /**
     * Danh sách bản ghi để chọn, theo loại đích.
     *
     * @return array<int|string, string>
     */
    protected static function optionsFor(?string $targetType): array
    {
        if (blank($targetType) || $targetType === 'url') {
            return [];
        }

        $labelColumn = match ($targetType) {
            'post', 'page' => 'title',
            default        => 'name',
        };

        return Catalog::query($targetType)
            ->orderBy($labelColumn)
            ->pluck($labelColumn, 'id')
            ->all();
    }
}
