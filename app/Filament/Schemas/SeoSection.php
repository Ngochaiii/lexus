<?php

namespace App\Filament\Schemas;

use App\Filament\Forms\Components\NativeMediaUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;

/**
 * Khối SEO dùng chung cho Product, Post, Page, Category.
 * Tất cả ghi vào cột `seo` json nên không cột nào phải thêm.
 */
class SeoSection
{
    public static function make(string $heading = 'SEO'): Section
    {
        return Section::make($heading)
            ->collapsible()
            ->collapsed()
            ->columns(2)
            ->schema([
                TextInput::make('seo.title')
                    ->label('Thẻ title')
                    ->maxLength(70)
                    ->helperText('Bỏ trống thì lấy tên bản ghi.'),

                TextInput::make('seo.canonical')
                    ->label('Canonical')
                    ->url(),

                Textarea::make('seo.description')
                    ->label('Meta description')
                    ->maxLength(170)
                    ->rows(2)
                    ->columnSpanFull(),

                NativeMediaUpload::make('seo.image')
                    ->label('Ảnh chia sẻ (OG)')
                    ->image()
                    ->directory('catalog/seo')
                    ->columnSpanFull(),
            ]);
    }
}
