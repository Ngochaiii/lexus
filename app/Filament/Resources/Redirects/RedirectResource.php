<?php

namespace App\Filament\Resources\Redirects;

use App\Filament\Concerns\HasCatalogNavigation;
use App\Filament\Resources\Redirects\Pages\ManageRedirects;
use App\Support\Catalog;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class RedirectResource extends Resource
{
    use HasCatalogNavigation;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowsRightLeft;

    protected static ?int $navigationSort = 8;

    public static function getModel(): string
    {
        return Catalog::model('redirect');
    }

    public static function getModelLabel(): string
    {
        return 'Chuyển hướng';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Chuyển hướng';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Cấu hình';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            TextInput::make('from_path')
                ->label('Từ đường dẫn')
                ->required()
                ->unique(ignoreRecord: true)
                ->placeholder('/xe-cu/gx-460')
                ->helperText('Đường dẫn cũ, tính từ gốc website.'),

            TextInput::make('to_path')
                ->label('Tới đường dẫn')
                ->required()
                ->placeholder('/san-pham/lexus-gx-550'),

            Select::make('status_code')
                ->label('Mã trạng thái')
                ->options([
                    301 => '301 — chuyển hẳn',
                    302 => '302 — tạm thời',
                    410 => '410 — đã gỡ',
                ])
                ->default(301)
                ->required()
                ->selectablePlaceholder(false),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('hits', 'desc')
            ->columns([
                TextColumn::make('from_path')->label('Từ')->searchable()->copyable(),
                TextColumn::make('to_path')->label('Tới')->searchable(),
                TextColumn::make('status_code')->label('Mã')->badge(),
                TextColumn::make('hits')
                    ->label('Lượt dùng')
                    ->numeric()
                    ->sortable()
                    ->description('Bao nhiêu lượt truy cập đã đi qua luật này.'),
            ])
            // Filament 4 không tự thêm nút sửa/xoá nữa — không khai thì bảng
            // chỉ để ngắm: tạo được bản ghi rồi chịu.
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageRedirects::route('/')];
    }
}
