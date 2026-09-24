<?php

namespace App\Filament\Resources\Menus;

use BackedEnum;
use App\Filament\Concerns\HasCatalogNavigation;
use App\Filament\Resources\Menus\Pages\EditMenu;
use App\Filament\Resources\Menus\Pages\ListMenus;
use App\Filament\Schemas\MenuItemFields;
use App\Support\Catalog;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class MenuResource extends Resource
{
    use HasCatalogNavigation;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBars3;

    protected static ?int $navigationSort = 6;

    public static function getModel(): string
    {
        return Catalog::model('menu');
    }

    public static function getModelLabel(): string
    {
        return 'Menu';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Menu';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Cấu hình';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Menu')
                ->columns(2)
                ->schema([
                    TextInput::make('name')
                        ->label('Tên')
                        ->required()
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn (?string $state, callable $set) => $set('key', Str::slug((string) $state))),

                    TextInput::make('key')
                        ->label('Khoá')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->helperText('Frontend gọi /api/v1/menus/{khoá}'),
                ]),

            Section::make('Các mục')
                ->description('Kéo thả để đổi thứ tự. Mỗi mục cấp 1 chứa được một cấp con.')
                ->columnSpanFull()
                ->schema([
                    Repeater::make('rootItems')
                        ->hiddenLabel()
                        ->relationship()
                        ->defaultItems(0)
                        ->addActionLabel('+ Thêm mục')
                        ->orderColumn('sort')
                        ->reorderableWithDragAndDrop()
                        ->collapsible()
                        ->collapsed()
                        ->itemLabel(fn (array $state): ?string => $state['label'] ?? null)
                        ->schema([
                            ...MenuItemFields::make(),

                            Repeater::make('children')
                                ->label('Mục con')
                                ->relationship()
                                ->defaultItems(0)
                                ->addActionLabel('+ Thêm mục con')
                                ->orderColumn('sort')
                                ->reorderableWithDragAndDrop()
                                ->collapsible()
                                ->collapsed()
                                ->itemLabel(fn (array $state): ?string => $state['label'] ?? null)
                                ->schema(MenuItemFields::make())
                                ->columns(2)
                                ->columnSpanFull(),
                        ])
                        ->columns(2)
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Tên')->searchable(),
                TextColumn::make('key')->label('Khoá')->badge()->color('gray')->copyable(),
                TextColumn::make('items_count')->label('Số mục')->counts('items'),
            ])
            ->headerActions([CreateAction::make()])
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMenus::route('/'),
            'edit'  => EditMenu::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteKeyName(): ?string
    {
        return 'id';
    }
}
