<?php

namespace App\Filament\Resources\Categories;

use App\Filament\Concerns\HasCatalogNavigation;
use App\Filament\Resources\Categories\Pages\ManageCategories;
use App\Support\Catalog;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class CategoryResource extends Resource
{
    use HasCatalogNavigation;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected static ?int $navigationSort = 2;

    public static function getModel(): string
    {
        return Catalog::model('category');
    }

    public static function getModelLabel(): string
    {
        return 'Danh mục';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Danh mục';
    }

    public static function getNavigationGroup(): ?string
    {
        return config('catalog.admin.navigation_group');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            TextInput::make('name')
                ->label('Tên')
                ->required()
                ->live(onBlur: true)
                ->afterStateUpdated(fn (?string $state, callable $set) => $set('slug', Str::slug((string) $state))),

            TextInput::make('slug')
                ->label('Slug')
                ->required()
                ->unique(ignoreRecord: true),

            Select::make('parent_id')
                ->label('Danh mục cha')
                ->relationship('parent', 'name')
                ->searchable()
                ->preload(),

            TextInput::make('sort')
                ->label('Thứ tự')
                ->numeric()
                ->default(0),

            Textarea::make('description')
                ->label('Mô tả')
                ->rows(3)
                ->columnSpanFull(),

            TextInput::make('seo.title')->label('Thẻ title'),
            Textarea::make('seo.description')->label('Meta description')->rows(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort')
            ->reorderable('sort')
            ->columns([
                TextColumn::make('name')->label('Tên')->searchable(),
                TextColumn::make('parent.name')->label('Danh mục cha')->badge()->color('gray'),
                TextColumn::make('products_count')->label('Số bản ghi')->counts('products'),
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
        return ['index' => ManageCategories::route('/')];
    }
}
