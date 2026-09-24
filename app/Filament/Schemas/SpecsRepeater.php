<?php

namespace App\Filament\Schemas;

use App\Support\SpecTableParser;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Set;

/**
 * `specs` — bảng thông số. Hai cách nhập:
 *   1. Dán bảng từ HTML (copy <table> từ web khác)
 *   2. Thêm nhóm / thêm dòng thủ công
 */
class SpecsRepeater
{
    public static function make(string $name = 'specs'): Repeater
    {
        return Repeater::make($name)
            ->hiddenLabel()
            ->addActionLabel('+ Thêm nhóm')
            ->defaultItems(0)
            ->reorderableWithDragAndDrop()
            ->collapsible()
            ->collapsed()
            ->itemLabel(fn (array $state): string => ($state['group'] ?? 'Nhóm chưa đặt tên')
                .'  ·  '.count($state['rows'] ?? []).' dòng')
            ->schema([
                TextInput::make('group')
                    ->label('Tên nhóm')
                    ->placeholder('Động Cơ & Hiệu Năng')
                    ->required()
                    ->columnSpanFull(),

                Repeater::make('rows')
                    ->hiddenLabel()
                    ->addActionLabel('+ Thêm dòng')
                    ->defaultItems(0)
                    ->reorderableWithDragAndDrop()
                    ->schema([
                        TextInput::make('label')->label('Nhãn')->required(),
                        TextInput::make('value')->label('Giá trị')->required(),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
            ])
            ->columnSpanFull();
    }

    /** Ô dán HTML, đặt ngay trên repeater specs. */
    public static function pasteField(string $target = 'specs'): Textarea
    {
        return Textarea::make('specs_paste')
            ->label('Dán bảng từ HTML')
            ->helperText('Copy nguyên thẻ <table> từ web khác rồi dán vào đây, rời chuột ra là tự đổ xuống bên dưới.')
            ->rows(3)
            ->dehydrated(false)
            ->live(onBlur: true)
            ->afterStateUpdated(function (?string $state, Set $set) use ($target) {
                if (blank($state)) {
                    return;
                }

                $specs = app(SpecTableParser::class)->parse($state);

                if ($specs === []) {
                    Notification::make()
                        ->title('Không đọc được bảng')
                        ->body('Kiểm tra lại xem đã copy cả thẻ <table> chưa.')
                        ->warning()
                        ->send();

                    return;
                }

                $set($target, $specs);
                $set('specs_paste', null);

                $rows = array_sum(array_map(fn (array $g) => count($g['rows']), $specs));

                Notification::make()
                    ->title('Đã đọc '.count($specs).' nhóm, '.$rows.' dòng')
                    ->success()
                    ->send();
            })
            ->columnSpanFull();
    }

    /**
     * Cùng ô dán, nhưng đổ ra MỘT danh sách dòng phẳng (không nhóm) — dùng cho
     * mục `sections` kiểu `table`, vốn chỉ là bảng phụ vài dòng.
     */
    public static function rowsPasteField(string $target = 'rows'): Textarea
    {
        return Textarea::make('rows_paste')
            ->label('Dán bảng từ HTML')
            ->helperText('Copy thẻ <table> rồi dán vào đây, rời chuột ra là tự đổ thành các dòng bên dưới.')
            ->rows(3)
            ->dehydrated(false)
            ->live(onBlur: true)
            ->afterStateUpdated(function (?string $state, Set $set) use ($target) {
                if (blank($state)) {
                    return;
                }

                $rows = collect(app(SpecTableParser::class)->parse($state))
                    ->flatMap(fn (array $group) => $group['rows'])
                    ->values()
                    ->all();

                if ($rows === []) {
                    Notification::make()
                        ->title('Không đọc được bảng')
                        ->body('Kiểm tra lại xem đã copy cả thẻ <table> chưa.')
                        ->warning()
                        ->send();

                    return;
                }

                $set($target, $rows);
                $set('rows_paste', null);

                Notification::make()
                    ->title('Đã đọc '.count($rows).' dòng')
                    ->success()
                    ->send();
            })
            ->columnSpanFull();
    }
}
