<?php

namespace App\Filament\Resources\Forms;

use App\Filament\Concerns\HasCatalogNavigation;
use App\Filament\Resources\Forms\Pages\CreateForm;
use App\Filament\Resources\Forms\Pages\EditForm;
use App\Filament\Resources\Forms\Pages\ListForms;
use App\Support\Catalog;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class FormResource extends Resource
{
    use HasCatalogNavigation;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?int $navigationSort = 9;

    /** Các kiểu ô. `options` chỉ hiện với kiểu có lựa chọn. */
    public const TYPES = [
        'text' => 'Một dòng',
        'textarea' => 'Nhiều dòng',
        'email' => 'Email',
        'tel' => 'Điện thoại',
        'product' => 'Mẫu xe đang bán',
        'date' => 'Ngày',
        'select' => 'Danh sách chọn',
        'radio' => 'Chọn một',
        'checkbox' => 'Chọn nhiều',
        'hidden' => 'Ẩn',
    ];

    public static function getModel(): string
    {
        return Catalog::model('form');
    }

    public static function getModelLabel(): string
    {
        return 'Form';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Form';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Khách hàng';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Thông tin form')
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
                        ->helperText('Frontend gọi /api/v1/forms/{khoá}'),

                    Textarea::make('description')
                        ->label('Giới thiệu ngắn')
                        ->placeholder('Để lại thông tin, tư vấn viên sẽ liên hệ lại.')
                        ->helperText('Hiện ngay dưới tên form ở frontend.')
                        ->rows(2)
                        ->columnSpanFull(),

                    TextInput::make('success_message')
                        ->label('Lời cảm ơn sau khi gửi')
                        ->placeholder('Tư vấn viên sẽ gọi lại trong 15 phút.')
                        ->columnSpanFull(),

                    TagsInput::make('notify_emails')
                        ->label('Gửi thông báo tới email')
                        ->placeholder('Thêm email rồi Enter')
                        ->helperText('Bỏ trống thì không gửi mail.'),

                    TextInput::make('webhook_url')
                        ->label('Webhook')
                        ->url()
                        ->helperText('Mỗi lead sẽ được POST tới đây.'),

                    Toggle::make('is_active')
                        ->label('Đang bật')
                        ->default(true),
                ]),

            Section::make('Các ô nhập')
                ->description('Luật kiểm tra dữ liệu sinh thẳng từ đây — API không hardcode ô nào.')
                ->columnSpanFull()
                ->schema([
                    Repeater::make('fields')
                        ->hiddenLabel()
                        ->relationship()
                        ->defaultItems(0)
                        ->addActionLabel('+ Thêm ô')
                        ->orderColumn('sort')
                        ->reorderableWithDragAndDrop()
                        ->collapsible()
                        ->itemLabel(fn (array $state): ?string => $state['label'] ?? null)
                        ->schema([
                            TextInput::make('label')
                                ->label('Nhãn')
                                ->required()
                                ->live(onBlur: true)
                                ->afterStateUpdated(fn (?string $state, callable $set) => $set('key', Str::slug((string) $state, '_'))),

                            TextInput::make('key')
                                ->label('Khoá')
                                ->required()
                                ->helperText('Tên trường trong dữ liệu gửi lên.'),

                            Select::make('type')
                                ->label('Kiểu')
                                ->options(static::TYPES)
                                ->default('text')
                                ->required()
                                ->live()
                                ->selectablePlaceholder(false),

                            Select::make('width')
                                ->label('Chiều rộng')
                                ->options(['full' => 'Cả hàng', 'half' => 'Nửa hàng'])
                                ->default('full')
                                ->selectablePlaceholder(false),

                            TextInput::make('placeholder')
                                ->label('Chữ mờ gợi ý')
                                ->columnSpanFull(),

                            KeyValue::make('options')
                                ->label('Các lựa chọn')
                                ->keyLabel('Giá trị lưu')
                                ->valueLabel('Chữ hiển thị')
                                ->visible(fn (Get $get): bool => in_array($get('type'), ['select', 'radio', 'checkbox'], true))
                                ->required(fn (Get $get): bool => in_array($get('type'), ['select', 'radio', 'checkbox'], true))
                                ->helperText('Bắt buộc có ít nhất một lựa chọn để frontend tạo được ô nhập.')
                                ->columnSpanFull(),

                            Select::make('rules')
                                ->label('Ràng buộc')
                                ->multiple()
                                ->options([
                                    'required' => 'Bắt buộc nhập',
                                    'nullable' => 'Được để trống',
                                ])
                                ->default(['nullable'])
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
                TextColumn::make('fields_count')->label('Số ô')->counts('fields'),
                TextColumn::make('leads_count')->label('Lượt gửi')->counts('leads'),
                IconColumn::make('is_active')->label('Bật')->boolean(),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListForms::route('/'),
            'create' => CreateForm::route('/create'),
            'edit' => EditForm::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteKeyName(): ?string
    {
        return 'id';
    }
}
